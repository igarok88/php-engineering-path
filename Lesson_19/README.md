### Lesson #19: Memory Optimization: Generators (`yield`) and Streams

Let's recall the source: an expert is distinguished by the ability to process a 10 GB file while consuming no more than 100 MB of RAM.

#### THEORY: Pipeline vs. Warehouse

**Mental Model:**

- **Array:** This is a **Warehouse**. To process 1 million products, you first have to build a huge warehouse (allocate RAM), bring _all_ products there, and only then start working. If the warehouse overflows (`Fatal Error: Allowed memory size exhausted`), the script dies.
- **Generator:** This is a **Conveyor Belt (Pipeline)**. You take _one_ product, process it, throw it away (free up memory), and only then ask for the next one. Only **one** element is ever in memory.

**The `yield` keyword:** When the interpreter sees `yield`, it does not return a value and does not forget the function's state (like `return`). It "pauses" the function's execution and spits out one value. On the next iteration, the function "wakes up" exactly where it fell asleep.

**Naive Example (RAM Death):**

PHP

        function getRows(string $file): array {
            // file() loads the ENTIRE file into an array of strings.
            // If the file is 200MB, the script will eat ~400MB RAM.
            return file($file);
        }

        foreach (getRows('big_log.csv') as $row) { ... }

**Engineering Example (Generator + Stream):**

PHP

        /**
         * @return Generator<int, array>
         */
        function getRows(string $file): Generator {
            // Open a stream (trickle of data) to the file. The file is not loaded into memory.
            $handle = fopen($file, 'rb');

            while (!feof($handle)) {
                // Read ONE line, parse CSV
                $row = fgetcsv($handle);
                if ($row !== false) {
                    // "Spit out" the line and go to sleep.
                    // Memory is spent only on the $row array.
                    yield $row;
                }
            }
            fclose($handle);
        }

        // foreach "pulls" the generator itself, waking it up to get the next line
        foreach (getRows('big_log.csv') as $row) { ... }

---

### TASK (Practice)

We will simulate importing products. Imagine a supplier sent a price list `import.csv` with 100,000 lines.

**Preparation:** Create an `import.csv` file in the project root (manually):

Фрагмент кода

        name,price,qty
        "Samsung TV",500,10
        "Dyson Vacuum",300,5
        "Sony PS5",450,20

**Task:**

1.  Create a `CsvReader` service.

    - It must implement an interface (invent it yourself, e.g., `ReaderInterface`).
    - The method `read(string $path): Generator` must use `fopen` and `yield`.
    - It must return an **associative array** (use the first line of the file as key headers), i.e., `['name' => 'Samsung TV', ...]` instead of `[0 => 'Samsung TV']`.

2.  Create a command/controller (or just code in `index.php` for the test) that uses `CsvReader`.
3.  In a `foreach` loop, create `PhysicalProduct` objects from the generator data and add them to the `Cart`.
4.  **Experiment:** Use the `memory_get_usage()` function before the start of the loop and after to prove efficiency.

**Constraints:**

- Strictly `declare(strict_types=1);`.
- It is forbidden to use functions `file()`, `file_get_contents()`. Only stream reading (`fopen`, `fgetcsv`, or `fgets`).
- In PHPDoc, specify the generic return type: `Generator<int, array>`.

Awaiting your efficient code.

---

**Excellent result.** You wrote clean, safe, and efficient code.

**Code Review (Solution Assessment):**

1.  **Stream Processing:** You correctly used `fopen` and `fgetcsv` inside the loop. The file is not loaded into memory entirely.
2.  **Generator:** `yield` is used correctly.
3.  **Safety:** The check `count($headers) !== count($row)` is a sign of a Senior Developer. Without it, `array_combine` would throw a fatal error if there was a broken line in the CSV.
4.  **Try/Catch:** The wrapper of the client code is correct.

**⚠️ Architectural Note (The Memory Trap):** You optimized **Reading**, but there is a nuance in **Storage** (State). Look at this code:

PHP

        foreach ($reader->read('import.csv') as $row) {
            // ... create $product
            $cart->add($product); // <--- DANGER!
        }

If there are 1 million lines in the CSV file, your generator will work perfectly, consuming little memory. **BUT** the `$cart` object will store an array of 1 million `PhysicalProduct` objects. As a result, memory will run out anyway (`Allowed memory size exhausted`).

**Real World Solution (Batch Processing):** When importing large data, we do not accumulate it in memory. We use the "Batch Processing" approach:

