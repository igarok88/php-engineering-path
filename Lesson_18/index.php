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

// Event-Driven Architecture

// 1. EVENT DTO

readonly class OrderCreatedEvent // This is an object that contains the fact that something happened (e.g., "Order was created").
{
    public function __construct(
        public Cart $cart // The $event object will contain the $cart field, which holds order info (items, total).
    ) {}
}

// 2. LISTENERS (Consumers/Subscribers)

// Listeners are classes or functions interested in a specific event that perform side effects in response to it.
// They know about the event (OrderCreatedEvent), but not about its source (OrderProcessor).
#[Singleton]
readonly class EmailNotificationListener
{

    // The method must be named clearly, e.g., onOrderCreated, or __invoke

    // onOrderCreated — these are handler methods that will be called when the event is received.
    public function onOrderCreated(OrderCreatedEvent $event): void
    {
        $total = $event->cart->getTotal();
        echo "📧 [EmailListener] Sending email to client. Order total: {$total} <br>";
    }
}

#[Singleton]
readonly class LogListener
{
    public function onOrderCreated(OrderCreatedEvent $event): void
    {
        echo "📝 [LogListener] Log entry: new order created. <br>";
    }
}

// 3. DISPATCHER (CORE)

// The Dispatcher (Event Bus/Broker) is the heart of the architecture. It is responsible for:
// Registering listeners for specific events (subscribe).
// Delivering the event to all subscribed listeners (dispatch).
class EventDispatcher
{

    /** @var array<string, array<callable>> */

    // Storing values ($listeners Array): At the moment of "Wiring" (Step 5), the Dispatcher accumulates references to listeners.
    private array $listeners = [];

    public function subscribe(string $eventName, callable $listener): void // Registering listeners for specific events
    {
        // We add the listener to the array under the key of the event name
        $this->listeners[$eventName][] = $listener;
        //     private array $listeners = [
        //     // Key is the full class name of the event
        //     'OrderCreatedEvent' => [
        //         0 => [EmailNotificationListener, 'onOrderCreated'], // reference to the first listener
        //         1 => [LogListener, 'onOrderCreated'], // reference to the second listener
        //     ],
        //     // ... other events
        // ];
    }

    public function dispatch(object $event): void // Delivering the event to all subscribed listeners
    {
        $eventName = get_class($event); // Example: "OrderCreatedEvent"

        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                // Call the listener function and pass the event to it
                $listener($event); // for the first listener: [$emailListener, 'onOrderCreated']($event)
            }
        }
    }
}

// 4. BUSINESS LOGIC (Refactoring OrderProcessor)


// OrderProcessor is the Event Producer/Publisher. Its only task is to execute the main logic (save the order) and publish the fact that this logic was executed.
readonly class OrderProcessor
{
    public function __construct(
        public OrderRepositoryInterface $orderRepository,
        public EventDispatcher $eventDispatcher,
    ) {}

    public function process(Cart $cart): void
    {
        // 1. Save the order (main logic)
        $this->orderRepository->save($cart);

        // 2. Throw the event "Into the fire" (Fire and Forget)
        // OrderProcessor doesn't care who catches it.
        $event = new OrderCreatedEvent($cart); // Creating the fact object.
        $this->eventDispatcher->dispatch($event); // Passing the event to the Dispatcher. At this moment, OrderProcessor finishes its work; it does not wait or care what happens next.
    }
}

// --- 5. WIRING (Wiring everything together) ---

// 1. Configuration
$container = new Container();

$container->set(OrderRepositoryInterface::class, fn() => new FileOrderRepository());

// !!! IMPORTANT: Dispatcher Configuration !!! We tell the Dispatcher which listeners should react to which events.
$container->set(EventDispatcher::class, function (Container $c) {
    $dispatcher = new EventDispatcher(); // Instance of $dispatcher is created

    // Create listeners (can also be done via get to trigger autowiring of listener dependencies if they exist)
    // Instances of $emailListener and $logListener are created
    $emailListener = new EmailNotificationListener();
    $logListener = new LogListener();

    // Subscribe them to the event
    // Callable array syntax: [$object, 'methodName']
    $dispatcher->subscribe(OrderCreatedEvent::class, [$emailListener, 'onOrderCreated']);
    $dispatcher->subscribe(OrderCreatedEvent::class, [$logListener, 'onOrderCreated']);

    return $dispatcher;
});

$container->set(OrderProcessor::class, function (Container $c) {
    return new OrderProcessor(
        $c->get(OrderRepositoryInterface::class),
        $c->get(EventDispatcher::class)
    );
});

// --- LAUNCH ---

try {
    $cart = new Cart();
    $cart->add(new PhysicalProduct('Iphone', 1000, 1, Currency::USD));
    $cart->add(new PhysicalProduct('Case', 50, 2, Currency::USD));
    // $cart->add(new DigitalService('Insurance', 75, Currency::USD)); // Fatal Error
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}




// 2. Initialization (Bootstrapping)

$kernel = new Kernel($container);

// 3. Handling Request
// $request = new Request($_SERVER['REQUEST_URI']);
$request = new Request('/home');

$kernel->handle($request);


// When $kernel->handle($request); is called, the following happens (simplified, focusing on order logic):

// Somewhere inside Kernel and Request, it determines that OrderProcessor::process needs to be called.

// The OrderProcessor object (retrieved from the container) calls the process method with the $cart object.

// OrderProcessor saves the order.

// OrderProcessor creates $event and calls $this->eventDispatcher->dispatch($event);.

// Inside EventDispatcher::dispatch:

// Gets $eventName = "OrderCreatedEvent".

// Finds the array of listeners for this event in $this->listeners.

// Loop:

// Calls $listener($event) for the first listener: [$emailListener, 'onOrderCreated']($event).

// Output: 📧 [EmailListener] Sending email to client. Order total: 1100 <br>

// Calls $listener($event) for the second listener: [$logListener, 'onOrderCreated']($event).

// Output: 📝 [LogListener] Log entry: new order created. <br>