<?php
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole(['admin', 'fleet_manager', 'fleet manager']);

$page = 'maintenance';
$page_title = 'Maintenance - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;
include __DIR__ . '/includes/header.php';
?>

            <!-- Header -->
            <header class="top-header">
                <div class="header-left">
                    <h1 class="page-title">Maintenance Tracking</h1>
                    <p class="page-subtitle">Monitor and schedule vehicle maintenance</p>
                </div>
                <div class="header-right">
                    <div class="date-display">📅</div>
                </div>
            </header>

            <!-- Maintenance Section -->
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Maintenance Schedule</h2>
                    <div class="section-actions">
                        <select class="filter-select">
                            <option>All Maintenance</option>
                            <option>Due Soon</option>
                            <option>Overdue</option>
                            <option>Completed</option>
                        </select>
                    </div>
                </div>
                <div class="maintenance-grid">
                    <div class="maintenance-card overdue">
                        <div class="maintenance-header">
                            <span class="maintenance-bus">BUS-003</span>
                            <span class="maintenance-status overdue">Overdue</span>
                        </div>
                        <div class="maintenance-body">
                            <div class="maintenance-type">Engine Oil Change</div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Due Date:</span>
                                <span class="detail-value">Dec 18, 2024</span>
                            </div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Priority:</span>
                                <span class="detail-value priority-high">High</span>
                            </div>
                        </div>
                    </div>
                    <div class="maintenance-card overdue">
                        <div class="maintenance-header">
                            <span class="maintenance-bus">BUS-007</span>
                            <span class="maintenance-status overdue">Overdue</span>
                        </div>
                        <div class="maintenance-body">
                            <div class="maintenance-type">Brake Inspection</div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Due Date:</span>
                                <span class="detail-value">Dec 20, 2024</span>
                            </div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Priority:</span>
                                <span class="detail-value priority-high">High</span>
                            </div>
                        </div>
                    </div>
                    <div class="maintenance-card overdue">
                        <div class="maintenance-header">
                            <span class="maintenance-bus">BUS-012</span>
                            <span class="maintenance-status overdue">Overdue</span>
                        </div>
                        <div class="maintenance-body">
                            <div class="maintenance-type">Tire Replacement</div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Due Date:</span>
                                <span class="detail-value">Dec 15, 2024</span>
                            </div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Priority:</span>
                                <span class="detail-value priority-high">High</span>
                            </div>
                        </div>
                    </div>
                    <div class="maintenance-card due">
                        <div class="maintenance-header">
                            <span class="maintenance-bus">BUS-005</span>
                            <span class="maintenance-status due">Due Soon</span>
                        </div>
                        <div class="maintenance-body">
                            <div class="maintenance-type">General Service</div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Due Date:</span>
                                <span class="detail-value">Jan 5, 2025</span>
                            </div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Priority:</span>
                                <span class="detail-value priority-medium">Medium</span>
                            </div>
                        </div>
                    </div>
                    <div class="maintenance-card due">
                        <div class="maintenance-header">
                            <span class="maintenance-bus">BUS-004</span>
                            <span class="maintenance-status due">Due Soon</span>
                        </div>
                        <div class="maintenance-body">
                            <div class="maintenance-type">AC Service</div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Due Date:</span>
                                <span class="detail-value">Jan 8, 2025</span>
                            </div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Priority:</span>
                                <span class="detail-value priority-medium">Medium</span>
                            </div>
                        </div>
                    </div>
                    <div class="maintenance-card due">
                        <div class="maintenance-header">
                            <span class="maintenance-bus">BUS-001</span>
                            <span class="maintenance-status due">Due Soon</span>
                        </div>
                        <div class="maintenance-body">
                            <div class="maintenance-type">Battery Check</div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Due Date:</span>
                                <span class="detail-value">Jan 15, 2025</span>
                            </div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Priority:</span>
                                <span class="detail-value priority-low">Low</span>
                            </div>
                        </div>
                    </div>
                    <div class="maintenance-card due">
                        <div class="maintenance-header">
                            <span class="maintenance-bus">BUS-006</span>
                            <span class="maintenance-status due">Due Soon</span>
                        </div>
                        <div class="maintenance-body">
                            <div class="maintenance-type">Engine Oil Change</div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Due Date:</span>
                                <span class="detail-value">Jan 18, 2025</span>
                            </div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Priority:</span>
                                <span class="detail-value priority-medium">Medium</span>
                            </div>
                        </div>
                    </div>
                    <div class="maintenance-card due">
                        <div class="maintenance-header">
                            <span class="maintenance-bus">BUS-002</span>
                            <span class="maintenance-status due">Due Soon</span>
                        </div>
                        <div class="maintenance-body">
                            <div class="maintenance-type">Transmission Check</div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Due Date:</span>
                                <span class="detail-value">Jan 22, 2025</span>
                            </div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Priority:</span>
                                <span class="detail-value priority-medium">Medium</span>
                            </div>
                        </div>
                    </div>
                    <div class="maintenance-card due">
                        <div class="maintenance-header">
                            <span class="maintenance-bus">BUS-009</span>
                            <span class="maintenance-status due">Due Soon</span>
                        </div>
                        <div class="maintenance-body">
                            <div class="maintenance-type">Suspension Check</div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Due Date:</span>
                                <span class="detail-value">Jan 25, 2025</span>
                            </div>
                            <div class="maintenance-detail">
                                <span class="detail-label">Priority:</span>
                                <span class="detail-value priority-low">Low</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
