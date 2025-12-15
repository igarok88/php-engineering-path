### Lesson #18: Event-Driven Architecture

We have reached a critically important topic for a Senior Developer. Currently, your code is **tightly coupled** (High Coupling).

Look at your `OrderProcessor` class:

PHP

        public function __construct(
            public OrderRepositoryInterface $orderRepository,
            public NotificationServiceInterface $notificationService, // <--- TIGHT COUPLING
        ) {}

        public function process(Cart $cart): void
        {
            $this->orderRepository->save($cart);
            // We EXPLICITLY say: "Send notification"
            $this->notificationService->notify(...);
        }

**What is the problem?** Imagine the business comes and says: _"When an order is created, we also need to: 1) send data to analytics, 2) deduct bonus points, 3) send an SMS to the administrator."_

You would have to open `OrderProcessor`, add new dependencies to the constructor, and add method calls. You are violating the **Open/Closed Principle** (open for extension, closed for modification). You are changing a working class just to add a new feature.

#### THEORY: Observer Pattern and Event Dispatcher

We must invert control. `OrderProcessor` should not know _who_ wants to do _what_ after an order. It should simply shout: **"ORDER CREATED!"**, and the other services will react on their own.

**Mental Model:**

- **Event:** This is simply an envelope with data (DTO). For example, `OrderCreatedEvent`. It contains no logic, only data about what happened.
- **Listener:** A class that waits for a specific event and performs an action (e.g., `SendEmailListener`).
- **Dispatcher:** The "Traffic Controller". It stores a list of listeners. When an event is thrown at it, it finds the necessary listeners and launches them.

**Naive Approach (Current):** `OrderProcessor` personally calls everyone: "Hello, mail? Send it. Hello, warehouse? Deduct it."

**Engineering Approach (Event Driven):** `OrderProcessor` throws an event to the Dispatcher and finishes its work. The Dispatcher itself notifies all subscribers. `OrderProcessor` knows nothing about Email or SMS.

---

### TASK (Practice)

We will implement our own simple **Event Dispatcher** (analogous to `symfony/event-dispatcher`).

**Task:**

1.  Create an `OrderCreatedEvent` DTO. Inside, it should hold the `Cart`.
2.  Create an `EventListenerInterface` interface (or use `callable`).
3.  Create an `EventDispatcher` class.

    - It must have a method `subscribe(string $eventName, callable $listener)`.
    - It must have a method `dispatch(object $event)`.

4.  Refactor `EmailNotificationService`. Now it is not a service we call directly, but a listener `EmailNotificationListener`.
5.  **Refactoring:** Change `OrderProcessor`.

    - Remove the dependency on `NotificationServiceInterface`.
    - Add a dependency on `EventDispatcher`.
    - In the `process` method, instead of calling notifications, create an `OrderCreatedEvent` object and pass it to the dispatcher.

6.  In `index.php` (bootstrap), "wire" it all together: create the dispatcher, register the listener in it, and put the dispatcher in the container.

**Constraints:**

- There should not be a single mention of the word `Email` or `Notification` left in `OrderProcessor`. Only `EventDispatcher`.
- Use `strict_types=1`.
- The event must be `readonly`.

**Implementation Hint for Dispatcher:** The dispatcher usually stores an array:

PHP

        private array $listeners = [
            'App\Events\OrderCreatedEvent' => [
                [EmailListener::class, 'handle'],
                [LogListener::class, 'handle'],
            ]
        ];

When `dispatch($event)` is called, the dispatcher takes the event class (`get_class($event)`), looks for it in the array, and runs all functions from the list.

Awaiting your code!

---

🟢 **Corrected Implementation (Solution)**

### Debriefing:

1.  **Loose Coupling:** Look at `OrderProcessor`. Previously, it depended on `NotificationService`. If we wanted to add SMS, we would have had to change `OrderProcessor`. Now `OrderProcessor` depends only on `OrderCreatedEvent`. To add SMS, we simply add `$dispatcher->subscribe(..., new SmsListener())` in the container configuration. The processor code **does not change**. This is an architectural victory.
2.  **`callable` Typing:** In PHP, the array `[$object, 'methodName']` is of type `callable`. When the dispatcher does `$listener($event)`, PHP calls the `methodName` method on the `$object` object.
3.  **Synchronicity:** It is important to understand the PHP mental model. `dispatch()` executes **synchronously**. The code in the controller will not proceed until all listeners have finished working (emails are not sent). In Lesson #20, we will learn how to move this to asynchronous queues (RabbitMQ/Redis) so the user doesn't wait for the email to send.

## Step-by-Step Code Analysis (How it works)

Your example demonstrates the classic **EDA** approach within a single application using the **Observer** or **Publisher-Subscriber** pattern.

### Step 1: Event Definition (Event DTO)

