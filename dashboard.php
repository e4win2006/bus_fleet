<?php
$page = 'dashboard';
$page_title = 'Dashboard - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;
include __DIR__ . '/includes/header.php';

require_once __DIR__ . '/includes/db.php';

$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : null;
$today = date('Y-m-d');
$current_username = $_SESSION['username'] ?? 'User';

// ── Database Queries ──────────────────────────────────────────────
// Buses
$total_buses = (int)$pdo->query("SELECT COUNT(*) FROM buses WHERE status != 'deleted'")->fetchColumn();
$active_buses = (int)$pdo->query("SELECT COUNT(*) FROM buses WHERE status = 'active'")->fetchColumn();
$idle_buses = (int)$pdo->query("SELECT COUNT(*) FROM buses WHERE status = 'idle'")->fetchColumn();
$maint_buses = (int)$pdo->query("SELECT COUNT(*) FROM buses WHERE status = 'maintenance'")->fetchColumn();

// Trips
$trips_today = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(start_time) = '$today'")->fetchColumn();
$ongoing_trips = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'ongoing'")->fetchColumn();
$completed_trips = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(start_time) = '$today' AND status = 'completed'")->fetchColumn();
$scheduled_trips_today = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(start_time) = '$today' AND status = 'scheduled'")->fetchColumn();

// Maintenance
$maint_due = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE status IN ('scheduled','ongoing')")->fetchColumn();
$maint_overdue = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE status = 'scheduled' AND service_date < '$today'")->fetchColumn();

// Routes
$total_routes = (int)$pdo->query("SELECT COUNT(*) FROM routes WHERE status = 'active'")->fetchColumn();
$total_route_distance = (float)$pdo->query("SELECT SUM(distance) FROM routes WHERE status = 'active'")->fetchColumn();

// Staff
$total_drivers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'driver' AND status != 'deleted'")->fetchColumn();
$total_conductors = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'conductor' AND status != 'deleted'")->fetchColumn();
$available_drivers = (int)$pdo->query("SELECT COUNT(*) FROM users u LEFT JOIN buses b ON b.driver_id = u.id WHERE u.role = 'driver' AND u.status = 'approved' AND b.id IS NULL")->fetchColumn();

// Latest Trips
$latest_trips = $pdo->query("
    SELECT t.*, b.bus_number, r.route_name, u.username as driver_name
    FROM trips t
    LEFT JOIN buses b ON t.bus_id = b.id
    LEFT JOIN routes r ON t.route_id = r.id
    LEFT JOIN users u ON t.driver_id = u.id
    ORDER BY t.start_time DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Buses on Journey
$buses_on_journey = $pdo->query("
    SELECT b.bus_number, b.make, b.model, r.route_name, u.username as driver_name
    FROM trips t
    JOIN buses b ON t.bus_id = b.id
    JOIN routes r ON t.route_id = r.id
    LEFT JOIN users u ON t.driver_id = u.id
    WHERE t.status = 'ongoing'
")->fetchAll(PDO::FETCH_ASSOC);

?>
<style>
/* Refined Attractive Dashboard Styles */
.dashboard-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; margin-bottom: 32px; }
.col-span-3 { grid-column: span 3; }
.col-span-4 { grid-column: span 4; }
.col-span-6 { grid-column: span 6; }
.col-span-8 { grid-column: span 8; }
.col-span-12 { grid-column: span 12; }

@media (max-width: 1200px) {
    .col-span-3 { grid-column: span 6; }
    .col-span-4 { grid-column: span 6; }
    .col-span-8 { grid-column: span 12; }
}
@media (max-width: 768px) {
    .col-span-3, .col-span-4, .col-span-6, .col-span-8 { grid-column: span 12; }
}

/* Glassmorphism / Modern Card Styling */
.stat-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
.stat-card.primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; }
.stat-card.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; }
.stat-card.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; }
.stat-card.danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none; }

.stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.stat-title { font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
.stat-card.primary .stat-title, .stat-card.warning .stat-title, .stat-card.success .stat-title, .stat-card.danger .stat-title { color: rgba(255,255,255,0.8); }
.stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #3b82f6; }
.stat-card.primary .stat-icon { background: rgba(255,255,255,0.2); color: white; }
.stat-card.warning .stat-icon { background: rgba(255,255,255,0.2); color: white; }
.stat-card.success .stat-icon { background: rgba(255,255,255,0.2); color: white; }
.stat-card.danger .stat-icon { background: rgba(255,255,255,0.2); color: white; }

.stat-value { font-size: 32px; font-weight: 700; line-height: 1.2; margin-bottom: 8px; }
.stat-desc { font-size: 13px; color: #94a3b8; font-weight: 500; }
.stat-card.primary .stat-desc, .stat-card.warning .stat-desc, .stat-card.success .stat-desc, .stat-card.danger .stat-desc { color: rgba(255,255,255,0.9); }

/* Panel Styling */
.panel {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.panel-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}
.panel-title { font-size: 16px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.panel-body { padding: 24px; flex: 1; overflow-y: auto; }

/* Mini Lists */
.feed-list { list-style: none; padding: 0; margin: 0; }
.feed-item { padding: 12px 0; border-bottom: 1px dashed #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.feed-item:last-child { border-bottom: none; }
.feed-main { display: flex; flex-direction: column; gap: 4px; }
.feed-title { font-weight: 600; color: #334155; font-size: 14px; }
.feed-sub { font-size: 12px; color: #64748b; }
.feed-right { text-align: right; }

.pill { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.pill.ongoing { background: #dbeafe; color: #1d4ed8; }
.pill.scheduled { background: #fef3c7; color: #d97706; }
.pill.completed { background: #dcfce3; color: #15803d; }
.pill.cancelled { background: #fee2e2; color: #ef4444; }

/* Progress Bars */
.prog-container { margin-bottom: 16px; }
.prog-header { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #475569; }
.prog-bar-bg { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
.prog-bar-fill { height: 100%; border-radius: 4px; }
.bg-blue { background: #3b82f6; }
.bg-green { background: #10b981; }
.bg-yellow { background: #f59e0b; }
.bg-red { background: #ef4444; }

/* Quick Actions Mini */
.mini-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.mini-action-btn { 
    display: flex; align-items: center; justify-content: center; gap: 8px; 
    padding: 12px; background: #f1f5f9; border-radius: 10px; 
    color: #475569; font-weight: 600; font-size: 13px; text-decoration: none;
    transition: all 0.2s ease;
}
.mini-action-btn:hover { background: #3b82f6; color: white; transform: translateY(-2px); }
</style>

<header class="top-header">
    <div class="header-left">
        <div class="greeting-section">
            <h1 class="greeting-title">Good <?php $h=(int)date('H'); echo $h<12?'Morning':($h<17?'Afternoon':'Evening'); ?>, <?php echo htmlspecialchars($current_username); ?> 👋</h1>
            <p class="greeting-subtitle">Comprehensive real-time overview of your fleet operations</p>
        </div>
    </div>
    <div class="header-right">
        <div class="date-display">📅 <?php echo date('l, d F Y'); ?></div>
    </div>
</header>

<section class="content-section">

<?php if ($role === 'admin' || $role === 'fleet manager' || $role === 'fleet_manager'): ?>

    <!-- Top Level Stats -->
    <div class="dashboard-grid">
        <!-- 1 -->
        <div class="stat-card primary col-span-3">
            <div class="stat-header">
                <div class="stat-title">Fleet Utilization</div>
                <div class="stat-icon"><i data-lucide="bus"></i></div>
            </div>
            <div class="stat-value"><?php echo $active_buses; ?> <span style="font-size:16px;font-weight:500;">/ <?php echo $total_buses; ?></span></div>
            <div class="stat-desc">Buses currently active</div>
        </div>

        <!-- 2 -->
        <div class="stat-card success col-span-3">
            <div class="stat-header">
                <div class="stat-title">Ongoing Trips</div>
                <div class="stat-icon"><i data-lucide="map"></i></div>
            </div>
            <div class="stat-value"><?php echo $ongoing_trips; ?></div>
            <div class="stat-desc">Out of <?php echo $trips_today; ?> total today</div>
        </div>

        <!-- 3 -->
        <div class="stat-card col-span-3">
            <div class="stat-header">
                <div class="stat-title">Staff Available</div>
                <div class="stat-icon"><i data-lucide="users"></i></div>
            </div>
            <div class="stat-value" style="color:#1e293b;"><?php echo $available_drivers; ?> <span style="font-size:16px;font-weight:500;color:#94a3b8;">/ <?php echo $total_drivers; ?></span></div>
            <div class="stat-desc">Drivers unassigned</div>
        </div>

        <!-- 4 -->
        <div class="stat-card <?php echo $maint_overdue > 0 ? 'danger' : 'warning'; ?> col-span-3">
            <div class="stat-header">
                <div class="stat-title">Maintenance Alerts</div>
                <div class="stat-icon"><i data-lucide="alert-triangle"></i></div>
            </div>
            <div class="stat-value"><?php echo $maint_overdue > 0 ? $maint_overdue : $maint_due; ?></div>
            <div class="stat-desc"><?php echo $maint_overdue > 0 ? 'Overdue tasks!' : 'Upcoming tasks'; ?></div>
        </div>
    </div>

    <!-- Detailed Panels -->
    <div class="dashboard-grid">
        
        <!-- Left Column (Trips & Activity) -->
        <div class="col-span-8" style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- Live On Journey -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i data-lucide="activity" style="color:#10b981;"></i> Live: Buses On Journey</div>
                    <span class="pill ongoing"><?php echo count($buses_on_journey); ?> Active</span>
                </div>
                <div class="panel-body">
                    <?php if (count($buses_on_journey) > 0): ?>
                    <table class="data-table" style="margin:0; box-shadow:none; border:none;">
                        <thead>
                            <tr>
                                <th style="background:transparent;">Bus</th>
                                <th style="background:transparent;">Route</th>
                                <th style="background:transparent;">Driver</th>
                                <th style="background:transparent;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($buses_on_journey as $boj): ?>
                            <tr>
                                <td style="font-weight:600;"><?php echo htmlspecialchars($boj['bus_number']); ?></td>
                                <td><?php echo htmlspecialchars($boj['route_name']); ?></td>
                                <td><?php echo htmlspecialchars($boj['driver_name'] ?? '—'); ?></td>
                                <td><span style="display:inline-flex; align-items:center; gap:6px; color:#10b981; font-weight:600; font-size:12px;"><span style="width:8px;height:8px;border-radius:50%;background:#10b981;animation:pulse 2s infinite;"></span> Moving</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px 0; color: #94a3b8;">
                        <i data-lucide="coffee" style="width:48px; height:48px; opacity:0.5; margin-bottom:12px;"></i>
                        <p>No buses are currently on a journey.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Trips Feed -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i data-lucide="clock"></i> Recent Trip Activity</div>
                    <a href="trips.php" style="font-size:13px; font-weight:600; color:#3b82f6; text-decoration:none;">View All</a>
                </div>
                <div class="panel-body">
                    <?php if(count($latest_trips) > 0): ?>
                    <ul class="feed-list">
                        <?php foreach($latest_trips as $lt): ?>
                        <li class="feed-item">
                            <div class="feed-main">
                                <div class="feed-title">Bus <?php echo htmlspecialchars($lt['bus_number'] ?? 'Unknown'); ?> — <?php echo htmlspecialchars($lt['route_name'] ?? 'Unknown Route'); ?></div>
                                <div class="feed-sub">Driver: <?php echo htmlspecialchars($lt['driver_name'] ?? 'Unassigned'); ?> • <?php echo date('M d, g:i A', strtotime($lt['start_time'])); ?></div>
                            </div>
                            <div class="feed-right">
                                <span class="pill <?php echo $lt['status']; ?>"><?php echo ucfirst($lt['status']); ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p style="color:#94a3b8; text-align:center; padding: 20px;">No trips logged yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Right Column (Analytics & Quick Actions) -->
        <div class="col-span-4" style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- Trip Progress -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i data-lucide="bar-chart-2"></i> Today's Trip Progress</div>
                </div>
                <div class="panel-body">
                    <?php 
                    $pct_comp = $trips_today > 0 ? round(($completed_trips / $trips_today)*100) : 0;
                    $pct_ong = $trips_today > 0 ? round(($ongoing_trips / $trips_today)*100) : 0;
                    $pct_sch = $trips_today > 0 ? round(($scheduled_trips_today / $trips_today)*100) : 0;
                    ?>
                    
                    <div class="prog-container">
                        <div class="prog-header"><span>Completed (<?php echo $completed_trips; ?>)</span> <span><?php echo $pct_comp; ?>%</span></div>
                        <div class="prog-bar-bg"><div class="prog-bar-fill bg-green" style="width:<?php echo $pct_comp; ?>%"></div></div>
                    </div>
                    
                    <div class="prog-container">
                        <div class="prog-header"><span>Ongoing (<?php echo $ongoing_trips; ?>)</span> <span><?php echo $pct_ong; ?>%</span></div>
                        <div class="prog-bar-bg"><div class="prog-bar-fill bg-blue" style="width:<?php echo $pct_ong; ?>%"></div></div>
                    </div>

                    <div class="prog-container">
                        <div class="prog-header"><span>Scheduled (<?php echo $scheduled_trips_today; ?>)</span> <span><?php echo $pct_sch; ?>%</span></div>
                        <div class="prog-bar-bg"><div class="prog-bar-fill bg-yellow" style="width:<?php echo $pct_sch; ?>%"></div></div>
                    </div>
                    
                    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="font-size:13px; color:#64748b; font-weight:500;">Total Active Routes</span>
                            <span style="font-weight:700; color:#1e293b;"><?php echo $total_routes; ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="font-size:13px; color:#64748b; font-weight:500;">Network Distance</span>
                            <span style="font-weight:700; color:#1e293b;"><?php echo $total_route_distance; ?> km</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Overview -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i data-lucide="users"></i> Staff Overview</div>
                </div>
                <div class="panel-body" style="display:flex; gap:16px;">
                    <div style="flex:1; background:#f8fafc; padding:16px; border-radius:12px; text-align:center; border:1px solid #e2e8f0;">
                        <i data-lucide="user-check" style="color:#3b82f6; margin-bottom:8px;"></i>
                        <div style="font-size:24px; font-weight:700; color:#1e293b;"><?php echo $total_drivers; ?></div>
                        <div style="font-size:12px; font-weight:600; color:#64748b; text-transform:uppercase;">Drivers</div>
                    </div>
                    <div style="flex:1; background:#f8fafc; padding:16px; border-radius:12px; text-align:center; border:1px solid #e2e8f0;">
                        <i data-lucide="ticket" style="color:#f59e0b; margin-bottom:8px;"></i>
                        <div style="font-size:24px; font-weight:700; color:#1e293b;"><?php echo $total_conductors; ?></div>
                        <div style="font-size:12px; font-weight:600; color:#64748b; text-transform:uppercase;">Conductors</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i data-lucide="zap"></i> Quick Actions</div>
                </div>
                <div class="panel-body">
                    <div class="mini-actions">
                        <a href="buses.php" class="mini-action-btn"><i data-lucide="bus" style="width:16px;"></i> Buses</a>
                        <a href="trips.php" class="mini-action-btn"><i data-lucide="map" style="width:16px;"></i> Trips</a>
                        <a href="routes.php" class="mini-action-btn"><i data-lucide="route" style="width:16px;"></i> Routes</a>
                        <a href="maintenance.php" class="mini-action-btn"><i data-lucide="wrench" style="width:16px;"></i> Maint.</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

<!-- Driver / Conductor View -->
<?php elseif ($role === 'driver' || $role === 'conductor'): 
    include __DIR__ . '/includes/weather_alert.php';
    $my_trips = $pdo->query("
        SELECT t.*, b.bus_number, r.route_name 
        FROM trips t 
        JOIN buses b ON t.bus_id = b.id 
        JOIN routes r ON t.route_id = r.id 
        WHERE t.".($role==='driver'?'driver_id':'conductor_id')." = {$_SESSION['user_id']} AND DATE(t.start_time) = '$today'
    ")->fetchAll(PDO::FETCH_ASSOC);
    $my_trips_total = count($my_trips);
    $my_trips_done = count(array_filter($my_trips, fn($t) => $t['status'] === 'completed'));
?>
    <div class="dashboard-grid">
        <div class="stat-card primary col-span-6">
            <div class="stat-header">
                <div class="stat-title">My Trips Today</div>
                <div class="stat-icon"><i data-lucide="map"></i></div>
            </div>
            <div class="stat-value"><?php echo $my_trips_total; ?></div>
            <div class="stat-desc"><?php echo $my_trips_done; ?> Completed</div>
        </div>
    </div>
    
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title"><i data-lucide="calendar"></i> Today's Schedule</div>
        </div>
        <div class="panel-body">
            <?php if ($my_trips_total > 0): ?>
            <ul class="feed-list">
                <?php foreach($my_trips as $t): ?>
                <li class="feed-item">
                    <div class="feed-main">
                        <div class="feed-title"><?php echo htmlspecialchars($t['route_name']); ?> (Bus <?php echo htmlspecialchars($t['bus_number']); ?>)</div>
                        <div class="feed-sub"><?php echo date('g:i A', strtotime($t['start_time'])); ?></div>
                    </div>
                    <div class="feed-right">
                        <span class="pill <?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p style="color:#94a3b8;">No trips assigned for today.</p>
            <?php endif; ?>
        </div>
    </div>

<!-- Normal User -->
<?php elseif ($role === 'user'): include __DIR__ . '/includes/weather_alert.php'; ?>
    <div class="panel" style="padding: 40px; text-align: center;">
        <i data-lucide="info" style="width:48px; height:48px; color:#3b82f6; margin-bottom:16px;"></i>
        <h2>Welcome to FleetVision</h2>
        <p style="color:#64748b; font-size:16px; margin-top:10px;">Your account is pending role assignment. Please contact your administrator.</p>
    </div>
<?php endif; ?>

</section>

<style>
@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
