<?php
$page = 'dashboard';
$page_title = 'Dashboard - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;
include __DIR__ . '/includes/header.php';

require_once __DIR__ . '/includes/db.php';

$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : null;
$today = date('Y-m-d');

// ── Real KPI queries ──────────────────────────────────────────────────────
$total_buses = (int)$pdo->query("SELECT COUNT(*) FROM buses WHERE status != 'deleted'")->fetchColumn();
$active_buses = (int)$pdo->query("SELECT COUNT(*) FROM buses WHERE status = 'active'")->fetchColumn();
$idle_buses = (int)$pdo->query("SELECT COUNT(*) FROM buses WHERE status = 'idle'")->fetchColumn();
$maint_buses = (int)$pdo->query("SELECT COUNT(*) FROM buses WHERE status = 'maintenance'")->fetchColumn();

$trips_today = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(start_time) = '$today'")->fetchColumn();
$completed_trips = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(start_time) = '$today' AND status = 'completed'")->fetchColumn();

$maint_due = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE status IN ('scheduled','ongoing')")->fetchColumn();
$maint_overdue = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE status = 'scheduled' AND service_date < '$today'")->fetchColumn();

$total_routes = (int)$pdo->query("SELECT COUNT(*) FROM routes WHERE status = 'active'")->fetchColumn();
?>

<header class="top-header">
    <div class="header-left">
        <div class="greeting-section">
            <h1 class="greeting-title">Good <?php
$hour = (int)date('H');
echo $hour < 12 ? 'Morning' : ($hour < 17 ? 'Afternoon' : 'Evening');
?>, <?php echo $current_username; ?> 👋</h1>
            <p class="greeting-subtitle">Here's what's happening with your fleet today</p>
        </div>
    </div>
    <div class="header-right">
        <div class="date-display">📅 <?php echo date('D, d M Y'); ?></div>
    </div>
</header>

<section class="content-section">

<?php if ($role === 'admin' || $role === 'fleet manager' || $role === 'fleet_manager'): ?>
    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-header">
                <i data-lucide="bus" class="kpi-icon"></i>
                <span class="kpi-label">Total Buses</span>
            </div>
            <div class="kpi-value"><?php echo $total_buses; ?></div>
            <div class="kpi-footer">
                <span class="kpi-detail"><?php echo $active_buses; ?> Active, <?php echo $idle_buses; ?> Idle</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <i data-lucide="check-circle" class="kpi-icon"></i>
                <span class="kpi-label">Active Buses</span>
            </div>
            <div class="kpi-value"><?php echo $active_buses; ?></div>
            <div class="kpi-footer">
                <span class="kpi-detail"><?php echo $total_buses - $active_buses; ?> not active</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <i data-lucide="map" class="kpi-icon"></i>
                <span class="kpi-label">Trips Today</span>
            </div>
            <div class="kpi-value"><?php echo $trips_today; ?></div>
            <div class="kpi-footer">
                <span class="kpi-detail"><?php echo $completed_trips; ?> Completed</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <i data-lucide="route" class="kpi-icon"></i>
                <span class="kpi-label">Active Routes</span>
            </div>
            <div class="kpi-value"><?php echo $total_routes; ?></div>
            <div class="kpi-footer">
                <span class="kpi-detail">Configured routes</span>
            </div>
        </div>

        <div class="kpi-card <?php echo $maint_due > 0 ? 'alert' : ''; ?>">
            <div class="kpi-header">
                <i data-lucide="alert-triangle" class="kpi-icon"></i>
                <span class="kpi-label">Maintenance Due</span>
            </div>
            <div class="kpi-value"><?php echo $maint_due; ?></div>
            <div class="kpi-footer">
                <span class="kpi-detail"><?php echo $maint_overdue; ?> Overdue</span>
            </div>
        </div>
    </div>

    <!-- Fleet Status -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Fleet Status Distribution</h3>
            </div>
            <div class="chart-content">
                <div class="status-bars">
                    <?php
    $statuses = [
        ['label' => 'Active', 'count' => $active_buses, 'class' => 'active'],
        ['label' => 'Idle', 'count' => $idle_buses, 'class' => 'idle'],
        ['label' => 'Maintenance', 'count' => $maint_buses, 'class' => 'maintenance'],
    ];
    foreach ($statuses as $s):
        $pct = $total_buses > 0 ? round(($s['count'] / $total_buses) * 100) : 0;
