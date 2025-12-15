### Lesson #9: Testing and Mock Objects (Anonymous Classes)

The methodology states: "Dependency Injection (DI) is the foundation of testable code". Imagine that your `FileOrderRepository` doesn't just write `echo`, but sends data to a real bank or deletes files on the disk. How do you test the `OrderProcessor` logic without performing real, dangerous actions?

#### 1\. THEORY

**Concept: Mock Objects** We slip a "fake" repository to the processor. The processor thinks it is working with a real database, but in reality, it is working with a dummy that simply says, "I did everything."

**Tool: Anonymous Classes (PHP 7+)** We don't need to create a separate file `FakeRepository.php`. We can create a class "on the fly."

**Naive Example (creating an extra file):** Creating `class DummyRepo implements OrderRepositoryInterface...` just for the sake of one test. This clutters the project.

**Engineering Example (Anonymous Class):**

PHP

        $mockRepo = new class implements OrderRepositoryInterface {
            public function save(Cart $cart): void {
                echo "Mock: I pretended to save.";
            }
        };

        $processor = new OrderProcessor($mockRepo);

This allows us to isolate the class being tested (`OrderProcessor`) from the outside world.

#### 2\. TASK (Practice)

**Task:** Write a "Test" for `OrderProcessor` without using `FileOrderRepository`.

1.  Keep the `Cart`, `PhysicalProduct`, `OrderProcessor` classes, interfaces, etc.
2.  **Remove** (or comment out) the creation of `FileOrderRepository`.
3.  Instead, create a **Mock Object** via an anonymous class (`new class implements ...`).

    - In the `save` method of this mock, implement simple logic: output "TEST PASSED: Save method called. Amount: X" to the screen.

4.  Pass this mock to `OrderProcessor`.
5.  Run the processing.

**Goal:** Prove that `OrderProcessor` works with _any_ implementation of the interface, even one that didn't exist 5 minutes ago.

Awaiting implementation with an anonymous class.

---

### 🟢 Status: Code Accepted

Congratulations! You just wrote your first **Unit Test** (albeit a manual one).

**What we proved:**

1.  We swapped a "heavy" dependency (writing to a file) for a lightweight stub (Mock) without changing a single line in the `OrderProcessor` class.
2.  We confirmed that Dependency Injection (DI) is indeed the foundation of testable code.
3.  You used an **Anonymous Class**—a powerful PHP tool for creating objects "on the fly" without cluttering the file system.
