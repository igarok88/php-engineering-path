<?php

declare(strict_types=1);

readonly class CartItem
{
    public function __construct(
        public string $name,
        public int $price,
        public int $qty
    ) {}

    public function getCost(): float
    {
        return $this->price * $this->qty;
    }
}

$cart = [
    new CartItem('Airflow',  100, 2),
    new CartItem('Laravel Book', 50,  1),
    new CartItem('Coffee',  10,  5),

];

// $total = array_reduce($cart, fn(float $carry, CartItem $product) => $carry + ($product->getCost() ?? 0), 0);

$total = array_reduce($cart, fn(float $carry, CartItem $product) => $carry + ($product->getCost()), 0);

echo "Total amount: $total";
