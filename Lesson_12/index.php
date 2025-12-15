<?php

declare(strict_types=1);

interface OrderRepositoryInterface
{
    public function save(Cart $cart): void;
}

readonly class FileOrderRepository implements OrderRepositoryInterface
{

    public function save(Cart $cart): void
    {
        echo "Order saved to file. Total: " . $cart->getTotal() . "<br>";
    }
}

interface NotificationServiceInterface
{
    public function notify(string $message): void;
}

readonly class EmailNotificationService implements NotificationServiceInterface
{

    public function notify(string $message): void
    {
        echo "Email sent: $message";
    }
}

readonly class OrderProcessor
{
    public function __construct(
        public OrderRepositoryInterface $orderRepository,
        public NotificationServiceInterface $notificationService,
    ) {}

    public function process(Cart $cart): void
    {
        $this->orderRepository->save($cart);
        $this->notificationService->notify("Order created with total: " . $cart->getTotal() . ". <br>");
    }
}

interface PayableInterface
{
    public function getCost(): int;
    public function getName(): string;
}

readonly class PhysicalProduct implements PayableInterface
{
    public function __construct(
        public string $name,
        public int $price,
        public int $qty
    ) {

        if (empty($name)) {
            throw new \InvalidArgumentException('The name cannot be empty');
        }

        if ($price < 0) {
            throw new \InvalidArgumentException('The price should not be less than 0');
        }

        if ($qty <= 0) {
            throw new \InvalidArgumentException('The quantity must be greater than 0');
        }
    }

    public function getCost(): int
    {
        return $this->price * $this->qty;
    }
    public function getName(): string
    {
        return $this->name;
    }
}


readonly class DigitalService implements PayableInterface
{
    public function __construct(
        public string $name,
        public int $price,
    ) {

        if (empty($name)) {
            throw new \InvalidArgumentException('The name cannot be empty');
        }

        if ($price < 0) {
            throw new \InvalidArgumentException('The price should not be less than 0');
        }
    }

    public function getCost(): int
    {
        return $this->price;
    }
    public function getName(): string
    {
        return $this->name;
    }
}


final class Cart
{
    private array $items = [];

    public function add(PayableInterface $item): void
    {
        $this->items[] = $item;
    }

    public function getTotal(): int
    {
        $total = array_reduce($this->items, fn(int $carry, PayableInterface $product) => $carry + ($product->getCost()), 0);
        return $total;
    }
}

try {
    $cart = new Cart();
    $cart->add(new PhysicalProduct('Iphone', 1000, 1));
    $cart->add(new PhysicalProduct('Case', 50, 2));
    $cart->add(new DigitalService('Insurance', 75));
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}


class Container
{
    private array $services = [];

    public function set(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    public function get(string $id): object // 1. $container->get(OrderProcessor::class)
    {
        if (isset($this->services[$id])) {
            // 8. $id = 'OrderRepositoryInterface';
            // 12. $id = 'NotificationServiceInterface';
            $factory = $this->services[$id];
            return $factory($this);

            // $this - This is the Container object itself. We pass the container into the function.
            // Why? In case the object being created needs even more dependencies (not the case in this specific code),
            // it can ask the container for them again (via $c->get(...)).

            // $factory - This is the Closure (Anonymous function) you put into the $services array earlier.
            // Specifically this one: fn() => new FileOrderRepository()

            // $factory($this) == 
            // Roughly speaking, this happens:
            // (fn() => new FileOrderRepository())($container);

            // In the specific example with FileOrderRepository, the $container variable is not used and disappears.
            // It has empty parentheses fn(). It says: "I don't need anything as input".
            // In PHP, this is not an error. You can pass an argument to a function that doesn't expect it.
            // The function will simply ignore the passed value.

            // When you write return $factory($this);, the following happens:
            // Execution: The recipe function for the repository runs.
            // Result: The function returns a brand new FileOrderRepository object.
            // Return: The get method returns this object back to where it was called (into the foreach loop inside the Reflection logic for OrderProcessor).
        }


        $reflector = new ReflectionClass($id);

        // 2. $reflector = ReflectionClass Object([name] => OrderProcessor)

        $constructor = $reflector->getConstructor();

        // 3. $constructor = ReflectionMethod Object
        // (
        //     [name] => __construct
        //     [class] => OrderProcessor
        // )


        if ($constructor === null) {
            return new $id();
        }

        $parameters = $constructor->getParameters();

        // 4. $parameters = Array
        // (
        //     [0] => ReflectionParameter Object
        //         (
        //             [name] => orderRepository
        //         )

        //     [1] => ReflectionParameter Object
        //         (
        //             [name] => notificationService
        //         )

        // )

        $dependencies = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // 5. $type =
            // ReflectionNamedType Object()

            // 9. $type =
            // ReflectionNamedType Object()


            // If the type is not specified or is a primitive (string/int) — autowiring will fail.

            // isBuiltin() — this is a reflection method found in ReflectionClass, ReflectionFunction, 
            // ReflectionParameter, and other objects. It indicates whether the element is built-in to PHP 
            // (i.e., written in C in the PHP core), rather than declared in user code.
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new Exception("Cannot autowire parameter '{$parameter->getName()}' in class $id");
            }

            $dependencyClassName = $type->getName();

            // 6. $dependencyClassName = OrderRepositoryInterface

            // 10. $dependencyClassName =  NotificationServiceInterface

            $dependencies[] = $this->get($dependencyClassName);

            // 7. The constructor needs OrderRepositoryInterface.
            // It calls itself (recursion): $this->get('OrderRepositoryInterface').
            // And here we enter the 'get' method for the second time. The condition if (isset($this->services[$id])) 
            // evaluates to TRUE because we added this interface via set.

            // 11. The constructor needs NotificationServiceInterface.
            // It calls itself (recursion): $this->get('NotificationServiceInterface').
        }

        // newInstanceArgs() — is a ReflectionClass method in PHP that allows creating a new object of a class
        // by passing an array of arguments to the constructor.

        // Thus, the container assembled the puzzle:
        // Created FileOrderRepository (via $factory($this)).
        // Created EmailNotificationService (via $factory($this)).
        // Inserted them into OrderProcessor (via newInstanceArgs).

        // 13. ReflectionClass -> newInstanceArgs(...)
        // The container takes the two obtained objects.
        // Creates new OrderProcessor($dependencies[0], $dependencies[1]).
        // Returns the finished result.

        // The $dependencies array contains: [Object(FileOrderRepository), Object(EmailNotificationService)]

        return $reflector->newInstanceArgs($dependencies);
    }
}

$container = new Container();

$container->set(OrderRepositoryInterface::class, fn() => new FileOrderRepository());
$container->set(NotificationServiceInterface::class, fn() => new EmailNotificationService());


$container->get(OrderProcessor::class)->process($cart);
