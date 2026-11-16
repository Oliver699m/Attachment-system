<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$department = $_SESSION['department'];

// Get user email for request record
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$user_email = $user['email'] ?? '';

// Fetch device types for dropdown
$stmt = $pdo->query("SELECT id, type, quantity FROM devices WHERE quantity > 0");
$devices = $stmt->fetchAll();

$error = '';
$success = '';

if ($_POST) {
    $requisitioner = $_POST['requisitioner'] ?? $username;
    $department = $_POST['department'] ?? $department;
    $device_id = $_POST['device_type'];
    $device_type = $_POST['device_type_text'];  // Hidden field for type name
    $description = $_POST['description'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (empty($device_type) || empty($reason)) {
        $error = "Please select a device and provide a reason.";
    } else {
        // Check stock (warn, but don't decrement yet)
        $stmt = $pdo->prepare("SELECT quantity FROM devices WHERE id = ?");
        $stmt->execute([$device_id]);
        $stock = $stmt->fetch()['quantity'] ?? 0;

        if ($stock <= 0) {
            $error = "No stock available for {$device_type}. Please contact supervisor.";
        } else {
            // Insert request (status 'Pending', no stock change)
            $stmt = $pdo->prepare("INSERT INTO requests (user_id, requisitioner, department, device_type, description, reason, requester_email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $requisitioner, $department, $device_type, $description, $reason, $user_email])) {
                $success = "Request submitted successfully! Awaiting supervisor approval. (Stock for {$device_type}: {$stock})";
            } else {
                $error = "Failed to submit request. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Device - iTrack Assets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="site_footer.css">
    <style>
        /* Modern form styling to match dashboard (green/grey) */
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif; background-color: #6cb87fff; padding: 2rem; }
        .container { max-width: 600px; margin: 0 auto; background: -o-repeating-linear-gradient(); padding: 2rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .page-title { font-size: 1.875rem; font-weight: 700; color: #1a1a1a; margin-bottom: 1rem; text-align: center; }
        .back-link { display: inline-block; margin-bottom: 1rem; color: #4CAF50; text-decoration: none; }
        .back-link i { margin-right: 0.5rem; }
        .form { display: flex; flex-direction: column; gap: 1rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .label { font-size: 0.875rem; font-weight: 500; color: #1a1a1a; }
        .input, select, textarea { padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; }
        .input:focus, select:focus, textarea:focus { outline: none; border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1); }
        textarea { resize: vertical; min-height: 80px; }
        .submit-button { padding: 0.75rem; background: #4CAF50; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; }
        .submit-button:hover { background: #45a049; }
        .message { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; text-align: center; font-weight: 500; }
        .error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .success { background: #d1fae5; color: #4CAF50; border: 1px solid #a7f3d0; }
        @media (max-width: 768px) { .container { padding: 1.5rem; } }
    </style>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <?php $active_page = 'requests'; include 'site_header.php'; ?>
    <main class="main-content">
    <div class="container">
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <h1 class="page-title">Request a device</h1>
        <p class="page-intro">Send a tracking device request to your supervisor for approval.</p>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" class="form" id="requestForm">
            <div class="form-group">
                <label class="label">Requisitioner</label>
                <input type="text" name="requisitioner" value="<?php echo htmlspecialchars($username); ?>" readonly>
            </div>
            <div class="form-group">
                <label class="label">Department</label>
                <input type="text" name="department" value="<?php echo htmlspecialchars($department); ?>" readonly>
            </div>
            <div class="form-group">
                <label class="label">Device Type</label>
                <select name="device_type" id="deviceSelect" required>
                    <option value="">Select Device Type</option>
                    <?php foreach ($devices as $device): ?>
                        <option value="<?php echo $device['id']; ?>" data-type="<?php echo htmlspecialchars($device['type']); ?>">
                            <?php echo htmlspecialchars($device['type']); ?> (Stock: <?php echo $device['quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="device_type_text" id="deviceTypeText">
            </div>
            <div class="form-group">
                <label class="label">Description</label>
                <textarea name="description" placeholder="Optional description of the device usage"></textarea>
            </div>
            <div class="form-group">
                <label class="label">Reason for Request</label>
                <textarea name="reason" placeholder="Why do you need this device?" required></textarea>
            </div>
            <button type="submit" class="submit-button">Submit Request</button>
        </form>
    </div>
    </main>

    <script>
        // Dynamic hidden field for device type
        document.addEventListener('DOMContentLoaded', function() {
            const deviceSelect = document.getElementById('deviceSelect');
            const deviceTypeText = document.getElementById('deviceTypeText');
            if (deviceSelect && deviceTypeText) {
                deviceSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    deviceTypeText.value = selectedOption.getAttribute('data-type') || '';
                });
            }

            // Basic validation
            document.getElementById('requestForm').addEventListener('submit', function(e) {
                if (!deviceTypeText.value.trim()) {
                    e.preventDefault();
                    alert('Please select a device type.');
                }
            });
        });
    </script>
    <?php include 'site_footer.php'; ?>
</body>
</html>