- **Code:** `readonly class OrderCreatedEvent`
- **Meaning:** An **Event** is an object that contains the **fact** that something happened (e.g., "Order was created").
- **Variable Values:** The `$event` object will contain the `$cart` field, which stores information about the order (items, total amount).

  > **Example:** `$event->cart` stores a `Cart` object with a total of `1100` USD.

---

### Step 2: Creating Listeners

- **Code:** `EmailNotificationListener`, `LogListener`
- **Meaning:** **Listeners (Consumers/Subscribers)** are classes or functions that are **interested** in a specific event and perform _side_ effects in response to it. They know about the event (`OrderCreatedEvent`) but **do not know** about its source (`OrderProcessor`).
- **Methods:** `onOrderCreated` are the handler methods that will be called when the event is received.

---

### Step 3: Dispatcher (Core)

- **Code:** `class EventDispatcher`
- **Meaning:** The **Dispatcher (Event Bus/Broker)** is the heart of the architecture. It is responsible for:

  1.  **Registering** listeners for specific events (`subscribe`).
  2.  **Delivering** the event to all subscribed listeners (`dispatch`).

- **Storing Values (`$listeners` Array):** At the moment of "Wiring" (Step 5), the Dispatcher accumulates references to the listeners.

  PHP

        /** @var array<string, array<callable>> */
        private array $listeners = [
            // Key is the full class name of the event
            'OrderCreatedEvent' => [
                0 => [EmailNotificationListener, 'onOrderCreated'], // reference to first listener
                1 => [LogListener, 'onOrderCreated'], // reference to second listener
            ],
            // ... other events
        ];

---

### Step 4: Business Logic (OrderProcessor)

- **Code:** `readonly class OrderProcessor`
- **Meaning:** This is the **Event Producer/Publisher**. Its only task is to execute the main logic (save the order) and **publish the fact** regarding the execution of this logic.
- **`process` Flow:**

  1.  `$this->orderRepository->save($cart);` — **Main logic** (saving the order).
  2.  `$event = new OrderCreatedEvent($cart);` — Creating the fact object.
  3.  `$this->eventDispatcher->dispatch($event);` — Passing the event to the Dispatcher. At this moment, `OrderProcessor` **finishes its work**; it does not wait for or care about what happens next.

---

### Step 5: Wiring

- **Code:** The `WIRING` section using `Container`.
- **Meaning:** **Configuration** happens here — we tell the Dispatcher which listeners should respond to which events.
- **Process:**

  1.  Instance of `$dispatcher` is created.
  2.  Instances of `$emailListener` and `$logListener` are created.
  3.  Subscription methods are called:

      - `$dispatcher->subscribe(OrderCreatedEvent::class, [$emailListener, 'onOrderCreated']);`
      - `$dispatcher->subscribe(OrderCreatedEvent::class, [$logListener, 'onOrderCreated']);`

  4.  As described in Step 3, the Dispatcher fills its internal `$listeners` array.

---

## 🎬 Execution (Run)

When `$kernel->handle($request);` is called, the following happens (simplified, focusing on order logic):

1.  Somewhere inside `Kernel` and `Request`, it is determined that `OrderProcessor::process` needs to be called.
2.  The `OrderProcessor` object (retrieved from the container) calls the `process` method with the `$cart` object.
3.  `OrderProcessor` saves the order.
4.  `OrderProcessor` creates `$event` and calls `$this->eventDispatcher->dispatch($event);`.
5.  **Inside `EventDispatcher::dispatch`:**

    - Gets `$eventName` = `"OrderCreatedEvent"`.
    - Finds the array of listeners for this event in `$this->listeners`.
    - **Loop:**

      - Calls `$listener($event)` for the first listener: `[$emailListener, 'onOrderCreated']($event)`.
      - Output: `📧 [EmailListener] Sending email to client. Order Total: 1100 <br>`
      - Calls `$listener($event)` for the second listener: `[$logListener, 'onOrderCreated']($event)`.
      - Output: `📝 [LogListener] Log entry: new order created. <br>`

---

## 🌟 Advantages of EDA

Advantage

Explanation in simple language

**Decoupling**

Components do not know about each other; they communicate via events. The source (Processor) does not depend on subscribers (Listeners).

**Scalability**

It is easy to add a new listener (e.g., for statistics) without changing existing logic. You simply add a new class and subscribe it to the event.

**Asynchrony**

Events are often processed asynchronously (in the background, using queues like RabbitMQ or Kafka). This allows for a quick response to the user (order process completed), while performing all "side" work later without slowing down the main system.

Экспортировать в Таблицы

### Next Step:

Your code has become architecturally cleaner, but it started "eating" memory. Imagine we are processing a CSV file with 1 million rows. If we create an array of `Product` objects in memory (as we do now in `Cart`), the server will crash with `Fatal Error: Allowed memory size exhausted`.
