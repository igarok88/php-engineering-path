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
        echo "The order has been saved to file. Amount: " . $cart->getTotal();
    }
}

readonly class OrderProcessor
{
    public function __construct(
        public OrderRepositoryInterface $orderRepository,
    ) {}

    public function process(Cart $cart): void
    {
        $this->orderRepository->save($cart);
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

    // The Cart class accepts neither PhysicalProduct nor DigitalService specifically. 
    // It accepts any object that "knows how to pay" (PhysicalProduct and DigitalService implement PayableInterface). 
    // We can create a Subscription or GiftCard class, implement this interface (PayableInterface) in them, 
    // and the Cart will accept them without a single change to the Cart's code itself. 
    // This is the Open/Closed Principle (open for extension, closed for modification).
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

// Implements OrderRepositoryInterface and contains save(Cart $cart). 
// We can swap this class for another if we want to save data differently. 
// This is the Dependency Inversion Principle. Your main code does not depend on a specific file. 
// Imagine if tomorrow the business says: "We no longer store orders in files, now we use a MySQL database". 
// You won't have to rewrite the entire code. You simply create a new DatabaseOrderRepository class 
// that also implements the interface and substitute it into the $repo variable. 
// The rest of the system won't even notice the switch.
$repo = new FileOrderRepository();

// Accepts the OrderRepositoryInterface into the $orderRepository variable. 
// OrderProcessor has a process(Cart $cart) function that calls a method on $orderRepository: $this->orderRepository->save($cart); 
// The OrderProcessor class implies: "I don't care exactly how or where you save it. 
// Just give me a tool that has a save method. Whether it writes to disk, sends it via carrier pigeon, 
// or saves it to the cloud — that is not my problem".
$processor = new OrderProcessor($repo);

$processor->process($cart); // Saving the order to a file in the FileOrderRepository class (simulation)