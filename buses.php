<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';

requireRole('admin');

$page = 'buses';
$page_title = 'Buses Management - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

$msg = '';
$error = '';

// Handle form submission for adding a new bus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_bus') {
    $bus_number = trim($_POST['bus_number']);
    $make = trim($_POST['make']);
    $model = trim($_POST['model']);
    $year = (int)$_POST['year'];
    $capacity = (int)$_POST['capacity'];
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';

    $gps_url = trim($_POST['gps_url'] ?? '');
    $cctv_url_1 = trim($_POST['cctv_url_1'] ?? '');
    $cctv_url_2 = trim($_POST['cctv_url_2'] ?? '');

    $route_id = empty($_POST['route_id']) ? null : (int)$_POST['route_id'];
    $driver_id = empty($_POST['driver_id']) ? null : (int)$_POST['driver_id'];
    $conductor_id = empty($_POST['conductor_id']) ? null : (int)$_POST['conductor_id'];

    if (empty($bus_number)) {
        $error = "Bus Number is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO buses (bus_number, make, model, year, capacity, status, gps_url, cctv_url_1, cctv_url_2, route_id, driver_id, conductor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$bus_number, $make, $model, $year, $capacity, $status, $gps_url, $cctv_url_1, $cctv_url_2, $route_id, $driver_id, $conductor_id]);
            $msg = "Bus added successfully.";
        } catch (PDOException $e) {
            $error = "Error adding bus: " . $e->getMessage();
        }
    }
}

// Handle deleting a bus
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    // Using soft delete / status change rather than hard delete due to foreign key constraints in trips, services, etc.
    $stmt = $pdo->prepare("UPDATE buses SET status = 'deleted' WHERE id = ?");
    if ($stmt->execute([$delete_id])) {
        $msg = "Bus deleted successfully.";
    } else {
        $error = "Failed to delete bus.";
    }
}

// Fetch all non-deleted buses
$stmt = $pdo->query("
    SELECT b.*, 
           r.route_name, 
           ud.username AS driver_name, 
           uc.username AS conductor_name
    FROM buses b
    LEFT JOIN routes r ON b.route_id = r.id
    LEFT JOIN users ud ON b.driver_id = ud.id
    LEFT JOIN users uc ON b.conductor_id = uc.id
    WHERE b.status != 'deleted' 
    ORDER BY b.id DESC
");
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch data for dropdowns
$routes = $pdo->query("SELECT id, route_name FROM routes WHERE status = 'active' ORDER BY route_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$drivers = $pdo->query("SELECT id, username FROM users WHERE role = 'driver' AND status = 'approved' ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
$conductors = $pdo->query("SELECT id, username FROM users WHERE role = 'conductor' AND status = 'approved' ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<style>
.users-container { display: flex; gap: 24px; align-items: flex-start; }
.users-list { flex: 2; }
.user-form-card { flex: 1; background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; position: sticky; top: 24px; }
.form-group { margin-bottom: 16px; }
.form-label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 500; color: #475569; }
.form-input, .form-select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
.btn-submit { width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-submit:hover { background: #2563eb; }
.alert { padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
.alert-success { background: #dcfce3; color: #15803d; border: 1px solid #bbf7d0; }
.alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
@media (max-width: 992px) { .users-container { flex-direction: column-reverse; } .user-form-card { width: 100%; position: static; } }
</style>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">Buses Management</h1>
        <p class="page-subtitle">Add, edit, and manage fleet vehicles</p>
    </div>
</header>

<section class="content-section">
    <?php if ($msg): echo "<div class='alert alert-success'>$msg</div>"; endif; ?>
    <?php if ($error): echo "<div class='alert alert-error'>$error</div>"; endif; ?>

    <div class="users-container">
        <!-- Buses List Table -->
        <div class="users-list table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bus Number</th>
                        <th>Make & Model</th>
                        <th>Year</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($buses) > 0): ?>
                        <?php foreach ($buses as $b): ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($b['bus_number']); ?></td>
                                <td><?php echo htmlspecialchars($b['make'] . ' ' . $b['model']); ?></td>
                                <td><?php echo htmlspecialchars($b['year']); ?></td>
                                <td><?php echo htmlspecialchars($b['capacity']); ?></td>
                                <td>
                                    <?php if($b['route_name']): ?><div style="font-size: 12px; color: #3b82f6;">🛣️ <?php echo htmlspecialchars($b['route_name']); ?></div><?php endif; ?>
                                    <?php if($b['driver_name']): ?><div style="font-size: 12px; color: #10b981;">👨‍✈️ <?php echo htmlspecialchars($b['driver_name']); ?></div><?php endif; ?>
                                    <?php if($b['conductor_name']): ?><div style="font-size: 12px; color: #f59e0b;">🎫 <?php echo htmlspecialchars($b['conductor_name']); ?></div><?php endif; ?>
                                </td>
                                <td><span class="status-badge <?php echo $b['status'] === 'active' ? 'active' : 'idle'; ?>"><?php echo ucfirst(htmlspecialchars($b['status'])); ?></span></td>
                                <td><a href="buses.php?delete_id=<?php echo $b['id']; ?>" style="color: #ef4444; font-size: 13px; font-weight: 500; text-decoration: none;" onclick="return confirm('Delete this bus?');">Delete</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center;">No buses found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Bus Form -->
        <div class="user-form-card">
            <h3 style="margin-bottom: 20px;">Add New Bus</h3>
            <form method="POST" action="buses.php">
                <input type="hidden" name="action" value="add_bus">
                <div class="form-group"><label class="form-label">Bus Number / Plate</label><input type="text" name="bus_number" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Make (e.g., Volvo)</label><input type="text" name="make" class="form-input"></div>
                <div class="form-group"><label class="form-label">Model</label><input type="text" name="model" class="form-input"></div>
                <div class="form-group"><label class="form-label">Year</label><input type="number" name="year" class="form-input" value="2020"></div>
                <div class="form-group"><label class="form-label">Passenger Capacity</label><input type="number" name="capacity" class="form-input" value="40"></div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="idle">Idle</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                <h4 style="margin-bottom: 12px; color: #475569;">Route & Crew Assignment</h4>
                
                <div class="form-group">
                    <label class="form-label">Designated Route</label>
                    <select name="route_id" class="form-select">
                        <option value="">None (Unassigned)</option>
                        <?php foreach($routes as $r): ?>
                            <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['route_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Assigned Driver</label>
                    <select name="driver_id" class="form-select">
                        <option value="">None (Unassigned)</option>
                        <?php foreach($drivers as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Assigned Conductor</label>
                    <select name="conductor_id" class="form-select">
                        <option value="">None (Unassigned)</option>
                        <?php foreach($conductors as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                <h4 style="margin-bottom: 12px; color: #475569;">Live Tracking Setup</h4>
                
                <div class="form-group"><label class="form-label">GPS iframe URL</label><input type="url" name="gps_url" class="form-input" placeholder="https://tracking.com/map/123"></div>
                <div class="form-group"><label class="form-label">Cam 1 (Hikvision/Web) URL</label><input type="url" name="cctv_url_1" class="form-input" placeholder="http://ip_cam1/stream"></div>
                <div class="form-group"><label class="form-label">Cam 2 URL</label><input type="url" name="cctv_url_2" class="form-input" placeholder="http://ip_cam2/stream"></div>

                <button type="submit" class="btn-submit" style="margin-top: 10px;">Add Bus</button>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
