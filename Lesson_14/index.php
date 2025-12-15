<?php

declare(strict_types=1);

enum Currency: string
{
    // Value — for the database and code (ISO 4217)
    case USD = 'USD';
    case EUR = 'EUR';

    // Method — for human-readable display
    public function getLabel(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
        };
    }
}
// Usage:
// echo Currency::USD->value; // Outputs "USD"
// echo Currency::USD->getLabel(); // Outputs "$"

enum NotificationType: string
{

    // case — is a ready-made Singleton Object. It is unique. NotificationType::Email is always equal only to itself.
    case Email = 'Email';
    case Sms = 'Sms';

    public function getIcon(): string
    {

        // match - works like a 'switch': it looks at the current value ($this = NotificationType::Email), 
        // compares it with the options, and returns the result immediately.

        // self::Email - Take the Email option defined right here in this class (NotificationType). 
        // Points to the class context, accessed via ::. Used for Constants, Enums, Static methods.

        // $this - points to the object instance, accessed via ->. Needed for regular variables and methods.
        return match ($this) {
            self::Email => '📧',
            self::Sms => '📱',
        };
    }
}

#[Attribute]
class Singleton {}

interface OrderRepositoryInterface
{
    public function save(Cart $cart): void;
}

#[Singleton]
readonly class FileOrderRepository implements OrderRepositoryInterface
{

    public function save(Cart $cart): void
    {
        echo "Order saved to file. Total: " . $cart->getTotal() . "<br>";
    }
}

interface NotificationServiceInterface
{
    public function notify(NotificationType $notificationType, Currency $currency, string $message,): void;
}

#[Singleton]
readonly class EmailNotificationService implements NotificationServiceInterface
{

    public function notify(NotificationType $notificationType, Currency $currency, string $message,): void
    {
        echo $notificationType->getIcon() . "Email sent: " . $message . $currency->getLabel();
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
        $this->notificationService->notify(NotificationType::Email, Currency::USD,  "Order created with total amount " . $cart->getTotal());
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
        public int $qty,
        public Currency $currency
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
        public Currency $currency
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
    $cart->add(new PhysicalProduct('Iphone', 1000, 1, Currency::USD));
    $cart->add(new PhysicalProduct('Case', 50, 2, Currency::USD));
    $cart->add(new DigitalService('Insurance', 75, Currency::USD));
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}


class Container
{
    private array $services = [];
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    public function get(string $id): object
    {

        // Cache
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->services[$id])) {

            $factory = $this->services[$id];
            $object = $factory($this);
        } else {

            $reflector = new ReflectionClass($id);

            $constructor = $reflector->getConstructor();

            if ($constructor === null) {
                return new $id();
            }

            $parameters = $constructor->getParameters();

            $dependencies = [];
            foreach ($parameters as $parameter) {
                $type = $parameter->getType();

                if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    throw new Exception("Cannot autowire parameter '{$parameter->getName()}' in class $id");
                }

                $dependencyClassName = $type->getName();

                $dependencies[] = $this->get($dependencyClassName);
            }
            $object = $reflector->newInstanceArgs($dependencies);
        }



        // 3. Check for the Singleton attribute on the instantiated object
        $reflector = new ReflectionObject($object);
        if (count($reflector->getAttributes(Singleton::class)) > 0) {
            $this->instances[$id] = $object;
        }

        return $object;
    }
}

$container = new Container();

$container->set(OrderRepositoryInterface::class, fn() => new FileOrderRepository());
$container->set(NotificationServiceInterface::class, fn() => new EmailNotificationService());


$container->get(OrderProcessor::class)->process($cart);
