### Lesson #1: Variables, Data Types, and Strict Typing

#### 1\. THEORY

**Concept:** In PHP, a variable is a named memory area. Historically, PHP was a language with weak typing. This means it tried to "guess" what you wanted to do. If you added the string `"10 apples"` and the number `5`, PHP would silently turn the string into the number `10` and output `15`.

**Mental Model:** Why is this bad? Because your brain is forced to constantly hold a table of implicit conversions in working memory (how a string turns into a number, what happens to a boolean value, etc.). This creates unnecessary cognitive load. In the engineering approach, we shift this responsibility to the language. We want an `int` (integer) to always remain an `int`. If we try to combine incompatible types, the program should crash with an error _immediately_, rather than yielding a strange result that surfaces as a bug a week later.

**Naive Example (Junior/Legacy):**

PHP

    <?php
    // No strict typing declaration
    $price = "100"; // This is a string!
    $qty = 2;
    $total = $price * $qty; // PHP implicitly converts string "100" to number 100.
    echo "Total: " . $total;

_Why this is bad:_ You get used to types "floating." In a large project, this leads to errors where a price variable might accidentally receive the string `"100$"` (with a currency symbol), and the math breaks, resulting in an error or `0` (in older versions).

**Engineering Example (Senior):**

PHP

    <?php
    declare(strict_types=1); [cite_start]// The first rule of Fight Club [cite: 37]

    // We explicitly understand that price is an integer (in cents),
    // to avoid floating point issues (float).
    $itemPriceCents = 1500;
    $itemQuantity = 3;

    // We are confident in the types, so the operation is safe.
    $totalCost = $itemPriceCents * $itemQuantity;

    // For output, we use explicit casting or interpolation, understanding what we are doing.
    echo "Order total: {$totalCost} cents";

Enabling `declare(strict_types=1);` guarantees that when passing data to functions (which we will learn later), PHP will not try to coerce types but will throw a `TypeError` exception. This is our first "hostile environment" tool for improving code quality.

---

#### 2\. TASK (Practice)

**Task:** Write a script that calculates the total cost of a shopping cart including a discount.

**Input Data (define them in variables):**

1.  Product name (string).
2.  Product price (integer).
3.  Quantity (integer).
4.  Discount (floating point number, e.g., `0.15` for 15%).

**Algorithm:**

1.  Calculate total without discount.
2.  Calculate discount amount.
3.  Calculate final amount to pay.

**Constraints:**

1.  The first line must be `declare(strict_types=1);`.
2.  Variable names must be in English, in `camelCase` style, and fully describe the essence (no `$p`, `$x`, `$a`).
3.  Use the function `var_dump($variableName);` at the end to show me not just the number, but also the **type** of the final variable.
4.  It is **forbidden** to use strings where numbers are expected (e.g., `$discount = "0.15"`).

### 🟢 Status: Code Accepted

You met the main requirements:

1.  **Pure Function:** Logic is separated from output. The function is now predictable and testable.
2.  **Strict Typing:** `declare(strict_types=1)` is in place, argument and return types are specified.
3.  **Readability:** Variables are named correctly (camelCase).

**Small Optimization Tip (Senior Tip):** Your formula: `$price * $quantity - $price * $quantity * $discount`. From a mathematical and processor perspective, these are two multiplication operations on the same base. It is more elegant (and slightly faster) to factor out the multiplier: `return ($price * $quantity) * (1.0 - $discount);`.

_Mental Model:_ We take the "Total" and multiply it by the "Residual Value" (if the discount is 15%, we pay 85%).
