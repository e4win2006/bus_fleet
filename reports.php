<?php
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole('admin');

$page = 'reports';
$page_title = 'Reports - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;
include __DIR__ . '/includes/header.php';
?>

            <!-- Header -->
            <header class="top-header">
                <div class="header-left">
                    <h1 class="page-title">Reports</h1>
                    <p class="page-subtitle">Generate and export fleet performance reports</p>
                </div>
                <div class="header-right">
                    <div class="date-display">📅</div>
                </div>
            </header>

            <!-- Reports Section -->
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Available Reports</h2>
                    <div class="section-actions">
                        <select class="filter-select">
                            <option>This Week</option>
                            <option>Last Week</option>
                            <option>This Month</option>
                            <option>Last Month</option>
                        </select>
                    </div>
                </div>
                <div class="reports-grid">
                    <div class="report-card">
                        <div class="report-header">
                            <h3 class="report-title">Daily Summary</h3>
                            <span class="report-date">Dec 23, 2024</span>
                        </div>
                        <div class="report-stats">
                            <div class="report-stat">
                                <span class="stat-label">Total Trips</span>
                                <span class="stat-value">156</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Active Buses</span>
                                <span class="stat-value">42</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Revenue</span>
                                <span class="stat-value">₹4,280</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Fuel Consumed</span>
                                <span class="stat-value">1,240L</span>
                            </div>
                        </div>
                        <div class="report-actions">
                            <a href="#" class="btn-export">Export CSV</a>
                            <a href="#" class="btn-export">Export PDF</a>
                        </div>
                    </div>
                    <div class="report-card">
                        <div class="report-header">
                            <h3 class="report-title">Weekly Summary</h3>
                            <span class="report-date">Dec 16 - Dec 23, 2024</span>
                        </div>
                        <div class="report-stats">
                            <div class="report-stat">
                                <span class="stat-label">Total Trips</span>
                                <span class="stat-value">1,092</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Avg Active Buses</span>
                                <span class="stat-value">41</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Revenue</span>
                                <span class="stat-value">₹29,960</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Fuel Consumed</span>
                                <span class="stat-value">8,680L</span>
                            </div>
                        </div>
                        <div class="report-actions">
                            <a href="#" class="btn-export">Export CSV</a>
                            <a href="#" class="btn-export">Export PDF</a>
                        </div>
                    </div>
                    <div class="report-card">
                        <div class="report-header">
                            <h3 class="report-title">Monthly Summary</h3>
                            <span class="report-date">December 2024</span>
                        </div>
                        <div class="report-stats">
                            <div class="report-stat">
                                <span class="stat-label">Total Trips</span>
                                <span class="stat-value">4,368</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Avg Active Buses</span>
                                <span class="stat-value">40</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Revenue</span>
                                <span class="stat-value">₹119,840</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Fuel Consumed</span>
                                <span class="stat-value">34,720L</span>
                            </div>
                        </div>
                        <div class="report-actions">
                            <a href="#" class="btn-export">Export CSV</a>
                            <a href="#" class="btn-export">Export PDF</a>
                        </div>
                    </div>
                    <div class="report-card">
                        <div class="report-header">
                            <h3 class="report-title">Fleet Performance</h3>
                            <span class="report-date">Last 30 Days</span>
                        </div>
                        <div class="report-stats">
                            <div class="report-stat">
                                <span class="stat-label">Uptime</span>
                                <span class="stat-value">94.2%</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Downtime</span>
                                <span class="stat-value">5.8%</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Avg Distance/Bus</span>
                                <span class="stat-value">3,248 km</span>
                            </div>
                            <div class="report-stat">
                                <span class="stat-label">Fuel Efficiency</span>
                                <span class="stat-value">29.5L/day</span>
                            </div>
                        </div>
                        <div class="report-actions">
                            <a href="#" class="btn-export">Export CSV</a>
                            <a href="#" class="btn-export">Export PDF</a>
                        </div>
                    </div>
                </div>
            </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
