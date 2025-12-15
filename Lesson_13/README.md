### Lesson #13: PHP 8 Attributes

We have wrapped up the topic of basic architecture. Now we move on to **Modern PHP (8.0+)**. There is a problem in your code: every time you call `$container->get()`, it creates a **new** object.

In web development, services (Repositories, Mailers) should usually be **Singletons** (exist as a single instance throughout the request) to avoid opening 100 connections to the DB.

We could configure this manually in `set`, but that is "naive practice". PHP 8 introduced **Attributes** (annotations that the language understands). We will mark our classes with a special attribute `#[Singleton]` and teach the container to understand it.

#### 1\. THEORY

**Concept: Metadata** Attributes are "stickers" on classes. By themselves, they do nothing. But using Reflection, we can read these stickers and change the program's behavior.

**Syntax:**

PHP

        #[Attribute]
        class Singleton {}

        #[Singleton] // <-- Sticker
        class FileOrderRepository { ... }

**Mental Model:** Before creating an object, the container looks: "Is there a `Singleton` sticker on this class? If yes, I will save the created object in the cache and give the same one next time."

#### 2\. TASK (Practice)

**Task:** Implement support for the `#[Singleton]` attribute in your container.

1.  Create a simple attribute class:

    PHP

            #[Attribute]
            class Singleton {}

2.  Mark the `FileOrderRepository` and `EmailNotificationService` classes with the `#[Singleton]` attribute.
3.  Modify the `Container` class:

    - Add an array `private array $instances = [];` (cache of ready objects).
    - In the `get` method:

      - First check if a ready object already exists in `$this->instances[$id]`. If it exists, return it.
      - Before returning the created object (both in the manual creation block and in the Reflection block), check via Reflection if the class has the `Singleton` attribute.
      - The attribute check looks like this: `count($reflector->getAttributes(Singleton::class)) > 0`.
      - If the attribute exists, save the object in `$this->instances[$id]`.

4.  **Verification:**

    - Call `$repo1 = $container->get(OrderRepositoryInterface::class);`
    - Call `$repo2 = $container->get(OrderRepositoryInterface::class);`
    - Do `var_dump($repo1 === $repo2);`. It should be `bool(true)`.

**Constraints:**

- Do not use the Singleton pattern inside the classes themselves (via `static instance`). Use only the container.

Awaiting the updated container with attribute support.

---

### (The Correct Algorithm)

We must change the strategy. We cannot know if it is a Singleton or not until we create the object (especially in the case of factories).

**The `get` method algorithm should be as follows:**

1.  **Cache Check:** If `$id` is in `$instances`, return the object immediately.
2.  **Creation:**

    - If there is a manual setting (`$services`), execute the factory and get `$object`.
    - If not, create via Reflection (Autowiring) and get `$object`.

3.  **Inspection:** Now that we have `$object` (no matter how it was created), we check its real class.
4.  **Saving:** If the real class of the object has the `#[Singleton]` attribute, save it to `$instances`.
5.  **Return:** Return `$object`.

### TASK (Correction)

Rewrite the `Container` class.

1.  Rewrite the `get` method according to the algorithm above.

    - _Hint:_ To check the attribute on a ready object `$object`, use `new ReflectionObject($object)`. This works more reliably than `ReflectionClass`, as it checks the exact entity we created.

**Example `get` structure:**

PHP

        public function get(string $id): object
        {
            // 1. Cache
            if (isset($this->instances[$id])) {
                return $this->instances[$id];
            }

            // 2. Creation (define variable $object first)
            if (isset($this->services[$id])) {
                // ... create from factory ...
                $object = ...;
            } else {
                // ... autowiring logic ...
                $object = ...;
            }

            // 3. Check Singleton attribute on the ready object
            $reflector = new ReflectionObject($object);
            if (count($reflector->getAttributes(Singleton::class)) > 0) {
                $this->instances[$id] = $object;
            }

            return $object;
        }

Awaiting the corrected version. Ensure that `var_dump` at the end outputs `true`.

---

🟢 **Status: Code Accepted**

You just wrote a **fully functional DI Container with support for Autowiring and Attributes**. This is Middle+ level.
