### Lesson #2: Arrays, Loops, and Data Structures

#### 1\. THEORY

**Concept:** In real applications, we rarely work with single variables (`$productName`). We work with lists of data. In PHP, an array is an incredibly powerful but treacherous structure. It is simultaneously a regular list (like `List` in Python) and a hash table (like `Dictionary` in Python or `Map` in Java).

**Mental Model:** When you pass an array to a function, PHP (by default) makes a copy of it (**Copy-on-Write** mechanism). This differs from objects, which are passed by reference. Deliberate practice requires you to understand the structure of your data. "Just an array" is a bad data type because it is unclear what is inside.

**Naive Example:**

PHP

    $cart = [
        ['Iphone', 1000, 1],
        ['Airpods', 200, 2]
    ];
    // We access by indices 0, 1, 2.
    // In a month, no one will remember what $item[1] means — is it price or quantity?
    echo $cart[0][1];

**Engineering Example (Associative Arrays):** We use keys so the code documents itself.

PHP

    $cart = [
        [
            'name' => 'Iphone',
            'price' => 1000,
            'qty' => 1,
        ],
        // ...
    ];
    // Now $item['price'] reads unambiguously.

#### 2\. TASK (Practice)

**Task: "Manual Aggregation Implementation"** In accordance with the methodology, we will not use ready-made convenient functions like `array_sum` or `array_reduce`. You must write the algorithm yourself to feel how working with state works.

You have a shopping cart (an array of associative arrays). Write a function `calculateCartTotal` that takes this array and returns the total sum of the cart.

**Input Data:**

PHP

    $cart = [
        ['name' => 'Airflow', 'price' => 100, 'qty' => 2],
        ['name' => 'Laravel Book', 'price' => 50, 'qty' => 1],
        ['name' => 'Coffee', 'price' => 10, 'qty' => 5],
    ];

**Constraints:**

1.  Use a `foreach` loop.
2.  It is **forbidden** to use `array_sum`, `array_map`, and other built-in functions. Only the loop and math.
3.  Type hint the incoming argument as `array`.
4.  Inside the loop, check if keys `'price'` and `'qty'` exist. (In real life, we would use DTOs/Objects, but for now, we are training with arrays).
5.  If keys are missing — the script must not crash with a Warning, it should simply skip the "broken" item (use `isset` or `array_key_exists`).

**Awaiting code.**

---

### 🟢 Status: Code Accepted

Excellent work.

**Defensive Programming:** You used `isset` to protect the code from missing keys. This is commendable. In a real project, if data comes from outside (API, JSON), we never trust its structure 100%.

**Algorithm:** You correctly implemented the "Accumulator" pattern (the `$total` variable that accumulates the value).

You just completed the "Standard Library Reverse Engineering" exercise. You manually wrote what works as `array_reduce` inside PHP. Understanding how a loop works is necessary to realize algorithmic complexity (O(n)).

Now we move from the **Imperative style** (describing _how_ to do it: "take an element, add, repeat") to the **Declarative style** (describing _what_ we want to get: "fold the array into a sum").
