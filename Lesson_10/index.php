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

$repo = new FileOrderRepository();

$emailNotification = new EmailNotificationService();

$processor = new OrderProcessor($repo, $emailNotification);

$processor->process($cart);
