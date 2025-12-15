### Lesson #15: Advanced Type System (Intersection Types)

We are moving forward. Right now, your `save` method in the repository simply outputs text. But what if we need to generate a PDF document?

In PHP 8.1, **Intersection Types** were introduced. This is a way to say: "I need an object that is **BOTH** this **AND** that at the same time." Syntax: `TypeA&TypeB`.

#### 1\. THEORY

**Problem:** Imagine we want to print a receipt. For a receipt, we need to know:

1.  The price (`PayableInterface` knows how to do this).
2.  The name for printing (let's say we need a special format for PDF).

We could create a `PrintableAndPayableInterface`, but that is a dead end (creating thousands of interfaces for every case).

**Solution:** We use `&` (Ampersand).

PHP

        function printInvoice(PayableInterface&ExportableInterface $item) {
            // Inside this function, we are guaranteed to be able to call methods of BOTH interfaces.
            echo $item->getCost(); // from Payable
            echo $item->toPdf();   // from Exportable
        }

#### 2\. TASK (Practice)

**Task:** Implement an invoice generator using intersection types.

1.  Create a new interface `ExportableInterface`.

    - Method: `toPdf(): string`.

2.  Update the `PhysicalProduct` class.

    - Add `implements PayableInterface, ExportableInterface`.
    - Implement the `toPdf` method: return a string like `"📄 PDF Invoice for [Name] - [Price]"`.

3.  **DO NOT touch** the `DigitalService` class. It must remain only `PayableInterface`. (Our services are without PDF).
4.  Create a new class `InvoiceGenerator` (a simple class, not a singleton, not a service).

    - Method: `generate(PayableInterface&ExportableInterface $item): void`.
    - Inside the method, call both `getCost()` and `toPdf()`, and output them to the screen.

5.  **Client Code (Type Check):**

    - Create an `InvoiceGenerator`.
    - Iterate through the items in the cart (you will have to make a `getItems()` getter in `Cart` or just create items manually for the test).
    - In the main script, get the generator.
    - Try to feed a `PhysicalProduct` to the generator — it should work.
    - Try to feed a `DigitalService` to the generator — **the script must crash with a fatal type error** (or the IDE will underline it in red). This is the goal of the lesson — to see type protection.

**Constraints:**

- Use the `&` syntax in the argument of the `generate` method.

Awaiting class code and demonstration of operation (or error).

---

### 🟢 Status: Code Accepted

You handled **Intersection Types** excellently. You created a generator that accepts only those objects that **simultaneously** have a price (`Payable`) and know how to print to PDF (`Exportable`). If you uncomment the line with `DigitalService`, the script will crash because the Service does not know how to print. This is strict architectural protection.
