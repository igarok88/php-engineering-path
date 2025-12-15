### Lesson #5: First Class Collections

We continue eliminating arrays from the main code.

#### 1\. THEORY

**Concept:** Currently, you have a `$cart` variable which is an `array`. The problem is that an array is an "open structure".

1.  I can accidentally do `$cart[] = 'Not a product';` and break your `array_reduce`.
2.  The logic for working with the cart (adding, calculating the total, clearing) is scattered throughout the code.

**Solution: First Class Collection** We wrap the array of items in a special class `Cart`. This class will "own" the array and guarantee that **only** `CartItem` objects get inside.

**Mental Model:** `Cart` is not just a list. It is an object that manages the state of the purchase. It protects data from external interference. We are moving from procedural code (`array_reduce` on the outside) to Object-Oriented code (method `getTotal` on the inside).

**Naive Example:**

PHP

        $items = [new Item(), new Item()];
        // All logic for working with items lies outside, in the controller or script.

**Engineering Example:**

PHP

        final class Cart {
            // Private property. No one from the outside can mess up the array.
            private array $items = [];

            // We accept ONLY an object of the required type. Type safety at the entrance.
            public function add(CartItem $item): void {
                $this->items[] = $item;
            }

            // All summation logic is encapsulated here.
            public function getTotal(): int {
                // ... logic ...
            }
        }

#### 2\. TASK (Practice)

**Task:** Implement a full object model of the cart.

1.  Keep the `CartItem` class (you can remove `?? 0` and change the return type to `int` if you want perfection).
2.  Create a `Cart` class.
3.  The `Cart` class must have a private property to store items.
4.  Implement the method `add(CartItem $item): void`.
5.  Implement the method `total(): int` (or float). Move the `array_reduce` logic inside this method.
6.  **Client code** (the main script) should look like this:

PHP

            $cart = new Cart();
            $cart->add(new CartItem('Iphone', 1000, 1));
            $cart->add(new CartItem('Case', 50, 2));

            echo "Total: " . $cart->total();

**Constraints:**

- No array operations in the main script. The entire `array_reduce` must be hidden inside the `Cart` class.
- Strict typing everywhere.

Awaiting the implementation of both classes and the usage example.

---

### 🟢 Status: Code Accepted

Excellent work. You have built a robust Object-Oriented structure.

**What distinguishes this code from a beginner's code:**

1.  **Type Safety:** It is impossible to pass a string or a number to the `add` method. _Only_ a `CartItem` will get in. If you try to do `$cart->add('apple')`, the script will crash immediately with a clear error.
2.  **Encapsulation:** The `$items` array is `private`. No one from the outside can do `$cart->items = []` and wipe the cart. Access is only possible via methods.
3.  **Single Source of Truth:** The summation logic now lives in one place (`getTotal`). If the formula changes tomorrow (e.g., tax is added), you change it in one line, not in 50 files across the entire project.
