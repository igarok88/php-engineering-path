### Lesson #10: Multiple Dependencies and Composition

In real systems, classes rarely depend on just one thing. Imagine the business logic: "When an order is processed, it needs to be saved to the DB **AND** an email must be sent to the client."

Beginners often make the mistake of adding email sending logic directly into the repository's `save` method. This is a violation of the **Single Responsibility Principle (SRP)**. The repository should only store data. Sending emails is the postman's job.

#### 1\. THEORY

**Concept: Service Composition** Our `OrderProcessor` is a conductor. It needs different musicians (dependencies) to play the symphony. We simply add another argument to the constructor.

**Mental Model:** The `OrderProcessor` does not know _how_ to save (the Repository knows this). The `OrderProcessor` does not know _how_ to send emails (the NotificationService knows this). It only knows the **order of actions**: first save, then notify.

**Engineering Example:**

PHP

        class OrderProcessor {
            public function __construct(
                private OrderRepositoryInterface $repository,
                private NotificationInterface $mailer // Second dependency
            ) {}

            public function process(Cart $cart): void {
                $this->repository->save($cart); // Step 1
                $this->mailer->send($cart);     // Step 2
            }
        }

In more complex systems, this is done via Events (Event Dispatcher), but for starters, you need to master direct composition.

#### 2\. TASK (Practice)

**Task:** Expand the `OrderProcessor` functionality by adding notification sending.

1.  Create an interface `NotificationServiceInterface`.

    - Method: `notify(string $message): void`.

2.  Create an implementation `EmailNotificationService`.

    - In the `notify` method, simply output: `echo "Email sent: $message";`.

3.  Change the `OrderProcessor` class.

    - Add a second dependency to the constructor: `NotificationServiceInterface`.
    - In the `process` method:

      1.  First call `save` on the repository.
      2.  Then form a message (e.g., "Order for amount X created") and call `notify`.

4.  **Client Code:**

    - Create `FileOrderRepository` (let's go back to it instead of the mock).
    - Create `EmailNotificationService`.
    - Pass **both** services to the `OrderProcessor`.
    - Run it.

**Constraints:**

- Strict typing.
- No `new EmailService()` inside the processor. Only via the constructor (DI).

Awaiting updated code with two dependencies.

---

### 🟢 Status: Code Accepted

Excellent.

1.  **Composition:** Your `OrderProcessor` is now not just an executor, but a **Coordinator**. It manages the process by delegating specific tasks (saving, notifying) to specialized services.
2.  **Flexibility:** We can easily replace `EmailNotificationService` with `SmsNotificationService` simply by passing a different object to the constructor. The processor code will not change.

We have implemented an architecture where business logic (Process) is separated from infrastructure (File, Email).
