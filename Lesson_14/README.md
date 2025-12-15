### Lesson #14: Type Evolution (Enums)

We are finished with the architectural core. Now let's look at modern PHP 8.1+ tools that make code expressive.

Recall our `OrderProcessor`. Currently, the order status or notification type is an abstraction. But what if we need to pass the order status? Previously, constants were written: `const STATUS_PAID = 'paid';`. The problem: the function `updateStatus(string $status)` can be passed any string: `"banana"`.

#### 1\. THEORY

**Concept: Enumerations (Enums)** Enum (PHP 8.1) is a special type of object that can accept only one of a limited set of values.

**Mental Model:** It's like a dropdown list in HTML. You cannot select something that isn't in the list. This is **Type Safety** to the max.

**Naive Example (Constants):**

PHP

        class Order {
            const PAID = 1;
            const PENDING = 2;
        }
        function setStatus(int $s) { ... } // Can pass 100500, and PHP won't crash.

**Engineering Example (Backed Enum):**

PHP

        enum OrderStatus: string {
            case Pending = 'pending';
            case Paid = 'paid';
            case Shipped = 'shipped';

            // Enums can have methods!
            public function label(): string {
                return match($this) {
                    self::Pending => 'Pending',
                    self::Paid => 'Paid',
                    self::Shipped => 'Shipped',
                };
            }
        }

        function updateStatus(OrderStatus $status) { ... }

Now passing `"banana"` is physically impossible.

#### 2\. TASK (Practice)

**Task:** Implement Enums into our system.

1.  Create an Enum `Currency` (string) with values `USD`, `EUR`, `RUB`.
2.  Update the `PhysicalProduct` class.

    - Add the property `public Currency $currency` to the constructor.
    - Update product creation in the main code by passing, for example, `Currency::USD` there.

3.  Create an Enum `NotificationType` (string) with values `Email`, `Sms`.

    - Add a method `getIcon(): string` that returns an emoji (📧 for Email, 📱 for Sms).

4.  In the `NotificationServiceInterface` class, change the `notify` method.

    - Now it must accept `string $message` AND `NotificationType $type`.

5.  Update the `EmailNotificationService` implementation (and the `notify` call in `OrderProcessor`) to use this Enum. Output the icon before the message.

**Constraints:**

- Full typing.
- Use `match` inside the Enum (as in the theory example).

Awaiting code with two Enums and updated classes. You can leave the Container (it works), just update the product and service classes.

---

### 🟢 Status: Code Accepted

Excellent work. You implemented Enums and Match, making the code strict and expressive. Your `Container` now works perfectly, and the `#[Singleton]` attribute correctly caches services.

**Hardcoding:** In `OrderProcessor` you wrote: `$this->notificationService->notify(..., Currency::USD, ...);`. You manually passed dollars. If the cart has products in Euros, the processor will still say it is Dollars.

_Solution (for the future):_ The Cart should know its currency, and the processor should take it from the cart: `$cart->getCurrency()`. But we will leave it like this for now.
