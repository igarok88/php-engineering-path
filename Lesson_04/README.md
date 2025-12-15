### Lesson #4: From Arrays to Objects (DTO)

We are approaching a critical moment. Remember the quote from the methodology:

> "Deliberate practice requires that the developer always knows the data type. If the type is unknown, it must be explicitly marked as `mixed`, which forces one to reflect on the nature of the data."

#### 1\. THEORY

**The Array Problem ("Primitive Obsession"):** In the `$cart` array, we have keys `'price'`, `'qty'`. What if you make a typo and write `'qyt'`? PHP won't flag the error until Runtime. A static analyzer might also miss this if the array is complex. Arrays are "unstructured data".

**The Solution: Data Transfer Object (DTO)** We create a class whose sole purpose is to transfer data. In modern PHP (8.1+), we use **Constructor Property Promotion** and **Readonly Properties**.

**Naive Example (PHP 7.x style):**

PHP

        class Product {
            public string $name;
            public int $price;
            // Need to write a constructor, assign $this->name = $name...
            // A lot of extra code (boilerplate).
        }

**Engineering Example (PHP 8.2+):**

PHP

        // readonly means properties cannot be changed after creation.
        // This guarantees Immutability — a crucial concept of reliable systems.
        readonly class CartItem
        {
            public function __construct(
                public string $name,
                public int $price,
                public int $qty
            ) {}

            // The method lives next to the data. This is encapsulation.
            public function getTotal(): int
            {
                return $this->price * $this->qty;
            }
        }

Now, if you try to create `new CartItem('iPhone', 'expensive', 1)`, the IDE and PHP will highlight the error instantly. You cannot create an "invalid" object.

#### 2\. TASK (Practice)

**Task:** Rewrite our cart script, completely abandoning associative arrays in favor of objects.

1.  Create a `CartItem` class. Use **Constructor Promotion** (declaring properties directly in constructor arguments).
2.  Make the class or properties `readonly`.
3.  Add a `getCost()` method to the class that returns the cost of this item (price \* qty).
4.  In the main code, create an array consisting of `CartItem` **objects** (instead of arrays `['name' => ...]`).
5.  Calculate the total sum by iterating through this array of objects.

**Constraints:**

- Type everything.
- No arrays like `['key' => 'value']`. Only `new CartItem(...)`.

Awaiting code with the class.

---

### 🟢 Status: Code Accepted (Assignment accepted with remarks)

You have made a qualitative leap. The transition from arrays to objects is the moment you stop being a "script kiddie" and become an engineer.

**Why your code is better now:**

1.  **Contract Guarantee:** Now it's impossible to create a product without a price or with a negative quantity (if checks are added to the constructor).
2.  **Encapsulation:** The cost calculation logic (`$price * $qty`) now lives where the data is — inside the `CartItem` class. This reduces cognitive load when reading the main code.
3.  **Ban on "Leaky" States:** Thanks to `readonly` and the constructor, the object is always valid immediately after creation.

**"Strict Mentor" Remarks (Code Review):**

1.  **Logical Redundancy (Dead Code):** You wrote: `$product->getCost() ?? 0`. Look at the method signature: `public function getCost(): float`. The method is **guaranteed** to return a number. It will never return `null`. The `?? 0` operator will never trigger. _Mental Model:_ If you typed the return, **trust the types**. Extra checks create visual noise and make one think: "Can there be null here?".
2.  **Data Types (Money Pattern):** You accept `int $price` and `int $qty`, but return `float`. Multiplying integers always yields an integer (until memory overflows). For money, it is better to stick to `int` until the very last moment to avoid floating-point issues (`0.1 + 0.2 !== 0.3`). But for now, we will leave `float` as an acceptable simplification.
