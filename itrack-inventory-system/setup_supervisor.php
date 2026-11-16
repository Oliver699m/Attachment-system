<?php
require_once 'config.php';

echo "<h2>Supervisor Setup Script</h2>";
echo "<p>This will create the supervisor user. Run once, then delete this file.</p>";

// Delete if exists
$stmt = $pdo->prepare("DELETE FROM users WHERE username = 'supervisor1'");
$stmt->execute();
echo "<p>Old user deleted (if existed).</p>";

// Insert new supervisor
$username = 'supervisor1';
$email = 'supervisor@itrackzimbabwe.com';
$password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';  // For 'password123'
$department = 'Management';
$role = 'supervisor';

$stmt = $pdo->prepare("INSERT INTO users (username, email, password, department, role) VALUES (?, ?, ?, ?, ?)");
if ($stmt->execute([$username, $email, $password_hash, $department, $role])) {
    echo "<p style='color:green;'>✅ Supervisor user created successfully!</p>";
    echo "<p>Username: <strong>supervisor1</strong></p>";
    echo "<p>Password: <strong>password123</strong></p>";
    echo "<p>Role: <strong>supervisor</strong></p>";
    echo "<p>Now go to index.php and login with these credentials.</p>";
} else {
    echo "<p style='color:red;'>❌ Failed to create user. Error: " . print_r($pdo->errorInfo(), true) . "</p>";
}

// Verify
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();
if ($user) {
    echo "<p>Verification: User found with role '" . $user['role'] . "'.</p>";
    if (password_verify('password123', $user['password'])) {
        echo "<p style='color:green;'>✅ Password verification works!</p>";
    } else {
        echo "<p style='color:red;'>❌ Password verification failed!</p>";
    }
}

echo "<hr><a href='index.php'>Go to Login</a> | <a href='logout.php'>Logout</a>";
?>