?>
                    <div class="status-bar-item">
                        <div class="status-bar-label">
                            <span class="status-dot <?php echo $s['class']; ?>"></span>
                            <span><?php echo $s['label']; ?></span>
                        </div>
                        <div class="status-bar-progress">
                            <div class="status-bar-fill <?php echo $s['class']; ?>" style="width:<?php echo $pct; ?>%"></div>
                        </div>
                        <span class="status-bar-value"><?php echo $s['count']; ?> buses</span>
                    </div>
                    <?php
    endforeach; ?>
                </div>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Today's Activity</h3>
            </div>
            <div class="chart-content">
                <div class="activity-stats">
                    <div class="activity-item">
                        <div class="activity-number"><?php echo $trips_today; ?></div>
                        <div class="activity-label">Total Trips</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-number"><?php echo $completed_trips; ?></div>
                        <div class="activity-label">Completed</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-number"><?php echo $trips_today - $completed_trips; ?></div>
                        <div class="activity-label">Pending</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <?php if ($role === 'admin'): ?>
    <div class="quick-actions" style="margin-top: 32px;">
        <h3 class="section-subtitle">Admin Quick Actions</h3>
        <div class="actions-grid">
            <a href="buses.php" class="action-card">
                <i data-lucide="bus" class="action-icon"></i>
                <span class="action-label">Manage Buses</span>
            </a>
            <a href="routes.php" class="action-card">
                <i data-lucide="route" class="action-icon"></i>
                <span class="action-label">Manage Routes</span>
            </a>
            <a href="trips.php" class="action-card">
                <i data-lucide="map" class="action-icon"></i>
                <span class="action-label">Manage Trips</span>
            </a>
            <a href="services.php" class="action-card">
                <i data-lucide="hammer" class="action-icon"></i>
                <span class="action-label">Service Records</span>
            </a>
            <a href="maintenance.php" class="action-card">
                <i data-lucide="wrench" class="action-icon"></i>
                <span class="action-label">Maintenance</span>
            </a>
            <a href="reports.php" class="action-card">
                <i data-lucide="bar-chart-3" class="action-icon"></i>
                <span class="action-label">Reports</span>
            </a>
        </div>
    </div>
    <?php
    elseif ($role === 'fleet manager' || $role === 'fleet_manager'): ?>
    <div class="quick-actions" style="margin-top: 32px;">
        <h3 class="section-subtitle">Operational Actions</h3>
        <div class="actions-grid">
            <a href="fleet-overview.php" class="action-card">
                <i data-lucide="bus-front" class="action-icon"></i>
                <span class="action-label">View Fleet</span>
            </a>
            <a href="trips.php" class="action-card">
                <i data-lucide="map" class="action-icon"></i>
                <span class="action-label">Manage Trips</span>
            </a>
            <a href="maintenance.php" class="action-card">
                <i data-lucide="wrench" class="action-icon"></i>
                <span class="action-label">Maintenance</span>
            </a>
        </div>
    </div>
    <?php
    endif; ?>

<!-- Driver / Conductor -->
<?php
elseif ($role === 'driver' || $role === 'conductor'):
    // Fetch this driver's trips today
    $my_trips_total = (int)$pdo->prepare("SELECT COUNT(*) FROM trips WHERE driver_id = ? AND DATE(start_time) = ?")->execute([$_SESSION['user_id'], $today]) ? $pdo->query("SELECT COUNT(*) FROM trips WHERE driver_id = {$_SESSION['user_id']} AND DATE(start_time) = '$today'")->fetchColumn() : 0;
    $my_trips_done = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE driver_id = {$_SESSION['user_id']} AND DATE(start_time) = '$today' AND status = 'completed'")->fetchColumn();
    include __DIR__ . '/includes/weather_alert.php';
?>
    <div class="kpi-grid">
        <div class="kpi-card" style="border-left: 4px solid #3b82f6;">
            <div class="kpi-header">
                <i data-lucide="map" class="kpi-icon"></i>
                <span class="kpi-label">Today's Trips</span>
            </div>
            <div class="kpi-value"><?php echo $my_trips_total; ?></div>
            <div class="kpi-footer"><span class="kpi-detail"><?php echo $my_trips_done; ?> Completed</span></div>
        </div>
        <div class="kpi-card" style="border-left: 4px solid #f59e0b; <?php echo $maint_overdue > 0 ? 'background:#fffbeb;' : ''; ?>">
            <div class="kpi-header">
                <i data-lucide="alert-triangle" class="kpi-icon"></i>
                <span class="kpi-label">Fleet Maintenance Alerts</span>
            </div>
            <div class="kpi-value"><?php echo $maint_overdue; ?></div>
            <div class="kpi-footer"><span class="kpi-detail">Overdue services</span></div>
        </div>
    </div>

<!-- Normal User -->
<?php
elseif ($role === 'user'):
    include __DIR__ . '/includes/weather_alert.php';
?>
    <div class="user-form-card">
        <h3>Welcome, <?php echo $current_username; ?></h3>
        <p style="color:#64748b; margin-top:10px;">Your account is active. Contact your fleet manager for trip assignments.</p>
    </div>
<?php
endif; ?>

</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
