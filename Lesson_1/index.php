<?php

declare(strict_types=1);


$productName = 'Iphone';
$price = 1000;
$quantity = 100;
$discount = 0.15;


function cartFinalPrice(int $price, int $quantity, float $discount): float
{
    //return $price * $quantity - $price * $quantity * $discount;
    return ($price * $quantity) * (1.0 - $discount);
}

$total = cartFinalPrice($price, $quantity, $discount);

echo "Product: $productName <br>";
echo "Total to be paid: $total <br>";


var_dump(cartFinalPrice($price,  $quantity,  $discount));
