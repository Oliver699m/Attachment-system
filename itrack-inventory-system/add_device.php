<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_POST) {
    $type = $_POST['type'];
    $quantity = (int)$_POST['quantity'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO devices (type, quantity, description) VALUES (?, ?, ?)");
    if ($stmt->execute([$type, $quantity, $description])) {
        header('Location: devices.php?success=Added');
        exit;
    } else {
        $error = "Failed to add device.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Device</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="site_footer.css">
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <?php $active_page = 'inventory'; include 'site_header.php'; ?>
    <main class="main-content">
    <div class="container">
        <a href="devices.php" class="back-link"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to inventory</a>
        <h1>Add New Device Type</h1>
        <p>Register a tracking device and set its available stock.</p>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

        <form method="POST">
            <input type="text" name="type" placeholder="Device Type (e.g., GPS Tracker)" required>
            <input type="number" name="quantity" placeholder="Initial Quantity" min="0" required>
            <textarea name="description" placeholder="Description"></textarea>
            <button type="submit">Add Device</button>
        </form>
    </div>
    </main>
    <?php include 'site_footer.php'; ?>
</body>
</html>