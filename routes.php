<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';

requireRole(['admin', 'fleet manager', 'fleet_manager']);

$page = 'routes';
$page_title = 'Routes Management - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

$msg = '';
$error = '';

// Handle Add Route
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_route') {
    $route_name = trim($_POST['route_name']);
    $start_location = trim($_POST['start_location']);
    $end_location = trim($_POST['end_location']);
    $distance = (float)($_POST['distance'] ?? 0);
    $duration = (int)($_POST['estimated_duration'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    if (empty($route_name)) {
        $error = "Route name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO routes (route_name, start_location, end_location, distance, estimated_duration, status) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$route_name, $start_location, $end_location, $distance, $duration, $status]);
            $msg = "Route added successfully.";
        } catch (PDOException $e) {
            $error = "Error adding route: " . $e->getMessage();
        }
    }
}

// Handle Delete Route
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM routes WHERE id = ?");
    if ($stmt->execute([$delete_id])) {
        $msg = "Route deleted successfully.";
        
        // Also wipe route_id from any assigned buses to prevent ghost references
        $pdo->prepare("UPDATE buses SET route_id = NULL WHERE route_id = ?")->execute([$delete_id]);
    } else {
        $error = "Failed to delete route.";
    }
}

// Fetch all routes
$stmt = $pdo->query("SELECT * FROM routes ORDER BY id DESC");
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">Routes Management</h1>
        <p class="page-subtitle">Configure fleet transit paths and waypoints</p>
    </div>
</header>

<section class="content-section">
    <?php if ($msg): echo "<div class='alert alert-success'>$msg</div>"; endif; ?>
    <?php if ($error): echo "<div class='alert alert-error'>$error</div>"; endif; ?>

    <div style="display: flex; gap: 24px; align-items: flex-start;">
        <!-- Routes Table -->
        <div class="table-container" style="flex: 2;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Route Name</th>
                        <th>Path</th>
                        <th>Distance</th>
                        <th>Est. Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($routes) > 0): ?>
                        <?php foreach ($routes as $r): ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($r['route_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['start_location'] . ' ➔ ' . $r['end_location']); ?></td>
                                <td><?php echo $r['distance']; ?> km</td>
                                <td><?php echo $r['estimated_duration']; ?> mins</td>
                                <td><span class="status-badge <?php echo $r['status'] === 'active' ? 'active' : 'idle'; ?>"><?php echo ucfirst(htmlspecialchars($r['status'])); ?></span></td>
                                <td><a href="routes.php?delete_id=<?php echo $r['id']; ?>" style="color: #ef4444; font-size: 13px; font-weight: 500; text-decoration: none;" onclick="return confirm('Delete route? Associated buses will be unassigned from this route.');">Delete</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center;">No routes defined yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Route Card -->
        <div class="user-form-card" style="flex: 1; position: sticky; top: 24px;">
            <h3 style="margin-bottom: 20px;">Add New Route</h3>
            <form method="POST" action="routes.php">
                <input type="hidden" name="action" value="add_route">
                <div class="form-group"><label class="form-label">Route Alias / Name</label><input type="text" name="route_name" class="form-input" placeholder="e.g. Route A / Express 1" required></div>
                <div class="form-group"><label class="form-label">Start Location</label><input type="text" name="start_location" class="form-input" placeholder="e.g. North Station"></div>
                <div class="form-group"><label class="form-label">End Location</label><input type="text" name="end_location" class="form-input" placeholder="e.g. Downtown Central"></div>
                
                <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                    <div style="flex: 1;"><label class="form-label">Distance (km)</label><input type="number" step="0.1" name="distance" class="form-input" value="10.5"></div>
                    <div style="flex: 1;"><label class="form-label">Duration (mins)</label><input type="number" name="estimated_duration" class="form-input" value="45"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit" style="margin-top: 10px;">Save Route</button>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
