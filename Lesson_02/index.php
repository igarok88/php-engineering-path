<?php

declare(strict_types=1);


$cart = [
    ['name' => 'Airflow', 'price' => 100, 'qty' => 2],
    ['name' => 'Laravel Book', 'price' => 50, 'qty' => 1],
    ['name' => 'Coffee', 'price' => 10, 'qty' => 5],
];


function calculateCartTotal(array $cart): float
{
    $total = 0;
    foreach ($cart as $product) {

        if (isset($product['price']) && isset($product['qty'])) {
            $total += $product['price'] * $product['qty'];
        }
    }

    return $total;
}

echo "Total amount: " . calculateCartTotal($cart);
