### Lesson #20: Asynchronous Programming and Fibers

We are moving on to the most complex and modern topic in PHP. Before version 8.1, PHP was strictly synchronous. If you made a request to an external API, the script "froze" and waited for a response.

#### THEORY: Blocking I/O vs Non-Blocking I/O

**Mental Model:**

- **Synchronous (Blocking):** A supermarket checkout line. You cannot serve the next customer until the current one pays and packs their groceries. If the terminal hangs for 5 seconds, the whole line stops.
- **Asynchronous (Non-Blocking):** A fast-food restaurant. You take an order, give a receipt with a number, and _immediately_ take the next order. The kitchen cooks in parallel.

PHP 8.1 introduced **Fibers**. This is a mechanism for creating "lightweight threads" that can be suspended and resumed (similar to Generators, but more powerful — they can suspend anywhere in the call stack).

**Naive Example (Blocking - PHP < 8.1):**

PHP

        $start = microtime(true);
        // Each request takes 1 second
        file_get_contents('https://site1.com'); // Wait 1 sec
        file_get_contents('https://site2.com'); // Wait 1 sec
        file_get_contents('https://site3.com'); // Wait 1 sec
        // Total: 3 seconds

**Engineering Example (Fibers - PHP 8.1+):** Fibers allow launching three tasks "almost simultaneously". When the first task goes into waiting for a response (I/O wait), PHP switches to the second one.

> **Important:** Fibers themselves are a low-level tool. In real code (Laravel/Symfony), an Event Loop is built on top of them (like in ReactPHP or Amp). But to become a Senior, you must understand how it works "under the hood" without libraries.

---

### TASK (Practice)

We will write a simulator of asynchronous HTTP requests. Since we don't have a real Event Loop library at hand, we will simulate "long operations" and manual context switching.

**Task:**

1.  Create a `SimulatedHttpClient` class.

    - Method `get(string $url): string`.
    - Inside the method, use `Fiber::suspend()` to simulate waiting for the network (as if we sent a request and are waiting).

2.  Create a "Task Scheduler" (`Scheduler`). This will be your mini-EventLoop.

    - It must store a list of active fibers.
    - Method `addTask(callable $task)` creates a fiber and adds it to the queue.
    - Method `run()` loops while there are active fibers and calls `resume()` for each.

3.  **Scenario:**

    - Add 3 tasks: loading "[https://www.google.com/search?q=google.com](https://www.google.com/search?q=google.com)", "yandex.ru", "github.com".
    - Inside each task, call `$http->get($url)` **twice** (simulating a redirect or complex logic).
    - Output a log to the console so we see how tasks are interleaved (Task 1 started -> Task 2 started -> Task 1 continued).

**How it works (Mental Model for Implementation):**

1.  `Task 1` calls `$http->get()`.
2.  `$http->get()` says: "I am waiting for the network, pausing." Calls `Fiber::suspend()`.
3.  Control returns to the `Scheduler`.
4.  `Scheduler` takes `Task 2` and runs it.
5.  ...
6.  In the next loop pass, `Scheduler` sees that `Task 1` is "suspended" and calls `$fiber->resume()`.

**Constraints:**

- Use the `Fiber` class.
- Show via logs that execution is **not** sequential (Task 1 start -> Task 1 end -> Task 2 start...), but **concurrent** (Task 1 start -> Task 2 start -> Task 1 end...).

Awaiting your implementation of your own Async Runtime!

---

This is perhaps the hardest conceptual barrier in PHP, and you overcame it on the first try.

**Code Review:**

1.  **Scheduler Logic:** Your `while (!empty($fibers))` loop with `isStarted` / `isSuspended` checks is essentially the simplified essence of how engines like ReactPHP or Amp work.
2.  **Execution Flow:** You correctly understood that `Fiber::suspend()` does not block the entire script but returns control to the `Scheduler`, which immediately picks up the next task.
3.  **Result:** If you run this code, you will see how tasks are mixed. While Google "waits for the network," Yandex starts sending a request. This is **Concurrency**.

You are ready. Now you have the full toolset: Architecture (DI), Types (Strict), Memory (Generators), Async (Fibers).

### Key Concept: Snapshot ("Freezing")

The most important thing to understand: when `Fiber::suspend()` is called, PHP takes a **snapshot** of the current state of the function.

- It remembers the line number of the code.
- It remembers the values of all local variables (`$url`, `$i`, etc.).
- This snapshot is stored in RAM until we call `resume()`.

---

### Execution Trace (Step-by-Step)

At the beginning of the work in the `Scheduler`, the `$fibers` array contains 3 tasks. Array indices: `[0, 1, 2]`.

#### ROUND 1: Start (Everyone jumps into the water)

