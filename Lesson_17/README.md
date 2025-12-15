### Lesson #17: Framework Architecture and Request Lifecycle

We have written excellent classes. But in reality, they don't hang in a vacuum. They live inside a framework (Laravel/Symfony). Most developers think only within the scope of the Controller. But an expert understands **the entire request path**. This allows fixing errors that happen _before_ the code reaches the controller (e.g., in Middleware).

#### 1\. THEORY

**Concept: The Pipeline** Any modern PHP framework is simply a converter: it turns a **Request** into a **Response**. `Request -> [ Kernel -> Router -> Middleware -> Controller ] -> Response`

**Request Lifecycle Stages:**

1.  **Entry Point (`index.php`):** All requests fly into one file. The Composer autoloader is connected here.
2.  **Kernel:** The DI Container loads here. The application "wakes up".
3.  **Router:** Looks at the URL (`/order/create`) and decides which Controller to call.
4.  **Middleware:** Checks along the way (Is logged in? Are there permissions? Is it JSON?).
5.  **Controller:** Your code. It returns data.
6.  **Response:** The framework sends HTTP headers and HTML/JSON to the browser.

**Mental Model:** A framework is just a very sophisticated function `handle(Request): Response`.

#### 2\. TASK (Practice: Micro-Framework)

We will simulate the work of the framework kernel using everything we learned earlier (Container, DI).

**Task:** Write a "Kernel" that accepts a request and launches the necessary controller.

1.  **`Request` Class:** A simple DTO.

    - Property `public string $path` (e.g., `/home`).

2.  **`HomeController` Class:**

    - Add the `OrderProcessor` dependency to its constructor (we will check how our Kernel finds dependencies itself!).
    - Method `index(): string` — returns "Hello World via Framework".

3.  **`Router` Class:**

    - Property `$routes = ['/home' => HomeController::class]`.
    - Method `resolve(string $path): string` — returns the controller class name or throws a 404 error.

4.  **`Kernel` Class (Most Important):**

    - Accepts our `Container` in the constructor.
    - Method `handle(Request $request): void`.
    - **`handle` Algorithm:**

      1.  Ask the Router: "Which class is responsible for the path `$request->path`?"
      2.  Get the class name (e.g., `HomeController`).
      3.  **Magic:** Use `$this->container->get(HomeController::class)` to create an instance of the controller with all dependencies (Processor, Repository, etc.).
      4.  Call the `index()` method on the controller and output the result.

5.  **Client Code (`index.php`):**

    - Assemble the Container (as in past lessons).
    - Create the Router, Kernel.
    - Create a Request with path `/home`.
    - Run `$kernel->handle($request)`.

**Goal:** You must see how the `Kernel` automatically assembles a complex dependency tree (`Controller -> Processor -> Repository`) simply by knowing the URL.

Awaiting the implementation of your micro-framework. (You don't need to duplicate the code for strategies and old classes; we assume they exist).
