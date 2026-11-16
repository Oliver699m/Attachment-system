<?php
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "<h3>New Hash for 'password123':</h3>";
echo "<p>Copy this entire string:</p>";
echo "<strong style='font-family: monospace; background: #f0f0f0; padding: 10px; display: block;'>" . $hash . "</strong>";
echo "<p>Paste it into the SQL UPDATE below in phpMyAdmin.</p>";
?>