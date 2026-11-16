<?php
$active_page = $active_page ?? '';
$username = $_SESSION['username'] ?? 'Account';
$department = $_SESSION['department'] ?? '';
$role = $_SESSION['role'] ?? 'user';
$account_initial = strtoupper(substr((string)$username, 0, 1));
?>
<header class="app-header">
    <a class="brand-lockup" href="dashboard.php" aria-label="iTrack dashboard">
        <span class="brand-mark"><img src="images/logo.png.png" alt=""></span>
        <span class="brand-copy">
            <strong>iTrack</strong>
            <small>Asset &amp; fleet operations</small>
        </span>
    </a>
    <div class="app-header-tools">
        <span class="workspace-status"><i aria-hidden="true"></i>Operations workspace</span>
        <details class="account-menu">
            <summary class="account-summary">
                <span class="account-avatar"><?php echo htmlspecialchars($account_initial); ?></span>
                <span class="account-name">
                    <?php echo htmlspecialchars($username); ?>
                    <small><?php echo htmlspecialchars(ucfirst($role)); ?></small>
                </span>
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </summary>
            <div class="account-popover">
                <p><?php echo htmlspecialchars($department ?: 'iTrack account'); ?></p>
                <a href="logout.php"><i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i> Sign out</a>
            </div>
        </details>
    </div>
</header>
<nav class="site-navigation" aria-label="Main navigation">
    <div class="nav-inner">
        <a href="dashboard.php" class="nav-link<?php echo $active_page === 'dashboard' ? ' active' : ''; ?>"<?php echo $active_page === 'dashboard' ? ' aria-current="page"' : ''; ?>>
            <i class="fas fa-chart-pie" aria-hidden="true"></i> Overview
        </a>
        <a href="devices.php" class="nav-link<?php echo $active_page === 'inventory' ? ' active' : ''; ?>"<?php echo $active_page === 'inventory' ? ' aria-current="page"' : ''; ?>>
            <i class="fas fa-boxes-stacked" aria-hidden="true"></i> Inventory
        </a>
        <a href="request.php" class="nav-link<?php echo $active_page === 'requests' ? ' active' : ''; ?>"<?php echo $active_page === 'requests' ? ' aria-current="page"' : ''; ?>>
            <i class="fas fa-file-circle-plus" aria-hidden="true"></i> Device request
        </a>
        <?php if ($role === 'supervisor'): ?>
            <a href="supervisor.php" class="nav-link<?php echo $active_page === 'approvals' ? ' active' : ''; ?>"<?php echo $active_page === 'approvals' ? ' aria-current="page"' : ''; ?>>
                <i class="fas fa-clipboard-check" aria-hidden="true"></i> Approvals
            </a>
        <?php endif; ?>
    </div>
</nav>
