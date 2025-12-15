### Lesson #16: Strategy Pattern and Legacy Refactoring

We have learned to write beautiful new code. But 80% of a Senior Developer's work is refactoring old, terrible code (Legacy) that is scary to touch.

The methodology states:

> "Huge `if/else` or `switch` structures must be replaced with polymorphic structures. Each branch of the condition turns into a separate class".

#### 1\. THEORY

**Problem: Switch Statements** Imagine we have a delivery cost calculation. In "bad" code, it looks like this:

PHP

        // Legacy code
        class DeliveryCalculator {
            public function calculate(string $type, int $distance): int {
                if ($type === 'car') {
                    return $distance * 10;
                } elseif ($type === 'plane') {
                     return $distance * 100;
                } elseif ($type === 'drone') {
                     return $distance * 50;
                }
                throw new Exception("Unknown type");
            }
        }

Why is this bad?

1.  **Violation of Open/Closed:** To add a "ship," you have to break this class.
2.  **Complexity:** If the "drone" logic is complex (considering wind, weight), this method will turn into a 500-line monster.

**Solution: Strategy Pattern** We extract the calculation _algorithm_ into separate classes.

1.  Interface `DeliveryStrategyInterface` (method `calculateCost`).
2.  Classes `CarStrategy`, `PlaneStrategy`.
3.  The calculator simply receives the needed strategy and calls the method.

#### 2\. TASK (Practice: Refactoring)

I am giving you "bad" code. Your task is to refactor it using the Strategy pattern.

**Initial "bad" code (Payment Gateway Commission Calculation System):**

PHP

        class CommissionCalculator
        {
            public function calculate(string $paymentMethod, int $amount): float
            {
                switch ($paymentMethod) {
                    case 'credit_card':
                        // 2% + $1 fixed
                        return $amount * 0.02 + 1;
                    case 'paypal':
                        // 5% commission
                        return $amount * 0.05;
                    case 'crypto':
                        // 0% commission
                        return 0;
                    default:
                        throw new Exception("Unknown method");
                }
            }
        }

**Task:**

1.  Create an interface `CommissionStrategyInterface`.

    - Method: `calculate(int $amount): float`.

2.  Create 3 strategy classes:

    - `CreditCardStrategy`
    - `PaypalStrategy`
    - `CryptoStrategy`

3.  Rewrite the `CommissionCalculator` class.

    - Remove `switch`.
    - The `calculate` method must accept a **strategy object** (`CommissionStrategyInterface $strategy`), not a string.

4.  **Client Code:**

    - Create a calculator.
    - Create a strategy instance for PayPal.
    - Call the calculator, passing the strategy and an amount (e.g., 100).
    - Output the result.

**Constraints:**

- Strict typing.
- No `switch` or `if` in the calculator.
- The calculator must not know about the existence of specific `Paypal` or `Crypto`. It knows only the interface.

Awaiting the refactored code.