The scheduler starts iterating through the `$fibers` array.

1.  **Task 1 (Google) \[Index 0\]:**

    - **Status:** Not started (`!started`).
    - **Action:** `$fiber->start()`.
    - **Code:** Enters the function, prints "Start", calls `$http->get('google.com')`.
    - **Inside `get()`:** Reaches `Fiber::suspend()`.
    - **Result:** Fiber **sleeps**. Control returns to the Scheduler.

2.  **Task 2 (Yandex) \[Index 1\]:**

    - **Status:** Not started.
    - **Action:** `$fiber->start()`.
    - **Code:** Prints "Start", calls `$http->get('yandex.ru')`, reaches `suspend()`.
    - **Result:** Fiber **sleeps**.

3.  **Task 3 (GitHub) \[Index 2\]:**

    - **Status:** Not started.
    - **Action:** `$fiber->start()`.
    - **Code:** Prints "Start", calls `$http->get('github.com')`, reaches `suspend()`.
    - **Result:** Fiber **sleeps**.

> **Round 1 Summary:** All three tasks are "hanging" in the air. None have finished. The `$fibers` variable still contains 3 elements.

---

#### ROUND 2: Awakening (Work continues)

The scheduler goes through the list `[0, 1, 2]` again.

1.  **Task 1 (Google) \[Index 0\]:**

    - **Status:** Suspended (`suspended`).
    - **Action:** `$fiber->resume()`.
    - **Magic:** PHP teleports inside the `get()` method, exactly to the line AFTER `suspend()`.
    - **Code:**

      - Outputs "✅ Response received".
      - Returns HTML.
      - Code returns to the anonymous function (Task 1).
      - Outputs "\[Task 1\] Processing data...".
      - Calls `$http->get('google.com/images')` a **second** time.
      - Hits `suspend()` inside `get()` again.

    - **Result:** Fiber **sleeps** again (for the second time).

2.  **Task 2 (Yandex) \[Index 1\]:**

    - **Status:** Suspended.
    - **Action:** `$fiber->resume()`.
    - **Code:** Receives response, writes "Analytics...", calls the **second** request to `maps`.
    - **Result:** Fiber **sleeps** again.

3.  **Task 3 (GitHub) \[Index 2\]:**

    - **Status:** Suspended.
    - **Action:** `$fiber->resume()`.
    - **Code:**

      - Receives response.
      - Outputs "\[Task 3\] Done".
      - **Function code ended!** No more commands.

    - **Result:** Fiber moves to **Terminated** status.
    - **Scheduler:** Sees `terminated`, removes it from the array (`unset`).

> **Round 2 Summary:** Task 3 is gone. Tasks 1 and 2 are hanging on the second request. The `$fibers` array has 2 elements left.

---

#### ROUND 3: Finale (Finishing leftovers)

The scheduler goes through the list (now only Google and Yandex are there).

1.  **Task 1 (Google):**

    - **Status:** Suspended (on the second request).
    - **Action:** `$fiber->resume()`.
    - **Code:** Receives second response, writes "\[Task 1\] Done". Function ended.
    - **Result:** Terminated -> Delete.

2.  **Task 2 (Yandex):**

    - **Status:** Suspended (on the second request).
    - **Action:** `$fiber->resume()`.
    - **Code:** Receives second response, writes "\[Task 2\] Done". Function ended.
    - **Result:** Terminated -> Delete.

---

### Where and what are the variable values?

Let's look at the state inside the `SimulatedHttpClient::get($url)` method at the moment of pause.

PHP

        public function get(string $url): string
        {
            // ...
            Fiber::suspend(); // <--- WE ARE HERE
            // ...
        }

When execution stands on the `suspend()` line:

1.  **In the Stack Memory:**

    - For **Task 1**: The variable `$url` equals `'https://google.com'`. It is "frozen".
    - For **Task 2**: The variable `$url` equals `'https://yandex.ru'`. It is also "frozen" in a _different_ memory area (in a different fiber).

These variables do not overwrite each other. Each fiber has its own isolated "backpack" with data. When we do `resume()`, the fiber opens its backpack and sees: "Aha, I was working with url [https://www.google.com](https://www.google.com/)...".

### Summary for Understanding

Imagine you have 3 tabs with videos (YouTube) open in your browser.

1.  You pressed Play on the first tab, it started loading (buffering) -> you pressed Pause (`suspend`).
2.  Switched to the second, pressed Play -> Pause.
3.  Switched to the third, pressed Play -> Pause.

When the video has loaded, you return to the first tab and press Play again (`resume`). The video continues exactly from the second where it stopped, not from the beginning.

**The Scheduler** is you switching tabs. **Fibers** are the tabs themselves with their state (at which second the video is, which video is open).
