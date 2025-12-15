### Lesson #8: Dependency Injection

We are moving on to application architecture. Right now, we have `Cart` (data) and products. But someone needs to _process_ the order: save it to the database, send a receipt via email.

Beginners write this logic directly in the controller or, even worse, inside the `Cart` class. We will create a **Service Layer**.

#### 1\. THEORY

**Concept: Dependency Injection (DI)** Classes should not create their own dependencies (via `new`); they should request them in the constructor. This is called "Inversion of Control".

**Mental Model:** Imagine you are building a house.

- _Naive approach:_ You (the Foreman) take clay and fire bricks right on the construction site (`new BrickFactory()`).
- _DI approach:_ You tell the supplier: "I need bricks." You get finished bricks delivered. You don't care which factory they come from, as long as they are bricks.

**Naive Example (Hard dependency / Tight coupling):**

PHP

        class OrderProcessor {
            public function process(Cart $cart) {
                // ERROR: Hard binding to a specific saving method
                // If we want to save to a file or cloud — we have to rewrite this class.
                $db = new MySQLDatabase();
                $db->save($cart);
            }
        }

**Engineering Example (Dependency Injection):** We declare an interface `OrderRepositoryInterface` (order storage). And we ask for it in the constructor.

PHP

        class OrderProcessor {
            // We ask for "any repository", not a specific database.
            public function __construct(
                private OrderRepositoryInterface $repository
            ) {}

            public function process(Cart $cart) {
                // We simply call the contract method.
                $this->repository->save($cart);
            }
        }

"Dependency Injection (DI) is the foundation of testable code."

#### 2\. TASK (Practice)

**Task:** Implement a simulation of saving an order using DI.

1.  Create an interface `OrderRepositoryInterface`.

    - Method: `save(Cart $cart): void`.

2.  Create a class `FileOrderRepository` implementing this interface.

    - In the `save` method, it shouldn't actually write to a file (to keep it simple), just let it do `echo "Order saved to file. Total: " . $cart->getTotal();`.

3.  Create a class `OrderProcessor` (Service).

    - **In the constructor**, it must accept `OrderRepositoryInterface`.
    - The method `process(Cart $cart): void` must call the save method on the repository.

4.  **Client Code (Wiring):**

    - Create a cart with items (like in the last lesson).
    - Create an instance of the repository (`new FileOrderRepository`).
    - Create the processor, passing the repository to it (`new OrderProcessor($repo)`).
    - Call `$processor->process($cart)`.

**Constraints:**

- The `OrderProcessor` class **must not** use the word `new` to create the repository.
- Typing everywhere (including the constructor).

---

### 🟢 Status: Code Accepted

Congratulations. Now your architecture complies with **Stateless Service** principles.

- **Stateless:** The `FileOrderRepository` is now clean. It doesn't store the cart inside itself. It works like a "function": received data -> saved -> forgot.
- **Reusability:** You created one instance of `$repo` and can use it to save a thousand different carts. This saves memory and aligns with the Singleton pattern (at the usage logic level).
- **Dependency Injection:** The processor (`OrderProcessor`) receives the dependency from the outside and doesn't know exactly which implementation it is.
