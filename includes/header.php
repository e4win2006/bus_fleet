<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

if (!isset($page)) $page = '';
if (!isset($page_title)) $page_title = 'FleetVision';
if (!isset($page_css)) $page_css = 'dashboard.css';
if (!isset($show_sidebar)) $show_sidebar = true;

// Get current username for profile display
$current_username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
$username_initials = strtoupper(substr($current_username, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($page_css); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<?php if ($show_sidebar): ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-icon">🚌</span>
                    <span class="logo-text">FleetVision</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <?php
                $role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : null;
                ?>

                <!-- Admin & Fleet Manager Navigation -->
                <?php if ($role === 'admin' || $role === 'fleet manager' || $role === 'fleet_manager'): ?>
                    <a href="dashboard.php" class="nav-item <?php echo ($page === 'dashboard') ? 'active' : ''; ?>" <?php if ($page === 'dashboard') echo 'aria-current="page"'; ?>>
                        <i data-lucide="layout-dashboard" class="nav-icon"></i>
                        <span class="nav-label">Dashboard</span>
                    </a>
                    <a href="fleet-overview.php" class="nav-item <?php echo ($page === 'fleet-overview') ? 'active' : ''; ?>" <?php if ($page === 'fleet-overview') echo 'aria-current="page"'; ?>>
                        <i data-lucide="bus-front" class="nav-icon"></i>
                        <span class="nav-label">Fleet Overview</span>
                    </a>
                    <a href="trips.php" class="nav-item <?php echo ($page === 'trips') ? 'active' : ''; ?>" <?php if ($page === 'trips') echo 'aria-current="page"'; ?>>
                        <i data-lucide="map" class="nav-icon"></i>
                        <span class="nav-label">Trips</span>
                    </a>
                    <a href="maintenance.php" class="nav-item <?php echo ($page === 'maintenance') ? 'active' : ''; ?>" <?php if ($page === 'maintenance') echo 'aria-current="page"'; ?>>
                        <i data-lucide="wrench" class="nav-icon"></i>
                        <span class="nav-label">Maintenance</span>
                    </a>
                    <a href="live-tracking.php" class="nav-item <?php echo ($page === 'live-tracking') ? 'active' : ''; ?>" <?php if ($page === 'live-tracking') echo 'aria-current="page"'; ?>>
                        <i data-lucide="cctv" class="nav-icon"></i>
                        <span class="nav-label">Live Tracking</span>
                    </a>
                    <a href="routes.php" class="nav-item <?php echo ($page === 'routes') ? 'active' : ''; ?>" <?php if ($page === 'routes') echo 'aria-current="page"'; ?>>
                        <i data-lucide="route" class="nav-icon"></i>
                        <span class="nav-label">Routes</span>
                    </a>
                <?php endif; ?>

                <!-- Admin Only Navigation -->
                <?php if ($role === 'admin'): ?>
                    <a href="reports.php" class="nav-item <?php echo ($page === 'reports') ? 'active' : ''; ?>" <?php if ($page === 'reports') echo 'aria-current="page"'; ?>>
                        <i data-lucide="bar-chart-3" class="nav-icon"></i>
                        <span class="nav-label">Reports</span>
                    </a>
                    <a href="users.php" class="nav-item <?php echo ($page === 'users') ? 'active' : ''; ?>" <?php if ($page === 'users') echo 'aria-current="page"'; ?>>
                        <i data-lucide="users" class="nav-icon"></i>
                        <span class="nav-label">Users</span>
                    </a>
                    <a href="buses.php" class="nav-item <?php echo ($page === 'buses') ? 'active' : ''; ?>" <?php if ($page === 'buses') echo 'aria-current="page"'; ?>>
                        <i data-lucide="bus" class="nav-icon"></i>
                        <span class="nav-label">Buses</span>
                    </a>
                    <a href="services.php" class="nav-item <?php echo ($page === 'services') ? 'active' : ''; ?>" <?php if ($page === 'services') echo 'aria-current="page"'; ?>>
                        <i data-lucide="hammer" class="nav-icon"></i>
                        <span class="nav-label">Services</span>
                    </a>
                    <a href="settings.php" class="nav-item <?php echo ($page === 'settings') ? 'active' : ''; ?>" <?php if ($page === 'settings') echo 'aria-current="page"'; ?>>
                        <i data-lucide="settings" class="nav-icon"></i>
                        <span class="nav-label">Settings</span>
                    </a>
                <?php endif; ?>

                <!-- Driver & Conductor Navigation -->
                <?php if ($role === 'driver' || $role === 'conductor'): ?>
                    <a href="my-trips.php" class="nav-item <?php echo ($page === 'my-trips') ? 'active' : ''; ?>" <?php if ($page === 'my-trips') echo 'aria-current="page"'; ?>>
                        <i data-lucide="map-pin" class="nav-icon"></i>
                        <span class="nav-label">My Trips</span>
                    </a>
                    <a href="my-bus.php" class="nav-item <?php echo ($page === 'my-bus') ? 'active' : ''; ?>" <?php if ($page === 'my-bus') echo 'aria-current="page"'; ?>>
                        <i data-lucide="bus" class="nav-icon"></i>
                        <span class="nav-label">My Bus</span>
                    </a>
                    <a href="driver-alerts.php" class="nav-item <?php echo ($page === 'driver-alerts') ? 'active' : ''; ?>" <?php if ($page === 'driver-alerts') echo 'aria-current="page"'; ?>>
                        <i data-lucide="alert-triangle" class="nav-icon"></i>
                        <span class="nav-label">Maintenance Alerts</span>
                    </a>
                <?php endif; ?>

                <!-- Normal User Navigation -->
                <?php if ($role === 'user'): ?>
                    <a href="my-schedule.php" class="nav-item <?php echo ($page === 'my-schedule') ? 'active' : ''; ?>" <?php if ($page === 'my-schedule') echo 'aria-current="page"'; ?>>
                        <i data-lucide="calendar" class="nav-icon"></i>
                        <span class="nav-label">My Schedule</span>
                    </a>
                <?php endif; ?>

                <!-- Global Communication (All Roles) -->
                <a href="chat.php" class="nav-item <?php echo ($page === 'chat') ? 'active' : ''; ?>" <?php if ($page === 'chat') echo 'aria-current="page"'; ?>>
                    <i data-lucide="message-square" class="nav-icon"></i>
                    <span class="nav-label">Messages</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $username_initials; ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo $current_username; ?></div>
                        <div class="user-role"><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></div>
                    </div>
                </div>
                <div style="margin-top: 16px; display: flex; gap: 8px;">
                    <button id="theme-toggle" style="flex: 1; padding: 8px; background: transparent; border: 1px solid #334155; color: #94a3b8; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="Toggle Theme">
                        <i data-lucide="moon" style="width: 16px; height: 16px;"></i>
                    </button>
                    <a href="logout.php" style="flex: 2; padding: 8px; text-align: center; color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 500; border: 1px solid #ef4444; border-radius: 6px; transition: all 0.2s;">
                        Logout
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
<?php endif; ?>
