### Lesson #6: Invariants and Validation (Guarding the State)

We have built the structure, but it still trusts incoming data. What happens if I write: `new CartItem('', -1000, -5)`? The program will create a product with a negative price and an empty name. This is an **invalid state**.

#### 1\. THEORY

**Concept: Invariants** An invariant is a condition that must always be true for an object.

- Price cannot be negative.
- Quantity must be greater than zero.
- Product name cannot be an empty string.

According to the methodology, "a class should not exist in an invalid state". All properties must be correct immediately after initialization.

**Fail Fast** Instead of checking data before saving to the database (as beginners do), we check it in the **constructor**. If the data is bad, we throw an Exception immediately. This stops the propagation of "dirty" data through the system.

**Example:**

PHP

        public function __construct(string $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // We do not return false, we break execution.
                // This is the "Hostile Environment" we spoke about.
                throw new InvalidArgumentException("Invalid email format");
            }
            $this->email = $email;
        }

#### 2\. TASK (Practice)

**Task:** Protect the `CartItem` class.

1.  In the `CartItem` constructor, add checks (Asserts/Guards):

    - If `$price` is less than `0`, throw an `InvalidArgumentException`.
    - If `$qty` is less than `1` (there must be at least one item), throw an `InvalidArgumentException`.
    - If `$name` is an empty string, throw an `InvalidArgumentException`.

2.  In the main code, wrap the product creation in a `try-catch` block to catch the error and output a nice message.
3.  Try to specifically create a "bad" product to demonstrate how the protection triggers.

**Constraints:**

- Use the standard `InvalidArgumentException` class.
- The error text must be understandable (e.g., "Price cannot be negative").

---

### Lesson #6.1: Guard Clauses Pattern

We will fix your code using the **Fail Fast** principle. The `CartItem` object must guarantee: "If I exist, then I am valid."

#### THEORY: Guard Clauses

Instead of nested `if-else` blocks, which create a "staircase," we use flat checks at the beginning of the method. If the check fails — exit immediately (throw).

**Naive (your version with else):**

PHP

        if ($price >= 0) {
            // ok
        } else {
            throw ...
        }

**Engineering (Guard Clause):**

PHP

        if ($price < 0) {
            throw new InvalidArgumentException("Price cannot be negative");
        }
        // The code proceeds linearly, we know the price is ok.

---

### 🟢 Status: Code Accepted

This is code you wouldn't be ashamed of in a Code Review.

1.  **Guard Clauses:** You correctly protected the invariants. Now the `CartItem` object physically cannot exist in a "broken" state.
2.  **Separation of Concerns:** The class handles validation, and the external code (client) decides what to do with the error (catch, log, show to user). This is the correct separation of responsibility.
