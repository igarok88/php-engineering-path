<?php

declare(strict_types=1);


class CommissionCalculator
{
    public function calculate(CommissionStrategyInterface $strategy, int $amount): float
    {
        return $strategy->calculate($amount);
    }
}

interface CommissionStrategyInterface
{
    public function calculate(int $amount): float;
}

readonly class CreditCardStrategy implements CommissionStrategyInterface
{
    public function calculate(int $amount): float
    {
        return $amount * 0.02 + 1; // 2% + $1
    }
}
readonly class PaypalStrategy implements CommissionStrategyInterface
{
    public function calculate(int $amount): float
    {
        return $amount * 0.05; // 5%
    }
}
readonly class CryptoStrategy implements CommissionStrategyInterface
{
    public function calculate(int $amount): float
    {
        return 0; // 0%
    }
}


$calculator = new CommissionCalculator();

$paypal = new PaypalStrategy();

$calculator->calculate($paypal, 100);

echo "Commission: {$calculator->calculate($paypal, 100)}$";
