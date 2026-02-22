<?php
$page = 'dashboard';
$page_title = 'Dashboard - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;
include __DIR__ . '/includes/header.php';
?>

            <!-- Header -->
            <header class="top-header">
                <div class="header-left">
                    <div class="greeting-section">
                        <h1 class="greeting-title">Good Morning, <?php echo $current_username; ?>! 👋</h1>
                        <p class="greeting-subtitle">Here's what's happening with your fleet today</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="date-display">📅</div>
                </div>
            </header>

<?php
$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : null;
?>
            <!-- Dashboard Section -->
            <section class="content-section">
                
                <?php if ($role === 'admin' || $role === 'fleet manager' || $role === 'fleet_manager'): ?>
                    <!-- KPI Cards (Admin & Fleet Manager) -->
                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <div class="kpi-header">
                                <i data-lucide="bus" class="kpi-icon"></i>
                                <span class="kpi-label">Total Buses</span>
                            </div>
                            <div class="kpi-value">48</div>
                            <div class="kpi-footer">
                                <span class="kpi-trend positive">↑ 2 from last month</span>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-header">
                                <i data-lucide="check-circle" class="kpi-icon"></i>
                                <span class="kpi-label">Active Today</span>
                            </div>
                            <div class="kpi-value">42</div>
                            <div class="kpi-footer">
                                <span class="kpi-detail">6 Inactive</span>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-header">
                                <i data-lucide="map" class="kpi-icon"></i>
                                <span class="kpi-label">Trips Today</span>
                            </div>
                            <div class="kpi-value">156</div>
                            <div class="kpi-footer">
                                <span class="kpi-trend positive">↑ 12% vs yesterday</span>
                            </div>
                        </div>
                        <?php if ($role === 'admin'): ?>
                        <div class="kpi-card">
                            <div class="kpi-header">
                                <i data-lucide="indian-rupee" class="kpi-icon"></i>
                                <span class="kpi-label">Revenue Today</span>
                            </div>
                            <div class="kpi-value">₹4,280</div>
                            <div class="kpi-footer">
                                <span class="kpi-trend positive">↑ 8% vs yesterday</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="kpi-card">
                            <div class="kpi-header">
                                <i data-lucide="fuel" class="kpi-icon"></i>
                                <span class="kpi-label">Fuel Today</span>
                            </div>
                            <div class="kpi-value">1,240L</div>
                            <div class="kpi-footer">
                                <span class="kpi-detail">Avg: 29.5L per bus</span>
                            </div>
                        </div>
                        <div class="kpi-card alert">
                            <div class="kpi-header">
                                <i data-lucide="alert-triangle" class="kpi-icon"></i>
                                <span class="kpi-label">Maintenance Due</span>
                            </div>
                            <div class="kpi-value">7</div>
                            <div class="kpi-footer">
                                <span class="kpi-detail">3 Overdue</span>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Placeholder -->
                    <div class="charts-row">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3 class="chart-title">Fleet Status Distribution</h3>
                            </div>
                            <div class="chart-content">
                                <div class="status-bars">
                                    <div class="status-bar-item">
                                        <div class="status-bar-label">
                                            <span class="status-dot active"></span>
                                            <span>Active</span>
                                        </div>
                                        <div class="status-bar-progress">
                                            <div class="status-bar-fill active" data-percent="87.5%"></div>
                                        </div>
                                        <span class="status-bar-value">42 buses</span>
                                    </div>
                                    <!-- Additional status bars omitted for brevity, but exist in DOM structure from CSS -->
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
                                        <div class="activity-number">156</div>
                                        <div class="activity-label">Total Trips</div>
                                    </div>
                                    <div class="activity-item">
                                        <div class="activity-number">142</div>
                                        <div class="activity-label">Completed</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <!-- Driver & Conductor Section -->
                <?php elseif ($role === 'driver' || $role === 'conductor'): ?>
                    <?php include __DIR__ . '/includes/weather_alert.php'; ?>
                    <div class="kpi-grid">
                        <div class="kpi-card" style="border-left: 4px solid #3b82f6;">
                            <div class="kpi-header">
                                <i data-lucide="map" class="kpi-icon"></i>
                                <span class="kpi-label">Today's Assigned Trips</span>
                            </div>
                            <div class="kpi-value">4</div>
                            <div class="kpi-footer">
                                <span class="kpi-detail">2 Completed, 2 Pending</span>
                            </div>
                        </div>
                        <div class="kpi-card" style="border-left: 4px solid #10b981;">
                            <div class="kpi-header">
                                <i data-lucide="fuel" class="kpi-icon"></i>
                                <span class="kpi-label">Bus Fuel Status</span>
                            </div>
                            <div class="kpi-value">68%</div>
                            <div class="kpi-footer">
                                <span class="kpi-detail">Est. range: 320km</span>
                            </div>
                        </div>
                        <div class="kpi-card alert" style="border-left: 4px solid #f59e0b;">
                            <div class="kpi-header">
                                <i data-lucide="alert-triangle" class="kpi-icon"></i>
                                <span class="kpi-label">Service Reminders</span>
                            </div>
                            <div class="kpi-value">1</div>
                            <div class="kpi-footer">
                                <span class="kpi-detail">Oil change due in 200km</span>
                            </div>
                        </div>
                    </div>
                
                <!-- Normal User Section -->
                <?php elseif ($role === 'user'): ?>
                    <?php include __DIR__ . '/includes/weather_alert.php'; ?>
                    <div class="user-form-card">
                        <h3>My Schedule</h3>
                        <p style="color: #64748b; margin-top: 10px;">You are scheduled for the 8:00 AM route to Downtown.</p>
                    </div>
                <?php endif; ?>

                <!-- Quick Actions (Only for Admin) -->
                <?php if ($role === 'admin'): ?>
                <div class="quick-actions" style="margin-top: 32px;">
                    <h3 class="section-subtitle">Admin Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="users.php" class="action-card">
                            <i data-lucide="users" class="action-icon"></i>
                            <span class="action-label">Manage Users</span>
                        </a>
                        <a href="buses.php" class="action-card">
                            <i data-lucide="bus" class="action-icon"></i>
                            <span class="action-label">Manage Buses</span>
                        </a>
                        <a href="services.php" class="action-card">
                            <i data-lucide="hammer" class="action-icon"></i>
                            <span class="action-label">Manage Services</span>
                        </a>
                        <a href="reports.php" class="action-card">
                            <i data-lucide="bar-chart-3" class="action-icon"></i>
                            <span class="action-label">View Full Reports</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Quick Actions (Only for Fleet Manager) -->
                <?php if ($role === 'fleet manager' || $role === 'fleet_manager'): ?>
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
                            <span class="action-label">Schedule Maintenance</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

            </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
