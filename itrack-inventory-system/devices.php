<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Fetch devices
$stmt = $pdo->query("SELECT * FROM devices ORDER BY created_at DESC");
$devices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Device Types</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="site_footer.css">
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <?php $active_page = 'inventory'; include 'site_header.php'; ?>
    <main class="main-content">
    <div class="container inventory-panel">
        <div class="page-heading-row">
            <div>
                <h1>Device inventory</h1>
                <p>Tracking equipment and available stock.</p>
            </div>
            <a href="add_device.php" class="btn"><i class="fas fa-plus" aria-hidden="true"></i>&nbsp; Add device</a>
        </div>

        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Description</th>
                    <th>Added</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($devices)): ?>
                    <tr><td colspan="4" class="table-empty">No devices have been added yet. <a href="add_device.php">Add the first device</a>.</td></tr>
                <?php else: ?>
                    <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($device['type']); ?></td>
                            <td><?php echo $device['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($device['description'] ?? 'N/A'); ?></td>
                            <td><?php echo $device['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    </main>
    <?php include 'site_footer.php'; ?>
</body>
</html>