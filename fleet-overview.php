<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole(['admin', 'fleet_manager', 'fleet manager']);

$page = 'fleet-overview';
$page_title = 'Fleet Overview - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

// Fetch all buses with their recent routing and maintenance
$stmt = $pdo->query("
    SELECT b.*, 
           COALESCE(curr_r.route_name, r.route_name) AS route_name,
           COALESCE(curr_ud.username, ud.username) AS driver_name,
           COALESCE(curr_uc.username, uc.username) AS conductor_name,
           curr_trip.status AS curr_trip_status,
           (SELECT MIN(service_date) FROM services WHERE bus_id = b.id AND status = 'scheduled') as next_maintenance,
           (SELECT COALESCE(SUM(r2.distance), 0) FROM trips t2 JOIN routes r2 ON t2.route_id = r2.id WHERE t2.bus_id = b.id AND t2.status = 'completed') as total_distance
    FROM buses b
    LEFT JOIN routes r ON b.route_id = r.id
    LEFT JOIN users ud ON b.driver_id = ud.id
    LEFT JOIN users uc ON b.conductor_id = uc.id
    LEFT JOIN (
        SELECT bus_id, MIN(start_time) as min_start_time
        FROM trips
        WHERE status IN ('ongoing', 'scheduled')
        GROUP BY bus_id
    ) latest_trip ON latest_trip.bus_id = b.id
    LEFT JOIN trips curr_trip ON curr_trip.bus_id = b.id 
                             AND curr_trip.start_time = latest_trip.min_start_time 
                             AND curr_trip.status IN ('ongoing', 'scheduled')
    LEFT JOIN routes curr_r ON curr_trip.route_id = curr_r.id
    LEFT JOIN users curr_ud ON curr_trip.driver_id = curr_ud.id
    LEFT JOIN users curr_uc ON curr_trip.conductor_id = curr_uc.id
    WHERE b.status != 'deleted'
    ORDER BY b.id DESC
");
$fleet_buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

            <!-- Header -->
            <header class="top-header">
                <div class="header-left">
                    <h1 class="page-title">Fleet Overview</h1>
                    <p class="page-subtitle">Monitor and manage all your vehicles</p>
                </div>
                <div class="header-right">
                    <div class="date-display">📅</div>
                </div>
            </header>

            <!-- Fleet Overview Section -->
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Vehicles</h2>
                    <div class="section-actions">
                        <input type="text" class="search-input" placeholder="Search buses...">
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Bus Number</th>
                                <th>Status</th>
                                <th>Driver / Conductor</th>
                                <th>Current Route</th>
                                <th>Next Maintenance</th>
                                <th>Total Distance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($fleet_buses) > 0): ?>
                                <?php foreach ($fleet_buses as $bus): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $disp_status = ucfirst(htmlspecialchars($bus['status']));
                                            $badge = $bus['status'] === 'active' ? 'active' : ($bus['status'] === 'maintenance' ? 'maintenance' : 'idle');
                                            
                                            // If not in maintenance and has an active trip assignment
                                            if ($bus['status'] !== 'maintenance' && !empty($bus['curr_trip_status'])) {
                                                $disp_status = ucfirst(htmlspecialchars($bus['curr_trip_status']));
                                                $badge = 'ongoing';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $badge; ?>"><?php echo $disp_status; ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($bus['driver_name'])): ?><div style="font-size: 13px; color: #10b981;">👨‍✈️ <?php echo htmlspecialchars($bus['driver_name']); ?></div><?php endif; ?>
                                            <?php if (!empty($bus['conductor_name'])): ?><div style="font-size: 13px; color: #f59e0b; margin-top:2px;">🎫 <?php echo htmlspecialchars($bus['conductor_name']); ?></div><?php endif; ?>
                                            <?php if (empty($bus['driver_name']) && empty($bus['conductor_name'])) echo '<span style="color:#94a3b8;">Unassigned</span>'; ?>
                                        </td>
                                        <td><?php echo !empty($bus['route_name']) ? htmlspecialchars($bus['route_name']) : '<span style="color: #94a3b8;">Unassigned</span>'; ?></td>
                                        <td><?php echo !empty($bus['next_maintenance']) ? date('d M Y', strtotime($bus['next_maintenance'])) : '<span style="color:#94a3b8;">Not scheduled</span>'; ?></td>
                                        <td><?php echo number_format((float)$bus['total_distance'], 1) . ' km'; ?></td>
                                    </tr>
                                <?php
    endforeach; ?>
                            <?php
else: ?>
                                <tr><td colspan="6" style="text-align: center;">No vehicles found in fleet.</td></tr>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
