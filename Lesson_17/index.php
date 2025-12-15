<?php

// php -S localhost:8000

declare(strict_types=1);

// --- 1. DOMAIN LAYER (Business Entities) ---

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

interface PayableInterface
{
    public function getCost(): int;
    public function getName(): string;
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


    public function toPdf(): string // Inside this function, we are guaranteed to be able to call methods of BOTH interfaces.
    {
        return "📄 PDF Invoice for {$this->name} - {$this->getCost()} <br>";
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

// --- 2. SERVICE LAYER (Services) ---
interface OrderRepositoryInterface
{
    public function save(Cart $cart): void;
}

interface NotificationServiceInterface
{
    public function notify(NotificationType $notificationType, Currency $currency, string $message,): void;
}

// Implementations
#[Singleton]
readonly class FileOrderRepository implements OrderRepositoryInterface
{

    public function save(Cart $cart): void
    {
        echo "[DB] Order saved to file. Total: " . $cart->getTotal() . "<br>";
    }
}

#[Singleton]
readonly class EmailNotificationService implements NotificationServiceInterface
{

    public function notify(NotificationType $notificationType, Currency $currency, string $message,): void
    {
        echo $notificationType->getIcon() . "[Email] {$message}  {$currency->getLabel()} <br>";
    }
}

// --- 3. BUSINESS LOGIC (What we call in the controller) ---

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

interface ExportableInterface
{
    public function toPdf(): string;
}

readonly class InvoiceGenerator
{
    public function generate(PayableInterface&ExportableInterface $item): void
    {
        echo "Order total: {$item->getCost()} <br>";
        echo $item->toPdf();
    }
}

// --- 4. FRAMEWORK CORE (Core) ---

#[Attribute]
class Singleton {}

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

readonly class Request
{
    public function __construct(
        public string $path // Example: '/home'
    ) {}
}

class Router // Knows which URL corresponds to which class.
{
    public array $routes = ['/home' => HomeController::class]; // Binding path to class
    public function resolve(string $path): string
    {

        if (!array_key_exists($path, $this->routes)) {
            throw new Exception("Page not found 404");
        }
        return $this->routes[$path];
    }
}

// --- 5. CONTROLLERS (Entry point for pages) ---

readonly class HomeController
{
    public function __construct(
        public OrderProcessor $orderProcessor
    ) {}
    public function index(): string
    {
        // Simulation: User arrived at the checkout page.
        // We create a cart (in reality, data would come from a $_POST form)
        $cart = new Cart();
        $cart->add(new PhysicalProduct('Iphone 15', 1000, 1, Currency::USD));

        // Run business logic
        $this->orderProcessor->process($cart);

        return "HTTP 200 OK: Order successfully processed via MVC pattern!";
    }
}

// --- 6. EXECUTION (index.php) ---

class Kernel
{
    public function __construct(
        public Container $container
    ) {}

    public function handle(Request $request): void
    {
        // 1. Find WHO is responsible for this path
        $router = $this->container->get(Router::class);

        // 2. Ask the router: "Who handles this path?"
        // Pass the path string, get the class name (e.g., "HomeController")
        $controllerClass = $router->resolve($request->path); // Result: string "HomeController"

        // 3. MAGIC: Ask the container to create THIS controller with all dependencies
        $controller = $this->container->get($controllerClass);

        // 4. Execute the method
        echo $controller->index();
    }
}


// --- LAUNCH ---

try {
    $cart = new Cart();
    $cart->add(new PhysicalProduct('Iphone', 1000, 1, Currency::USD));
    $cart->add(new PhysicalProduct('Case', 50, 2, Currency::USD));
    // $cart->add(new DigitalService('Insurance', 75, Currency::USD)); // Fatal Error
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}

// 1. Configuration
$container = new Container();

$container->set(OrderRepositoryInterface::class, fn() => new FileOrderRepository());
$container->set(NotificationServiceInterface::class, fn() => new EmailNotificationService());

// 2. Bootstrapping

$kernel = new Kernel($container);

// 3. Handling
// $request = new Request($_SERVER['REQUEST_URI']);
$request = new Request('/home');

$kernel->handle($request);


// $container->get(OrderProcessor::class)->process($cart);

// $generator = $container->get(InvoiceGenerator::class);

// foreach ($cart->getItems() as $product) {
//     $generator->generate($product);
// }