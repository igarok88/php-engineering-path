<?php

declare(strict_types=1);


readonly class CartItem
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
}


final class Cart
{
    private array $items = [];

    public function add(CartItem $item): void
    {
        $this->items[] = $item;
    }

    public function getTotal(): int
    {
        $total = array_reduce($this->items, fn(int $carry, CartItem $product) => $carry + ($product->getCost()), 0);
        return $total;
    }
}

try {
    $cart = new Cart();
    $cart->add(new CartItem('Iphone', 1000, 1));
    $cart->add(new CartItem('Case', 50, 2));
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}

echo "Total: " . $cart->getTotal();
