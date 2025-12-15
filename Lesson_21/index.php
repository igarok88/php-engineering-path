<?php

declare(strict_types=1);

// --- 0. SETUP (Testing Ground) ---

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create table and populate with data
$pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
$pdo->exec("INSERT INTO users (name, email) VALUES ('Alice', 'alice@corp.com')");
$pdo->exec("INSERT INTO users (name, email) VALUES ('Bob', 'bob@corp.com')");
$pdo->exec("INSERT INTO users (name, email) VALUES ('CEO', 'admin@corp.com')");
// Add a "poisoned" user for XSS test
$pdo->exec("INSERT INTO users (name, email) VALUES ('<script>alert(\"HACKED\")</script>', 'hacker@xss.com')");


// ⛔ VULNERABLE REPO (Leaky Class)
class VulnerableUserRepo
{
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): array
    {
        // TERRIBLE SECURITY MISTAKE
        // We intentionally log this query so you can see what happens inside
        $sql = "SELECT id, name, email FROM users WHERE email = '$email'";
        echo "   [DB Log] Executing SQL: $sql\n";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}

echo "<h1>🏴‍☠️ STAGE 1: SQL Injection (RED TEAM)</h1>";

$vulnerableRepo = new VulnerableUserRepo($pdo);

// 1. ATTACK
// We close the quote, add an "OR TRUE" condition, and comment out or close the rest.
$maliciousEmail = "' OR '1'='1";

echo "<b>Hacker Input:</b> $maliciousEmail<br><br>";

try {
    $hackedUsers = $vulnerableRepo->findByEmail($maliciousEmail);

    echo "<pre><b>Hack Result (entire database leaked):</b>\n";
    print_r($hackedUsers);
    echo "</pre>";

    if (count($hackedUsers) > 1) {
        echo "<span style='color:red'>[SUCCESS] Database compromised! Access gained to " . count($hackedUsers) . " accounts.</span><br><br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<hr>";

// --- 3. DEFENSE (BLUE TEAM) ---

echo "<h1>🛡️ STAGE 2: Secure Coding (BLUE TEAM)</h1>";

class SecureUserRepo
{
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): array
    {
        // 1. Preparation (Prepare)
        // The DB builds an execution plan. It knows that :email is ONLY data.
        $stmt = $this->pdo->prepare("SELECT id, name, email FROM users WHERE email = :email");

        // 2. Execution (Execute)
        // We pass data separately. Even if there are quotes, the DB will perceive them as part of the string.
        $stmt->execute(['email' => $email]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$secureRepo = new SecureUserRepo($pdo);

echo "<b>Attempting repeat attack via secure repository...</b><br>";
$result = $secureRepo->findByEmail($maliciousEmail);

if (count($result) === 0) {
    echo "<span style='color:green'>[DEFENDED] Attack repelled. User with email \"$maliciousEmail\" not found.</span><br>";
} else {
    echo "Something went wrong...<br>";
}

echo "<hr>";

// --- 4. XSS DEFENSE ---

echo "<h1>🦠 STAGE 3: XSS Defense</h1>";

class HtmlView
{
    public function render(array $users): string
    {
        $html = "<ul>\n";

        foreach ($users as $user) {
            // DEFENSE: htmlspecialchars turns <script> into &lt;script&gt;
            // ENT_QUOTES - escapes both double and single quotes
            // 'UTF-8' - explicitly specify encoding
            $safeName = htmlspecialchars($user['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeEmail = htmlspecialchars($user['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // If we output $user['name'] directly, the hacker's JS would execute in the browser
            $html .= "  <li>Name: <b>{$safeName}</b> | Email: {$safeEmail}</li>\n";
        }

        $html .= "</ul>";
        return $html;
    }
}

// Get all users (via secure repo, but taking all for the test)
$stmt = $pdo->query("SELECT * FROM users");
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$view = new HtmlView();
echo "<b>Secure rendering of user list:</b><br>";

// Output the result. Note that <script> is visible as text, not executed.
echo $view->render($allUsers);

// For clarity, let's show the source code of what was generated
echo "<br><b>Source Code:</b><pre>";
echo htmlspecialchars($view->render($allUsers));
echo "</pre>";
