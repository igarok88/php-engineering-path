<?php

declare(strict_types=1);

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';

    public function getLabel(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
        };
    }
}

enum NotificationType: string
{

    case Email = 'Email';
    case Sms = 'Sms';

    public function getIcon(): string
    {

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
        echo $notificationType->getIcon() . "Email sent: " . $message . $currency->getLabel() . "<br>";
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

interface ExportableInterface
{
    public function toPdf(): string;
}

readonly class PhysicalProduct implements PayableInterface, ExportableInterface
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


    // Inside this function, we are guaranteed to be able to call methods of BOTH interfaces.
    public function toPdf(): string
    {
        return "📄 PDF Invoice for {$this->name} - {$this->getCost()} <br>";
    }
}

readonly class InvoiceGenerator
{
    // Intersection Type: The argument must implement BOTH PayableInterface AND ExportableInterface
    public function generate(PayableInterface&ExportableInterface $item): void
    {
        echo "Item total: {$item->getCost()} <br>";
        echo $item->toPdf();
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
    public function getItems(): array
    {
        return $this->items;
    }
}

try {
    $cart = new Cart();
    $cart->add(new PhysicalProduct('Iphone', 1000, 1, Currency::USD));
    $cart->add(new PhysicalProduct('Case', 50, 2, Currency::USD));
    // $cart->add(new DigitalService('Insurance', 75, Currency::USD)); // Fatal Error: DigitalService does not implement ExportableInterface
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



        // 3. Check for the Singleton attribute on the finished object
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

$generator = $container->get(InvoiceGenerator::class);

foreach ($cart->getItems() as $product) {
    // Only items that are BOTH Payable AND Exportable will work here.
    // DigitalService would fail here because it is not Exportable.
    $generator->generate($product);
}
