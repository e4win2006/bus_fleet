<?php
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole(['admin', 'fleet_manager', 'fleet manager']);

$page = 'trips';
$page_title = 'Trips - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;
include __DIR__ . '/includes/header.php';
?>

            <!-- Header -->
            <header class="top-header">
                <div class="header-left">
                    <h1 class="page-title">Trips & Operations</h1>
                    <p class="page-subtitle">Track all trip activities and schedules</p>
                </div>
                <div class="header-right">
                    <div class="date-display">📅</div>
                </div>
            </header>

            <!-- Trips Section -->
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Trips</h2>
                    <div class="section-actions">
                        <select class="filter-select">
                            <option>All Trips</option>
                            <option>Completed</option>
                            <option>Ongoing</option>
                            <option>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Trip ID</th>
                                <th>Route</th>
                                <th>Bus Assigned</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Distance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#T1056</strong></td>
                                <td>Route A - Downtown</td>
                                <td>BUS-001</td>
                                <td>08:00 AM</td>
                                <td>09:30 AM</td>
                                <td>24 km</td>
                                <td><span class="status-badge completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1057</strong></td>
                                <td>Route B - Airport</td>
                                <td>BUS-002</td>
                                <td>08:15 AM</td>
                                <td>10:00 AM</td>
                                <td>42 km</td>
                                <td><span class="status-badge completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1058</strong></td>
                                <td>Route C - Suburbs</td>
                                <td>BUS-004</td>
                                <td>09:00 AM</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="status-badge ongoing">Ongoing</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1059</strong></td>
                                <td>Route D - City Center</td>
                                <td>BUS-006</td>
                                <td>09:30 AM</td>
                                <td>11:15 AM</td>
                                <td>18 km</td>
                                <td><span class="status-badge completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1060</strong></td>
                                <td>Route A - Downtown</td>
                                <td>BUS-008</td>
                                <td>10:00 AM</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="status-badge ongoing">Ongoing</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1061</strong></td>
                                <td>Route B - Airport</td>
                                <td>BUS-002</td>
                                <td>10:30 AM</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="status-badge cancelled">Cancelled</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1062</strong></td>
                                <td>Route C - Suburbs</td>
                                <td>BUS-004</td>
                                <td>11:00 AM</td>
                                <td>12:45 PM</td>
                                <td>28 km</td>
                                <td><span class="status-badge completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1063</strong></td>
                                <td>Route D - City Center</td>
                                <td>BUS-006</td>
                                <td>11:30 AM</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="status-badge ongoing">Ongoing</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1064</strong></td>
                                <td>Route A - Downtown</td>
                                <td>BUS-001</td>
                                <td>12:00 PM</td>
                                <td>01:30 PM</td>
                                <td>24 km</td>
                                <td><span class="status-badge completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1065</strong></td>
                                <td>Route B - Airport</td>
                                <td>BUS-009</td>
                                <td>01:00 PM</td>
                                <td>02:45 PM</td>
                                <td>42 km</td>
                                <td><span class="status-badge completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1066</strong></td>
                                <td>Route C - Suburbs</td>
                                <td>BUS-004</td>
                                <td>02:00 PM</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="status-badge ongoing">Ongoing</span></td>
                            </tr>
                            <tr>
                                <td><strong>#T1067</strong></td>
                                <td>Route D - City Center</td>
                                <td>BUS-006</td>
                                <td>02:30 PM</td>
                                <td>04:00 PM</td>
                                <td>18 km</td>
                                <td><span class="status-badge completed">Completed</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
