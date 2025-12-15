### Lesson #12: The Magic of Reflection API (Autowiring)

You might have noticed one problem. We wrote an instruction (`set`) for `OrderProcessor`. But if we look at the `OrderProcessor` constructor, it is **already written** there what it needs:

PHP

        public function __construct(
            public OrderRepositoryInterface $orderRepository,
            public NotificationServiceInterface $notificationService,
        ) {}

We are duplicating code. We write types in the constructor, and then manually write in the container: "Take this type." If we have 1000 classes, the container configuration file will become huge.

**Solution: Autowiring.** We will teach our Container to be a "psychic". It must look at the class, read its constructor, understand which dependencies are needed, find them within itself, and create the object on its own. Without a manual `set` instruction.

#### 1\. THEORY

**Concept: Reflection API** PHP has built-in classes (Mirrors) that allow a script to study itself. `ReflectionClass` can say: "This class has a constructor. It has 2 parameters. The first has the type `OrderRepositoryInterface`".

**Mental Model:**

- **Manual Container:** The chef cooks according to a paper recipe. No paper — no cooking.
- **Autowiring:** The chef looks at a photo of the finished dish (class code), sees the ingredients, and understands on their own what needs to be mixed.

**Autowiring Algorithm:**

1.  I am asked to give the class `OrderProcessor`.
2.  I look: is there a manual recipe in the `$services` array? No.
3.  Then I create a "Mirror" (`new ReflectionClass('OrderProcessor')`).
4.  I ask the mirror: "Is there a constructor?".
5.  If there is, I iterate through all constructor parameters.
6.  I see that `OrderRepositoryInterface` is needed. I call myself: `$this->get(OrderRepositoryInterface)`.
7.  When I have collected all ingredients, I create the object (`newInstanceArgs`).

#### 2\. TASK (Practice: Senior Level)

This is a difficult task, but it is worth it. We are modifying the `get` method in your `Container` class.

**Task:** Implement simplified Autowiring.

1.  Remove the manual configuration for `OrderProcessor` (`$container->set(OrderProcessor::class, ...)`) from the client code. **Remove it completely.**
2.  Leave the settings for Interfaces (The Container cannot guess that `Interface` means `FileRepository`; a manual binding is needed here).
3.  Modify the `get(string $id)` method in the `Container` class:

    - If `$id` exists in `$services` — return the result (old logic).
    - If not — try to create via **Reflection**:

      1.  Create `$reflector = new ReflectionClass($id);`.
      2.  Get the constructor: `$constructor = $reflector->getConstructor();`.
      3.  If there is no constructor (`null`) — simply return `new $id()`.
      4.  If there is a constructor, get parameters: `$parameters = $constructor->getParameters();`.
      5.  Run through parameters, get their type (class), and recursively call `$this->get(TypeName)`. Collect dependencies into a `$dependencies` array.
      6.  Return `$reflector->newInstanceArgs($dependencies);`.

**Reflection Hint:**

PHP

        // Getting the dependency type name
        $type = $parameter->getType();
        $dependencyClassName = $type->getName();
        $dependencies[] = $this->get($dependencyClassName);

**Expected Result:** You removed the configuration for `OrderProcessor`, but the code `$container->get(OrderProcessor::class)` continues to work because the container figured out how to assemble it itself.

Awaiting the updated `Container` class and client code.

---

### 🟢 Status: Code Accepted

You just wrote what is called `Illuminate\Container\Container` inside Laravel, and the `DependencyInjection` component inside Symfony. This is "Magic" that scares beginners, but now you know it is simply **Recursion + Reflection**.
