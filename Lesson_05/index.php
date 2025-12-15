<?php

declare(strict_types=1);

readonly class CartItem
{
    public function __construct(
        public string $name,
        public int $price,
        public int $qty
    ) {}

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

$cart = new Cart();
$cart->add(new CartItem('Iphone', 1000, 1));
$cart->add(new CartItem('Case', 50, 2));

echo "Total: " . $cart->getTotal();
