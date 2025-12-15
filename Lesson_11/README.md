### Lesson #11: Your Own DI Container

Currently, we assemble the entire application manually at the end of the file:

PHP

        $repo = new FileOrderRepository();
        $email = new EmailNotificationService();
        $processor = new OrderProcessor($repo, $email);

In a real project, there are thousands of classes. If `OrderProcessor` needs another dependency (e.g., `Logger`), you will have to find all the places where this processor is created and fix the code. This is "Wiring Hell".

We are moving to automation.

#### 1\. THEORY

**Concept: DI Container (IoC Container)** A container is a "box" that knows **how** to create objects. You don't create objects via `new` in the main code. You ask the container: "Give me an `OrderProcessor`", and the container finds the necessary dependencies itself, creates them, and assembles the finished object.

Understanding how a DI container works distinguishes a Senior Developer.

**Stage 1: Registry on Closures** The simplest container stores "recipes" for creating objects. A recipe is an anonymous function.

**Mental Model:**

- **Registry (`$services`):** This is the restaurant menu.
- **`set()`:** Add a dish to the menu and instructions for the chef (recipe).
- **`get()`:** Order a dish. The chef looks at the recipe, cooks it, and hands it over.

**Engineering Example:**

PHP

        class Container {
            private array $services = [];

            // Save the "recipe" (function), but do NOT run it yet
            public function set(string $id, callable $factory): void {
                $this->services[$id] = $factory;
            }

            public function get(string $id): object {
                if (!isset($this->services[$id])) {
                    throw new Exception("Service $id not found");
                }
                // Run the recipe, passing the container itself ($this) inside,
                // so the service can request other dependencies.
                $factory = $this->services[$id];
                return $factory($this);
            }
        }

#### 2\. TASK (Practice)

**Task:** Write your own `Container` class and use it to assemble the application.

1.  Implement the `Container` class with `set` and `get` methods (as in the example above).
2.  In the client code, **register** the dependencies:

    - Key `OrderRepositoryInterface::class` -> returns `new FileOrderRepository()`.
    - Key `NotificationServiceInterface::class` -> returns `new EmailNotificationService()`.
    - Key `OrderProcessor::class` -> returns `new OrderProcessor(...)`.

      - _Important:_ Inside the processor creation function, you must not write `new FileOrderRepository()`. You must request the dependency from the container: `$container->get(OrderRepositoryInterface::class)`.

3.  **Get** the finished processor via `$container->get(OrderProcessor::class)` and run the order processing.

**Constraints:**

- In the final part of the script, there should not be a single word `new`, except inside the `set` method.
- Use `::class` for key names (e.g., `OrderProcessor::class` instead of the string `"OrderProcessor"`). This will prevent typos.

### What is a DI Container in simple terms?

Imagine you are assembling **Lego**.

**1\. Manual approach (how we did it before):** You sit and search for parts yourself by hand:

- "Okay, I need a 'Motor' part (Repository)." Found it, took it.
- "I need a 'Battery' part (Notification)." Found it, took it.
- "Now I need to assemble the 'Car' (OrderProcessor)." You take the Motor, take the Battery, and insert them into the Car.

This is slow. If the Car is complex, you will get tired of looking for parts.

**2\. Approach with a Container:** A Container is a **Smart Warehouse**. You don't collect parts yourself. You write an **Instruction** for the storekeeper once:

- **Instruction 1:** "If they ask for a 'Motor', give them that gray part."
- **Instruction 2:** "If they ask for a 'Battery', give them the red one."
- **Instruction 3 (Most important):** "If they ask for a 'Car', then go to the warehouse, take the 'Motor' (according to instruction 1), take the 'Battery' (according to instruction 2), and put them together."

### How does this look in code?

We use **Closures** — these are our "Instructions". `fn($container) => ...` is a function that does not execute immediately. It sits and waits until it is called.

Let me give you the ready-made code for the Container itself so you don't struggle with its internal logic (`callable`, arrays, etc.). Your task will be to **write instructions**.

Here is the code for a simple "Box" (you don't need to change it, just copy it):

PHP

        class Container
        {
            private array $recipes = [];

            // Method set: We put the INSTRUCTION (recipe) in the box
            public function set(string $id, callable $recipe): void
            {
                $this->recipes[$id] = $recipe;
            }

            // Method get: We ask for the finished object
            public function get(string $id): object
            {
                // 1. Search for the recipe by name
                if (!isset($this->recipes[$id])) {
                    throw new Exception("No instruction for: $id");
                }

                // 2. Retrieve the instruction
                $recipe = $this->recipes[$id];

                // 3. EXECUTE the instruction.
                // We pass ($this) inside, that is the container itself,
                // so the instruction can ask the container for other parts.
                return $recipe($this);
            }
        }

---

### TASK (Simplified)

I wrote the `Container` class for you. Your task is to "teach" it.

1.  Copy all your previous code (Interfaces, Classes).
2.  Copy my `Container` class (above).
3.  Create an instance: `$container = new Container();`.
4.  **Most important:** Write the instructions (`set`).

Here is an example of how to write an instruction for the Repository:

PHP

        // When Repository is requested -> Return new FileOrderRepository
        $container->set(OrderRepositoryInterface::class, fn() => new FileOrderRepository());

And here is an example of a complex instruction for the Processor (look closely):

PHP

        $container->set(OrderProcessor::class, function(Container $c) {
            // To create a Processor, we need a Repository and Notification.
            // We do not create them via new, we ask THIS SAME container ($c) for them.
            return new OrderProcessor(
                $c->get(OrderRepositoryInterface::class),
                $c->get(NotificationServiceInterface::class)
            );
        });

**Your goal:** Fill in `set` for all three components (`OrderRepositoryInterface`, `NotificationServiceInterface`, `OrderProcessor`) and at the end run the code in one line: `$container->get(OrderProcessor::class)->process($cart);`

---

### 🟢 Status: Code Accepted

Congratulations. You have just overcome the highest barrier to entry into professional development.

**What you just did:**

1.  **Inversion of Control (IoC):** You handed over control of object creation to an external mechanism (the Container).
2.  **Centralized Configuration:** The entire configuration of your application ("which database to use", "which mail to send") is now in one place (`$container->set` blocks).
3.  **Dependency Graph:** You built a dependency graph. The container itself figured out: "Aha, a Processor is needed -> for it, a Repository is needed -> here it is."

In frameworks like Laravel or Symfony, this code (`$container->get(...)`) is hidden deep under the hood (in `public/index.php`), but it works **exactly like this**.
