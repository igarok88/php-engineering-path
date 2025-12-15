### Lesson #3: Type Documentation (PHPDoc) and Functional Style

#### 1\. THEORY

**Part A: PHPDoc and Pseudo-types** You specified the argument type `array $cart`. But for PHPStan (our digital mentor), `array` is too vague. It is an array of what? Strings? Numbers? Other arrays? PHP does not have generics yet (like `List<Product>` in Java), but we use the PHPDoc standard to explain the array structure to the IDE and analyzers.

**How it is written:**

PHP

        /**
         * @param array<int, array{name: string, price: int, qty: int}> $cart
         */
        function calculate(array $cart): float { ... }

This tells the analyzer: "The array is indexed by integers, and inside lie sub-arrays with specific keys and types." This is _deliberate practice_ — you are explicitly describing the shape of the data.

- **`@param` (Parameter)** — is an **instruction for the function**. It is a description of what the function _expects as input_. Therefore, this tag is always written only before the function or method declaration.
- **`@var` (Variable)** — is a **label for a variable**. It is a description of what _lies inside_ a specific variable or class property right now.

**Part B: array_reduce and Arrow Functions** Experienced developers avoid writing `foreach` loops for simple transformation operations. We use high-order functions. They reduce "noise" in the code.

**Naive Example (loop):**

PHP

        $sum = 0;
        foreach ($arr as $item) {
            $sum += $item;
        }

**Engineering Example (array_reduce):** We "fold" (reduce) the array into a single value.

PHP

        // fn($carry, $item) — is a short arrow function (PHP 7.4+)
        // $carry — is our accumulator (the same as $total)
        // $item — current element
        // 0 — initial accumulator value

        $sum = array_reduce($arr, fn(int $carry, int $item) => $carry + $item, 0);

It reads like this: "Reduce the array by adding each element to the current sum, starting from zero."

#### 2\. TASK (Refactoring)

**Task:** Rewrite the `calculateCartTotal` function from the previous lesson.

**Constraints:**

1.  **Complete ban on loops** (`foreach`, `for`, `while`). Use only `array_reduce`.
2.  Use the short arrow function syntax `fn(...) => ...`.
3.  Add **PHPDoc** before the function describing the `$cart` array structure (format `array<int, array{...}>`).
4.  Inside `array_reduce`, you won't be able to use `if (isset(...))` as easily (the arrow function must return a value in a single line).

    - _Hint:_ Use the null coalescing operator `??` (Null Coalescing Operator). For example: `($item['price'] ?? 0)`. This is the "default value" mental model.

Awaiting updated, compact, and strictly typed code.

---

### 🟢 Status: Code Accepted

You handled the functional style excellently.

1.  **Conciseness:** The code became short and expressive.
2.  **Safety:** The construction `($product['price'] ?? 0)` guarantees that the math won't break even on broken data. This is exactly what is needed for a stable system.
3.  **PHPDoc:** You described the structure. In an IDE, this will allow you to see key hints when hovering over `$product`.