PHP

        $batch = [];
        foreach ($reader->read(...) as $row) {
            $batch[] = new Product(...);
            if (count($batch) >= 1000) {
                $repository->saveMany($batch); // Save 1000 items to DB
                $batch = []; // Clear memory!
                // EntityManager::clear(); // If Doctrine
            }
        }

_But for the current lesson, your solution is accepted, as the task was specifically about writing a generator._

Let's "get under the hood" and see how variable values change step by step. This is like watching a movie in slow motion.

Imagine we have an `import.csv` file with the following content:

Фрагмент кода

        name,price,qty
        "Samsung TV",500,10
        "Dyson Vacuum",300,5

---

### Stage 1: Start and Reading Headers

The code starts executing when we enter the `foreach` loop:

PHP

        foreach ($reader->read('import.csv') as $row) { ... }

At this moment, PHP jumps inside the `CsvReader::read` method.

**1\. Opening the file**

PHP

        $handle = fopen($path, 'rb');

- **`$path`**: `'import.csv'`
- **`$handle`**: This is not the file content! This is a **Resource (Resource id #...)**. Imagine this is a "phone number" you can use to call the file. We haven't read anything yet; the cursor is at the very beginning (byte 0).

**2\. Reading headers**

PHP

        $headers = fgetcsv($handle);

- The function reads the first line and moves the file cursor further.
- **`$headers`**:

  PHP

        [
            0 => 'name',
            1 => 'price',
            2 => 'qty'
        ]

---

### Stage 2: First Iteration (First Product)

We enter the `while (!feof($handle))` loop.

**1\. Reading the raw line**

PHP

        $row = fgetcsv($handle);

- The file cursor moves to the second line.
- **`$row` (inside the method)**:

  PHP

        [
            0 => 'Samsung TV',
            1 => '500',
            2 => '10'
        ]

**2\. Combining (Key Magic)**

PHP

        $data = array_combine($headers, $row);

- PHP takes keys from `$headers` and values from `$row` and glues them together.
- **`$data`**:

  PHP

        [
            'name'  => 'Samsung TV',
            'price' => '500',
            'qty'   => '10'
        ]

**3\. Key Moment: YIELD**

PHP

        yield $data;

- **WHAT HAPPENS:** The `read()` function **freezes**. It does not close; the variables `$handle`, `$headers` are not deleted. They "solidify" in RAM.
- The value `$data` is "thrown" out to the main file `index.php`.

---

### Stage 3: Working Outside (in index.php)

Now control has passed to the `foreach` loop in the main file.

PHP

        foreach ($reader->read('import.csv') as $row) {
            // Here $row is what yield spat out ($data)

            $name = $row['name']; // "Samsung TV"
            $price = (int)$row['price']; // 500

            // Object is created. Memory is spent ONLY on this single object.
            $cart->add(new PhysicalProduct(...));
        }

As soon as the code reaches the closing bracket `}` of the `foreach` loop, it says: "I need the next element!".

---

### Stage 4: Returning to the Generator (Second Product)

PHP "unfreezes" the `read()` method exactly where it fell asleep (right after `yield`).

1.  The `while` loop continues.
2.  **Reading the raw line (`$row = fgetcsv($handle)`)**:

    - Now `$row` is overwritten with new data! Old data about "Samsung" is deleted from memory (if there are no references to it).
    - **`$row` (new value)**:

      PHP

            [
                0 => 'Dyson Vacuum',
                1 => '300',
                2 => '5'
            ]

3.  **Combining**:

    - **`$data` (new value)**:

      PHP

            [
                'name'  => 'Dyson Vacuum',
                'price' => '300',
                'qty'   => '5'
            ]

4.  **`yield $data`**: Pause again, data flies out.

---

### Stage 5: The End

1.  The main `foreach` loop requests data again.
2.  The `read` method wakes up.
3.  Calls `fgetcsv($handle)`.
4.  The file ended. The function returns `false`.
5.  The loop `while (!feof($handle))` sees the end of the file and terminates.
6.  `fclose($handle)` closes the "pipe" to the file.
7.  The `read` method terminates completely.

### Main Conclusion on Variables

At any given moment in time, inside the `$data` variable, there is **only one array** (one table row). As soon as we move to the next row, the previous one is erased from `$data` and frees up space. This is precisely why we can read a 10 GB file with only 10 MB of memory — we never store the entire file in a variable at once.
