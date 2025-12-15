<?php

declare(strict_types=1);

/**
 * @var array<int, array{name: string, price: int, qty: int}> $cart
 */
$cart = [
    ['name' => 'Airflow', 'price' => 100, 'qty' => 2],
    ['name' => 'Laravel Book', 'price' => 50, 'qty' => 1],
    ['name' => 'Coffee', 'price' => 10, 'qty' => 5],
];

$total = array_reduce($cart, fn(int $carry, array $product) => $carry + ($product['price'] ?? 0) * ($product['qty'] ?? 0), 0);

echo "Total amount: $total";
