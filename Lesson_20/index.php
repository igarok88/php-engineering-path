<?php

declare(strict_types=1);

// --- 1. TOOLS ---

/**
 * Simulated HTTP client.
 * Instead of actually waiting for a response from a server, it "freezes" execution.
 */
class SimulatedHttpClient
{
    public function get(string $url): string
    {
        // 1. Simulate sending request
        echo "   [Network] 📡 Sending request: $url<br>";

        // 2. MAGIC: We don't wait for a response (sleep). We tell PHP: 
        // "Stop this fiber, return control to the scheduler".
        // null is passed out (a status could be passed).
        Fiber::suspend(); // State: Fiber 0 is "frozen" right in the middle of the get() method. All variables inside it are saved. Control returns to the Scheduler.

        // 3. When the scheduler calls resume(), the code continues exactly from here
        echo "   [Network] ✅ Response received for: $url<br>";

        // Return fake content
        return "<html>Content of $url</html>";
    }
}

/**
 * Scheduler - our mini-EventLoop.
 * It manages the task queue.
 */
class Scheduler
{
    /** @var Fiber[] Queue of active fibers */
    private array $fibers = []; // At the beginning, the $fibers array in the Scheduler holds 3 tasks.

    // 3 Fiber objects: (initial state, before $scheduler->run())
    // Inside each Fiber lies the very anonymous function we passed: function () use ($http) { ... }


    // array(3) {
    //   [0] => object(Fiber) {
    //     ["state"] => "unstarted" // IMPORTANT: It is still sleeping!
    //     ["callable"] => object(Closure) // This is our anonymous function about Google
    //   }
    
    //   [1] => object(Fiber) {
    //     ["state"] => "unstarted"
    //     ["callable"] => object(Closure) // This is the function about Yandex
    //   }

    //   [2] => object(Fiber) {
    //     ["state"] => "unstarted"
    //     ["callable"] => object(Closure) // This is the function about GitHub
    //   }
    // }

    /**
     * Adds a task to the queue.
     * The task is wrapped in a Fiber so it can be paused.
     */
    public function addTask(callable $task): void
    {
        // Create a fiber, but don't start it immediately.

        // Before running run(), inside $fibers lie 3 objects of the Fiber class. They are currently sleeping (Unstarted state).


        $fiber = new Fiber($task); // These variables do not overwrite each other. Each fiber has its own isolated "backpack" with data. When we do resume(), the fiber opens its backpack and sees: "Aha, I was working with url https://www.google.com...".




        $this->fibers[] = $fiber;

        // $scheduler->fibers = [
        //     0 => Fiber (Task 1: Google),
        //     1 => Fiber (Task 2: Yandex),
        //     2 => Fiber (Task 3: GitHub)
        // ];
    }

    /**
     * Starts the Event Loop.
     * This is the "heart" that beats as long as there is work.
     */
    public function run(): void
    {
        echo "🚀 Scheduler: Starting event loop...<br><br>";

        // While there is at least one unfinished fiber
        while (!empty($this->fibers)) {

            // Iterate through the task queue
            foreach ($this->fibers as $i => $fiber) {

                try {
                    // A. If the fiber hasn't started yet — start it
                    if (!$fiber->isStarted()) {
                        $fiber->start();

                        // Step 1. Task 1 (Google)
                        // The Scheduler sees: Fiber 0 is not started (!$fiber->isStarted()).

                        // Action: $fiber->start().
                    }
                    // B. If the fiber is paused (waiting for I/O) — wake it up
                    elseif ($fiber->isSuspended()) {
                        $fiber->resume(); // Magic: PHP teleports inside the get() method, exactly to the line AFTER Fiber::suspend();.
                    }
                    // C. If the fiber has finished work — remove from queue
                    elseif ($fiber->isTerminated()) {
                        unset($this->fibers[$i]);
                    }
                } catch (Throwable $e) {
                    echo "Error in task: " . $e->getMessage() . "<br>";
                }
            }

            // Reset array keys so there are no holes after unset
            $this->fibers = array_values($this->fibers);
        }

        echo "<br>🏁 Scheduler: All tasks completed.<br>";
    }
}

// --- 2. SCENARIO ---

$scheduler = new Scheduler();
$http = new SimulatedHttpClient();

// Task 1: Google (makes 2 requests)
$scheduler->addTask(function () use ($http) {
    echo "[Task 1] Start (Google)<br>";

    $http->get('https://google.com'); // Here the fiber will fall asleep for the first time
    echo "[Task 1] Processing data...<br>";

    $http->get('https://google.com/images'); // Here the fiber will fall asleep for the second time

    echo "[Task 1] Finished<br>";
});

// Task 2: Yandex (makes 2 requests)
$scheduler->addTask(function () use ($http) {
    echo "[Task 2] Start (Yandex)<br>";

    $http->get('https://yandex.ru');
    echo "[Task 2] Analytics...<br>";

    $http->get('https://yandex.ru/maps');

    echo "[Task 2] Finished<br>";
});

// Task 3: GitHub (fast task, 1 request)
$scheduler->addTask(function () use ($http) {
    echo "[Task 3] Start (GitHub)<br>";

    $http->get('https://github.com');

    echo "[Task 3] Finished<br>";
});

// --- 3. EXECUTION ---

$scheduler->run();
