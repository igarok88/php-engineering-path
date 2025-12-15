### Lesson #7: Polymorphism and Interfaces

We are approaching one of the most important topics that distinguishes a Senior Developer. Your code currently has tight coupling: the `Cart` class knows about the specific `CartItem` class.

**Situation:** The business has come with a new task: "We want to sell not only physical goods (iPhones) but also Services (Phone setup, Extended warranty)." A `Service` does not have a quantity (`qty`). It is sold once.

If you start adding `if ($type === 'service')` to the `CartItem` class, you will violate the **Open/Closed Principle** (code should be open for extension, but closed for modification).

#### 1\. THEORY

**Concept: Contract (Interface)** The `Cart` actually doesn't care what was put into it: an iPhone, a Service, or a Subscription. The Cart cares about only one thing: **"Can I get the price from this thing?"**.

We create an Interface — a contract that obliges classes to have specific methods.

**Mental Model:** An interface is a power plug. A TV, a vacuum cleaner, and a charger look different, but they all have the same plug. The socket (Cart) doesn't care what is plugged into it, as long as the plug fits.

**Naive Example (If/Else hell):**

PHP

        // Bad: The Cart must know about all product types
        foreach ($items as $item) {
            if ($item instanceof Product) {
                $total += $item->price * $item->qty;
            } elseif ($item instanceof Service) {
                $total += $item->fee;
            }
        }

**Engineering Example (Polymorphism):** "Huge if/else structures must be replaced with polymorphic structures."

PHP

        interface Payable {
            public function getCost(): int;
            public function getName(): string;
        }

        // Now the Cart accepts not a specific class, but "something that is payable"
        public function add(Payable $item): void { ... }

#### 2\. TASK (Practice)

**Task:** Implement a polymorphic cart system.

1.  Create an interface `PayableInterface`.

    - Methods: `getCost(): int` and `getName(): string`.

2.  Rename your `CartItem` class to `PhysicalProduct`.

    - It must implement `PayableInterface`.
    - The price calculation logic remains the same (price \* qty).

3.  Create a new class `DigitalService`.

    - It must also implement `PayableInterface`.
    - Constructor has only `$name` and `$price`. No quantity (a service is always singular).
    - The `getCost` method simply returns the price.

4.  Update the `Cart` class.

    - The `add` method must accept the `PayableInterface` type.
    - Ensure that `getTotal` continues to work (it should not care what object is inside; it simply calls the interface method).

5.  In the main code, add one `PhysicalProduct` and one `DigitalService` to the cart.

**Constraints:**

- Use `implements`.
- Typing in the `add` method must be by the interface.

---

### 🟢 Status: Code Accepted

This is excellent code.

**Why this is Senior Level:**

1.  **Loose Coupling:** The `Cart` class no longer knows about the existence of `PhysicalProduct` or `DigitalService`. It only knows about `PayableInterface`.
2.  **Open/Closed Principle:** If your boss says tomorrow: "Add Subscriptions," you **will not have to** change the `Cart` class code. You will simply create a `Subscription implements PayableInterface` class. The Cart will accept it like family. This is the principle of "Open for extension, closed for modification".
3.  **Uniformity:** You removed `array_reduce` and complex `if` statements, replacing them with a polymorphic call `$product->getCost()`.
