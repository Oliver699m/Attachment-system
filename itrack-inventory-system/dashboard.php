<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Total devices in stock
$stmt = $pdo->query("SELECT SUM(quantity) as total FROM devices");
$total_stock = $stmt->fetch()['total'] ?? 0;

// Pending requests (all)
$stmt = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE status = 'Pending'");
$pending_requests = $stmt->fetch()['count'] ?? 0;

// Low stock (quantity < 5)
$stmt = $pdo->query("SELECT COUNT(*) as count FROM devices WHERE quantity < 5");
$low_stock = $stmt->fetch()['count'] ?? 0;

// Welcome message after signup
$welcome_msg = '';
if (isset($_GET['welcome'])) {
    $welcome_msg = "Welcome, {$_SESSION['username']}! Your account is set up and ready.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iTrack Assets - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="site_footer.css">
    <style>
        /* Reset and base styles (your design, green/grey theme) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif; -webkit-font-smoothing: antialiased; background-color: #6cb87fff; }
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
        /* User Dropdown */
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
        .welcome-banner { background: #d1fae5; color: #4CAF50; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; text-align: center; font-weight: 500; border: 1px solid #a7f3d0; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .stats-card { background-color: white; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid; }
        .stats-card.green { border-left-color: #4CAF50; }
        .stats-card.yellow { border-left-color: #d97706; }
        .stats-card.gray { border-left-color: #d1d5db; }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .card-title { font-size: 0.95rem; font-weight: 500; color: #6b7280; }
        .card-icon { width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; }
        .card-icon.green { background-color: #d1fae5; }
        .card-icon.yellow { background-color: #fef3c7; }
        .card-icon.gray { background-color: #f3f4f6; }
        .card-icon i { width: 1.5rem; height: 1.5rem; }
        .card-icon.green i { color: #4CAF50; }
        .card-icon.yellow i { color: #d97706; }
        .card-icon.gray i { color: #6b7280; }
        .card-value { font-size: 3rem; font-weight: 700; color: #1a1a1a; margin-bottom: 0.25rem; }
        .card-description { font-size: 0.875rem; color: #6b7280; }
        .card-description.warning { color: #d97706; }
        @media (max-width: 768px) { 
            .header, .nav-container { padding: 0.75rem 1rem; } 
            .nav-tabs { gap: 1rem; } 
            .main-content { padding: 1.5rem 1rem; } 
            .page-title { font-size: 1.5rem; } 
            .dashboard-grid { grid-template-columns: 1fr; } 
            .logo-text { font-size: 1rem; } 
        }
    </style>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <?php $active_page = 'dashboard'; include 'site_header.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <h1 class="page-title">Dashboard Overview</h1>
        <p class="page-intro">A clear view of tracking equipment and requests across your operation.</p>
        
        <?php if (!empty($welcome_msg)): ?>
            <div class="welcome-banner"><?php echo htmlspecialchars($welcome_msg); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Total Devices Card -->
            <div class="stats-card green">
                <div class="card-header">
                    <div>
                        <div class="card-title">Total Devices</div>
                    </div>
                    <div class="card-icon green">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo $total_stock; ?></div>
                <div class="card-description">Across all categories</div>
            </div>

            <!-- Pending Requests Card -->
            <div class="stats-card yellow">
                <div class="card-header">
                    <div>
                        <div class="card-title">Pending Requests</div>
                    </div>
                    <div class="card-icon yellow">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo $pending_requests; ?></div>
                <div class="card-description warning">Awaiting approval</div>
            </div>

            <!-- Low Stock Card -->
            <div class="stats-card gray">
                <div class="card-header">
                    <div>
                        <div class="card-title">Low Stock</div>
                    </div>
                    <div class="card-icon gray">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo $low_stock; ?></div>
                <div class="card-description">Devices need restocking</div>
            </div>
        </div>
    </main>

    <?php include 'site_footer.php'; ?>
</body>
</html>