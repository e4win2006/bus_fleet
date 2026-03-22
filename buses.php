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
    }
    else {
        $stmt = $pdo->prepare("INSERT INTO buses (bus_number, make, model, year, capacity, status, gps_url, cctv_url_1, cctv_url_2, route_id, driver_id, conductor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$bus_number, $make, $model, $year, $capacity, $status, $gps_url, $cctv_url_1, $cctv_url_2, $route_id, $driver_id, $conductor_id]);
            $msg = "Bus added successfully.";
        }
        catch (PDOException $e) {
            $error = "Error adding bus: " . $e->getMessage();
        }
    }
}

// Handle form submission for editing an existing bus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_bus') {
    $bus_id = (int)$_POST['bus_id'];
    $bus_number = trim($_POST['bus_number']);
    $make = trim($_POST['make']);
    $model = trim($_POST['model']);
    $year = (int)$_POST['year'];
    $capacity = (int)$_POST['capacity'];
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';

    $route_id = empty($_POST['route_id']) ? null : (int)$_POST['route_id'];
    $driver_id = empty($_POST['driver_id']) ? null : (int)$_POST['driver_id'];
    $conductor_id = empty($_POST['conductor_id']) ? null : (int)$_POST['conductor_id'];

    if (empty($bus_number)) {
        $error = "Bus Number is required.";
    }
    else {
        $stmt = $pdo->prepare("UPDATE buses SET bus_number=?, make=?, model=?, year=?, capacity=?, status=?, route_id=?, driver_id=?, conductor_id=? WHERE id=?");
        try {
            $stmt->execute([$bus_number, $make, $model, $year, $capacity, $status, $route_id, $driver_id, $conductor_id, $bus_id]);
            $msg = "Bus updated successfully.";
        }
        catch (PDOException $e) {
            $error = "Error updating bus: " . $e->getMessage();
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
    }
    else {
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
$routes = $pdo->query("
    SELECT r.id, r.route_name, b.bus_number AS assigned_bus
    FROM routes r
    LEFT JOIN buses b ON r.id = b.route_id AND b.status != 'deleted'
    WHERE r.status = 'active'
    ORDER BY r.route_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$drivers = $pdo->query("
    SELECT u.id, u.username, b.bus_number AS assigned_bus
    FROM users u
    LEFT JOIN buses b ON u.id = b.driver_id AND b.status != 'deleted'
    WHERE u.role = 'driver' AND u.status = 'approved'
    ORDER BY u.username ASC
")->fetchAll(PDO::FETCH_ASSOC);

$conductors = $pdo->query("
    SELECT u.id, u.username, b.bus_number AS assigned_bus
    FROM users u
    LEFT JOIN buses b ON u.id = b.conductor_id AND b.status != 'deleted'
    WHERE u.role = 'conductor' AND u.status = 'approved'
    ORDER BY u.username ASC
")->fetchAll(PDO::FETCH_ASSOC);

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
.hover-row { transition: background-color 0.2s ease; }
.hover-row:hover { background-color: #f8fafc; }
@media (max-width: 992px) { .users-container { flex-direction: column-reverse; } .user-form-card { width: 100%; position: static; } }
</style>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">Buses Management</h1>
        <p class="page-subtitle">Add, edit, and manage fleet vehicles</p>
    </div>
        <div class="header-right">
        <div class="date-display"></div>
    </div>
</header>

<section class="content-section">
    <?php if ($msg):
    echo "<div class='alert alert-success'>$msg</div>";
endif; ?>
    <?php if ($error):
    echo "<div class='alert alert-error'>$error</div>";
endif; ?>

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
                        <th>Route Info</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($buses) > 0): ?>
                        <?php foreach ($buses as $b): ?>
                            <?php
        $jsdata = htmlspecialchars(json_encode([
            'id' => $b['id'], 'bus_number' => $b['bus_number'], 'make' => $b['make'], 'model' => $b['model'],
            'year' => $b['year'], 'capacity' => $b['capacity'], 'status' => $b['status'],
            'route_id' => $b['route_id'], 'driver_id' => $b['driver_id'], 'conductor_id' => $b['conductor_id']
        ]), ENT_QUOTES, 'UTF-8');
?>
                            <tr onclick="editBus(this)" data-bus="<?php echo $jsdata; ?>" class="hover-row" style="cursor: pointer;" title="Click to edit">
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($b['bus_number']); ?></td>
                                <td><?php echo htmlspecialchars($b['make'] . ' ' . $b['model']); ?></td>
                                <td><?php echo htmlspecialchars($b['year']); ?></td>
                                <td><?php echo htmlspecialchars($b['capacity']); ?></td>
                                <td>
                                    <?php if ($b['route_name']): ?><div style="font-size: 12px; color: #3b82f6;">🛣️ <?php echo htmlspecialchars($b['route_name']); ?></div><?php
        endif; ?>
                                    <?php if ($b['driver_name']): ?><div style="font-size: 12px; color: #10b981;">👨‍✈️ <?php echo htmlspecialchars($b['driver_name']); ?></div><?php
        endif; ?>
                                    <?php if ($b['conductor_name']): ?><div style="font-size: 12px; color: #f59e0b;">🎫 <?php echo htmlspecialchars($b['conductor_name']); ?></div><?php
        endif; ?>
                                </td>
                                <td><span class="status-badge <?php echo $b['status'] === 'active' ? 'active' : 'idle'; ?>"><?php echo ucfirst(htmlspecialchars($b['status'])); ?></span></td>
                                <td>
                                    <a href="buses.php?delete_id=<?php echo $b['id']; ?>" style="color: #ef4444; font-size: 13px; font-weight: 500; text-decoration: none;" onclick="event.stopPropagation(); return confirm('Delete this bus?');">Delete</a>
                                </td>
                            </tr>
                        <?php
    endforeach; ?>
                    <?php
else: ?>
                        <tr><td colspan="6" style="text-align: center;">No buses found.</td></tr>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Bus Form -->
        <div class="user-form-card">
            <h3 id="form-title" style="margin-bottom: 20px;">Add New Bus</h3>
            <form method="POST" action="buses.php" id="bus-form">
                <input type="hidden" name="action" id="form-action" value="add_bus">
                <input type="hidden" name="bus_id" id="form-bus-id" value="">
                
                <div class="form-group"><label class="form-label">Bus Number / Plate</label><input type="text" name="bus_number" id="form-bus_number" class="form-input" required></div>
                
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;"><label class="form-label">Make (e.g., Volvo)</label><input type="text" name="make" id="form-make" class="form-input"></div>
                    <div class="form-group" style="flex: 1;"><label class="form-label">Model</label><input type="text" name="model" id="form-model" class="form-input"></div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;"><label class="form-label">Year</label><input type="number" name="year" id="form-year" class="form-input" value="2020"></div>
                    <div class="form-group" style="flex: 1;"><label class="form-label">Capacity</label><input type="number" name="capacity" id="form-capacity" class="form-input" value="40"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Default Route</label>
                    <select name="route_id" id="form-route_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($routes as $r): ?>
                            <?php $isAssigned = !empty($r['assigned_bus']); ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo $isAssigned ? 'disabled data-assigned="true"' : ''; ?>>
                                <?php echo htmlspecialchars($r['route_name']) . ($isAssigned ? ' (Assigned to ' . htmlspecialchars($r['assigned_bus']) . ')' : ''); ?>
                            </option>
                        <?php
endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Permanent Driver</label>
                    <select name="driver_id" id="form-driver_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($drivers as $d): ?>
                            <?php $isAssigned = !empty($d['assigned_bus']); ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $isAssigned ? 'disabled data-assigned="true"' : ''; ?>>
                                <?php echo htmlspecialchars($d['username']) . ($isAssigned ? ' (Assigned to ' . htmlspecialchars($d['assigned_bus']) . ')' : ''); ?>
                            </option>
                        <?php
endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Permanent Conductor</label>
                    <select name="conductor_id" id="form-conductor_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($conductors as $c): ?>
                            <?php $isAssigned = !empty($c['assigned_bus']); ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $isAssigned ? 'disabled data-assigned="true"' : ''; ?>>
                                <?php echo htmlspecialchars($c['username']) . ($isAssigned ? ' (Assigned to ' . htmlspecialchars($c['assigned_bus']) . ')' : ''); ?>
                            </option>
                        <?php
endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="form-status" class="form-select">
                        <option value="active">Active</option>
                        <option value="idle">Idle</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit" id="btn-submit" style="margin-top: 10px;">Add Bus</button>
                <button type="button" class="btn-cancel" id="btn-cancel" style="margin-top: 10px; display:none; background:#94a3b8; color:white; width:100%; padding:10px; border:none; border-radius:6px; font-weight:600; cursor:pointer;" onclick="cancelEdit()">Cancel Edit</button>
            </form>
            <script>
            function editBus(el) {
                var data = JSON.parse(el.getAttribute('data-bus'));
                document.getElementById('form-action').value = 'edit_bus';
                document.getElementById('form-bus-id').value = data.id;
                document.getElementById('form-bus_number').value = data.bus_number || '';
                document.getElementById('form-make').value = data.make || '';
                document.getElementById('form-model').value = data.model || '';
                document.getElementById('form-year').value = data.year || '';
                document.getElementById('form-capacity').value = data.capacity || '';
                document.getElementById('form-status').value = data.status || 'active';
                // Reset disabled states to default
                ['form-route_id', 'form-driver_id', 'form-conductor_id'].forEach(id => {
                    Array.from(document.getElementById(id).options).forEach(opt => {
                        if(opt.getAttribute('data-assigned')) opt.disabled = true;
                    });
                });
                
                // Allow the currently assigned options to be selectable for this specific bus
                if (data.route_id) { let o = document.querySelector('#form-route_id option[value="'+data.route_id+'"]'); if(o) o.disabled = false; }
                if (data.driver_id) { let o = document.querySelector('#form-driver_id option[value="'+data.driver_id+'"]'); if(o) o.disabled = false; }
                if (data.conductor_id) { let o = document.querySelector('#form-conductor_id option[value="'+data.conductor_id+'"]'); if(o) o.disabled = false; }
                
                document.getElementById('form-route_id').value = data.route_id || '';
                document.getElementById('form-driver_id').value = data.driver_id || '';
                document.getElementById('form-conductor_id').value = data.conductor_id || '';
                
                document.getElementById('form-title').innerText = 'Edit Bus Details';
                document.getElementById('btn-submit').innerText = 'Save Changes';
                document.getElementById('btn-cancel').style.display = 'block';
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
            function cancelEdit() {
                document.getElementById('form-action').value = 'add_bus';
                document.getElementById('form-bus-id').value = '';
                document.getElementById('form-bus_number').value = '';
                document.getElementById('form-make').value = '';
                document.getElementById('form-model').value = '';
                document.getElementById('form-year').value = '2020';
                document.getElementById('form-capacity').value = '40';
                document.getElementById('form-status').value = 'active';
                // Restore default disabled states
                ['form-route_id', 'form-driver_id', 'form-conductor_id'].forEach(id => {
                    Array.from(document.getElementById(id).options).forEach(opt => {
                        if(opt.getAttribute('data-assigned')) opt.disabled = true;
                    });
                });
                
                document.getElementById('form-route_id').value = '';
                document.getElementById('form-driver_id').value = '';
                document.getElementById('form-conductor_id').value = '';
                
                document.getElementById('form-title').innerText = 'Add New Bus';
                document.getElementById('btn-submit').innerText = 'Add Bus';
                document.getElementById('btn-cancel').style.display = 'none';
            }
            </script>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
