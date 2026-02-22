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
           r.route_name
    FROM buses b
    LEFT JOIN routes r ON b.route_id = r.id
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
                                <th>Current Route</th>
                                <th>Last Trip</th>
                                <th>Next Maintenance</th>
                                <th>Total Distance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($fleet_buses) > 0): ?>
                                <?php foreach ($fleet_buses as $bus): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                        <td><span class="status-badge <?php echo $bus['status'] === 'active' ? 'active' : ($bus['status'] === 'maintenance' ? 'maintenance' : 'idle'); ?>"><?php echo ucfirst(htmlspecialchars($bus['status'])); ?></span></td>
                                        <td><?php echo $bus['route_name'] ? htmlspecialchars($bus['route_name']) : '<span style="color: #94a3b8;">Unassigned</span>'; ?></td>
                                        <td>--</td>
                                        <td>--</td>
                                        <td>--</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center;">No vehicles found in fleet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
