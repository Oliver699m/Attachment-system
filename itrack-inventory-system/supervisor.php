<?php
require_once 'config.php';

// Suppress notices for clean output
error_reporting(E_ALL & ~E_NOTICE);

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=not_logged_in');
    exit;
}

// Strict role check - interns get redirected
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

$error = '';
$success = '';

// Handle approve/reject
if (isset($_POST['action']) && ($_POST['action'] === 'approve' || $_POST['action'] === 'reject')) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    if ($request_id > 0) {
        $new_status = ($_POST['action'] === 'approve') ? 'Approved' : 'Rejected';
        $device_type = trim($_POST['device_type'] ?? '');

        // Update status and log history (only if pending)
        $stmt = $pdo->prepare("UPDATE requests SET status = ?, updated_at = CURRENT_TIMESTAMP, updated_by = ? WHERE id = ? AND status = 'Pending'");
        if ($stmt->execute([$new_status, $_SESSION['user_id'], $request_id])) {
            if ($_POST['action'] === 'approve') {
                // Decrement stock
                $stmt = $pdo->prepare("UPDATE devices SET quantity = GREATEST(0, quantity - 1) WHERE type = ?");
                $stmt->execute([$device_type]);
            }
            $success = "Request #{$request_id} updated to '{$new_status}' successfully.";
        } else {
            $error = "Failed to update (request may already be processed).";
        }
    } else {
        $error = "Invalid request.";
    }

    // Redirect to refresh (prevents form resubmit)
    header("Location: supervisor.php?" . ($success ? 'success=1' : 'error=1'));
    exit;
}

