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
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        if ($price < 0) {
            throw new \InvalidArgumentException('Price cannot be less than 0');
        }

        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
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


    public function toPdf(): string
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
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        if ($price < 0) {
            throw new \InvalidArgumentException('Price cannot be less than 0');
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
    public function count(): int
    {
        return count($this->items);
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
        echo "Order Total: {$item->getCost()} <br>";
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
        public string $path
    ) {}
}

class Router
{
    public array $routes = ['/home' => HomeController::class];
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

        $cart = new Cart();
        $cart->add(new PhysicalProduct('Iphone 15', 1000, 1, Currency::USD));

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
        $router = $this->container->get(Router::class);

        $router->resolve($request->path);

        $controllerClass = $router->resolve($request->path);

        $controller = $this->container->get($controllerClass);

        echo $controller->index();
    }
}

// Event-Driven Architecture

// 1. EVENT DTO

readonly class OrderCreatedEvent
{
    public function __construct(
        public Cart $cart
    ) {}
}

// 2. LISTENERS 


#[Singleton]
readonly class EmailNotificationListener
{


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


class EventDispatcher
{

    /** @var array<string, array<callable>> */


    private array $listeners = [];

    public function subscribe(string $eventName, callable $listener): void
    {

        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $eventName = get_class($event);

        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {

                $listener($event);
            }
        }
    }
}

// 4. BUSINESS LOGIC (Refactoring OrderProcessor)



readonly class OrderProcessor
{
    public function __construct(
        public OrderRepositoryInterface $orderRepository,
        public EventDispatcher $eventDispatcher,
    ) {}

    public function process(Cart $cart): void
    {

        $this->orderRepository->save($cart);


        $event = new OrderCreatedEvent($cart);
        $this->eventDispatcher->dispatch($event);
    }
}


// ---  (Memory Optimization) ---

/**
 * Interface for reading data from a source.
 */
interface ReaderInterface
{
    /**
     * @return Generator<int, array>
     */
    public function read(string $path): Generator;
}

class CsvReader implements ReaderInterface
{
    /**
     * @return Generator<int, array>
     * @throws RuntimeException If file not found or headers are invalid
     */
    public function read(string $path): Generator //$path: 'import.csv'
    {
        // Return to Generator (Second product). PHP "unfreezes" the read() method exactly where it slept (right after yield). The while loop continues.



        // 1. Open stream. This is just a pointer to the file, not the file itself.
        // fopen opens the file, sets the cursor to the beginning of the file, returns a pointer to this file. PHP remembers where it stopped. On the next access, the pointer will be on the 2nd line.
        // 'rb' r — read, b — binary

        // $handle: This is not the file content! It is a Resource (Resource id #...). Imagine it as a "phone number" to call the file. We haven't read anything yet, the cursor is at the very beginning (byte 0).
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new \RuntimeException("Failed to open file: $path");
        }

        // 2. Read the first line separately to get headers (array keys)

        // fgetcsv() reads one line from the file, parses it as CSV, returns an array of values. e.g. ['name', 'price', 'qty']
        // The function reads the first line and moves the file cursor forward.
        $headers = fgetcsv($handle);
        //$headers: [
        //     0 => 'name',
        //     1 => 'price',
        //     2 => 'qty'
        // ]

        if (empty($headers)) {
            fclose($handle);
            return;
        }

        // 3. Start reading loop (First product)
        // feof() checks if we have reached the end of the file
        while (!feof($handle)) {
            // Read the next line. The file cursor moves to the second line.
            $row = fgetcsv($handle);
            //             $row = [
            //     0 => 'Samsung TV',
            //     1 => '500',
            //     2 => '10'
            // ]

            // Skip empty lines or reading errors
            if ($row === false || $row === null) {
                continue;
            }

            // Check data integrity (column count must match)
            if (count($headers) !== count($row)) {
                continue; // Or throw exception, depending on strictness
            }

            // 4. array_combine magic: make ['price' => 500] instead of [1 => 500]
            // PHP takes keys from $headers and values from $row and combines them.
            $data = array_combine($headers, $row);
            //             $data:[
            //     'name'  => 'Samsung TV',
            //     'price' => '500',
            //     'qty'   => '10'
            // ]

            // 5. YIELD: Spit out the ready array and pause execution.
            // Memory occupied by variables inside this loop will be freed in the next iteration.
            yield $data; // At any given moment, the $data variable contains only one array (one table row). As soon as we move to the next row, the previous one is erased from $data and frees up space.

            // KEY MOMENT: YIELD

            // WHAT HAPPENS: The read() function freezes. It doesn't close, variables $handle, $headers are not deleted. They are "frozen" in RAM.
            // The value of $data is "thrown" out to the main file index.php.
        }

        // 6. Always close resources
        fclose($handle);
    }
}

// --- CLIENT CODE (Execution) ---

// Memory measurement BEFORE start
$startMemory = memory_get_usage();

echo "<h3>Importing products...</h3>";

try {
    $reader = new CsvReader();
    $cart = new Cart();

    // We simply start the loop. The read() method does not execute entirely at once!
    // It will execute in pieces exactly as many times as the loop runs.
    foreach ($reader->read('import.csv') as $row) {
        // Here $row is what yield ($data) spit out


        // Data transformation
        $name = $row['name']; // "Samsung TV"
        $price = (int)$row['price']; // 500
        $qty = (int)$row['qty'];

        // Create business object. Memory is spent ONLY on this one object.
        $product = new PhysicalProduct(
            $name,
            $price,
            $qty,
            Currency::USD
        );

        // Add to cart
        $cart->add($product);

        // Process visualization (for clarity)
        echo "Processed: {$name} (Memory: " . round(memory_get_usage() / 1024, 2) . " KB)<br>";
    } // As soon as the code reaches the closing bracket } of the foreach loop, it says: "I need the next element!". Return to Generator (Second product)

    echo "<hr>";
    echo "Total items in cart: " . $cart->count() . "<br>";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Memory measurement AFTER
$endMemory = memory_get_usage();
$peakMemory = memory_get_peak_usage();

echo "<h3>Memory Statistics:</h3>";
echo "Start Memory: " . round($startMemory / 1024, 2) . " KB<br>";
echo "End Memory: " . round($endMemory / 1024, 2) . " KB<br>";
echo "<b>Peak Memory: " . round($peakMemory / 1024, 2) . " KB</b>";