// Fetch all requests with details (for audit/history)
$stmt = $pdo->query("
    SELECT r.*, 
           d.quantity as current_stock, 
           u.username as requester_username,
           updater.username as updated_by_username
    FROM requests r 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN devices d ON d.type = r.device_type 
    LEFT JOIN users updater ON r.updated_by = updater.id 
    ORDER BY r.created_at DESC
");
$all_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Panel - iTrack Asset Tracking System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="site_footer.css">
    <style>
        /* Base styles (green/grey theme, matching dashboard) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif; background-color: #6cb87fff; }
        .header { background-color: white; border-bottom: 1px solid #e5e7eb; padding: 0.75rem 2rem; display: flex; align-items: center; justify-content: space-between; }
        .logo-section { display: flex; align-items: center; gap: 0.75rem; }
        .logo-circle { width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center; }
        .logo-icon { width: 2.5rem; height: 2.5rem; object-fit: contain; }
        .logo-text { font-size: 1.25rem; font-weight: 600; color: #1a1a1a; }
        .header-actions { display: flex; align-items: center; gap: 1rem; position: relative; }
        .icon-button { width: 2.5rem; height: 2.5rem; border-radius: 50%; background-color: transparent; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background-color 0.2s ease; }
        .icon-button:hover { background-color: #f3f4f6; }
        .icon-button i { width: 1.25rem; height: 1.25rem; color: #6b7280; }
        .user-button { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 9999px; background-color: transparent; border: none; cursor: pointer; transition: background-color 0.2s ease; }
        .user-button:hover { background-color: #f3f4f6; }
        .user-avatar { width: 2rem; height: 2rem; border-radius: 50%; background-color: #4CAF50; display: flex; align-items: center; justify-content: center; }
        .user-avatar i { width: 1.25rem; height: 1.25rem; color: white; }
        .user-dropdown { position: absolute; top: 100%; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); min-width: 180px; z-index: 1000; display: none; }
        .user-dropdown.show { display: block; }
        .dropdown-item { padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.5rem; color: #374151; text-decoration: none; border-bottom: 1px solid #f3f4f6; transition: background-color 0.2s; }
        .dropdown-item:hover { background-color: #f9fafb; }
        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item i { width: 1rem; color: #6b7280; }
        .nav-container { background-color: white; border-bottom: 1px solid #e5e7eb; padding: 0 2rem; }
        .nav-tabs { display: flex; gap: 2rem; overflow-x: auto; }
        .nav-tab { padding: 1rem 0; border: none; background: none; font-size: 0.95rem; font-weight: 500; color: #6b7280; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s ease; white-space: nowrap; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .nav-tab:hover { color: #4CAF50; }
        .nav-tab.active { color: #4CAF50; border-bottom-color: #4CAF50; }
        .nav-tab i { width: 1.25rem; height: 1.25rem; }
        .main-content { padding: 2rem; max-width: 1400px; margin: 0 auto; }
        .page-title { font-size: 1.875rem; font-weight: 700; color: #1a1a1a; margin-bottom: 2rem; }
        .message { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; text-align: center; font-weight: 500; }
        .success { background: #d1fae5; color: #4CAF50; border: 1px solid #a7f3d0; }
        .error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .requests-grid { display: grid; gap: 1.5rem; }
        .request-card { background: white; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #4CAF50; }
        .request-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .request-title { font-size: 1.25rem; font-weight: 600; color: #1a1a1a; }
        .request-status { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #d1fae5; color: #4CAF50; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .request-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .detail-item { display: flex; flex-direction: column; }
        .detail-label { font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem; }
        .detail-value { font-weight: 500; color: #1a1a1a; }
        .request-description { margin-bottom: 0.5rem; }
        .request-reason { margin-bottom: 1rem; }
        .actions { display: flex; gap: 0.5rem; }
        .action-btn { padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .approve-btn { background: #4CAF50; color: white; }
        .approve-btn:hover { background: #45a049; }
        .approve-btn:disabled { background: #9e9e9e; cursor: not-allowed; }
        .reject-btn { background: #f87171; color: white; }
        .reject-btn:hover { background: #ef4444; }
        .no-requests { text-align: center; padding: 3rem; color: #6b7280; }
        .no-requests i { font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; }
        .completed-actions { opacity: 0.5; font-style: italic; color: #6b7280; }
        @media (max-width: 768px) { 
            .header, .nav-container { padding: 0.75rem 1rem; } 
            .nav-tabs { gap: 1rem; } 
            .main-content { padding: 1.5rem 1rem; } 
            .page-title { font-size: 1.5rem; } 
            .request-details { grid-template-columns: 1fr; } 
            .actions { flex-direction: column; } 
        }
    </style>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <?php $active_page = 'approvals'; include 'site_header.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <h1 class="page-title">Request approvals</h1>
        <p class="page-intro">Review device requests and update their approval status.</p>

        <?php if (!empty($success)): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($all_requests)): ?>
            <div class="no-requests">
                <i class="fas fa-clipboard-list"></i>
                <h3>No requests yet</h3>
                <p>New device requests will appear here when they are submitted for approval.</p>
            </div>
        <?php else: ?>
            <div class="requests-grid">
                <?php foreach ($all_requests as $req): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div class="request-title">Request #<?php echo $req['id']; ?> - <?php echo htmlspecialchars($req['device_type']); ?></div>
                            <span class="request-status status-<?php echo strtolower($req['status']); ?>"><?php echo $req['status']; ?></span>
                        </div>
                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">Requester</span>
                                <span class="detail-value"><?php echo htmlspecialchars($req['requester_username']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Department</span>
                                <span class="detail-value"><?php echo htmlspecialchars($req['department']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Stock</span>
                                <span class="detail-value"><?php echo $req['current_stock'] ?? 'N/A'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date</span>
                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($req['created_at'])); ?></span>
                            </div>
                            <?php if ($req['updated_at']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Updated</span>
                                    <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($req['updated_at'])); ?> by <?php echo htmlspecialchars($req['updated_by_username'] ?? 'N/A'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="request-description">
                            <strong>Description:</strong> <?php echo htmlspecialchars($req['description'] ?? 'N/A'); ?>
                        </div>
                        <div class="request-reason">
                            <strong>Reason:</strong> <?php echo htmlspecialchars($req['reason']); ?>
                        </div>
                        <?php if ($req['status'] === 'Pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <input type="hidden" name="device_type" value="<?php echo htmlspecialchars($req['device_type']); ?>">
                                <div class="actions">
                                    <button type="submit" name="action" value="approve" class="action-btn approve-btn" 
                                            <?php echo ($req['current_stock'] ?? 0) < 1 ? 'disabled title="No stock available"' : ''; ?>>
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="submit" name="action" value="reject" class="action-btn reject-btn">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="actions completed-actions">
                                <span>Action completed</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'site_footer.php'; ?>
</body>
</html>