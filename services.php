<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';

requireRole('admin');

$page = 'services';
$page_title = 'Services & Maintenance - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

$msg = '';
$error = '';

// Handle form submission for adding a new service record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_service') {
    $bus_id = (int)$_POST['bus_id'];
    $service_type = trim($_POST['service_type']);
    $description = trim($_POST['description']);
    $cost = (float)$_POST['cost'];
    $service_date = $_POST['service_date'];
    $status = $_POST['status'];

    if (empty($bus_id) || empty($service_type)) {
        $error = "Bus and Service Type are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO services (bus_id, service_type, description, cost, service_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$bus_id, $service_type, $description, $cost, $service_date, $status]);
            $msg = "Service record added safely.";
        } catch (PDOException $e) {
            $error = "Error adding service: " . $e->getMessage();
        }
    }
}

// Handle deleting a service
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    if ($stmt->execute([$delete_id])) {
        $msg = "Service record deleted.";
    } else {
        $error = "Failed to delete service record.";
    }
}

// Fetch buses for the dropdown
$bus_stmt = $pdo->query("SELECT id, bus_number FROM buses WHERE status != 'deleted' ORDER BY bus_number ASC");
$buses = $bus_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all service records
$stmt = $pdo->query("
    SELECT s.*, b.bus_number 
    FROM services s 
    LEFT JOIN buses b ON s.bus_id = b.id 
    ORDER BY s.service_date DESC
");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <h1 class="page-title">Service Records</h1>
        <p class="page-subtitle">Manage maintenance logs and service history</p>
    </div>
</header>

<section class="content-section">
    <?php if ($msg): echo "<div class='alert alert-success'>$msg</div>"; endif; ?>
    <?php if ($error): echo "<div class='alert alert-error'>$error</div>"; endif; ?>

    <div class="users-container">
        <!-- Services List Table -->
        <div class="users-list table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bus</th>
                        <th>Type</th>
                        <th>Cost</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($services) > 0): ?>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($s['bus_number'] ?? 'Unknown/Deleted'); ?></td>
                                <td><?php echo htmlspecialchars($s['service_type']); ?></td>
                                <td>₹<?php echo number_format($s['cost'], 2); ?></td>
                                <td><?php echo htmlspecialchars($s['service_date']); ?></td>
                                <td><span class="status-badge <?php echo $s['status'] === 'completed' ? 'active' : 'idle'; ?>"><?php echo ucfirst(htmlspecialchars($s['status'])); ?></span></td>
                                <td><a href="services.php?delete_id=<?php echo $s['id']; ?>" style="color: #ef4444; font-size: 13px; font-weight: 500; text-decoration: none;" onclick="return confirm('Delete record?');">Delete</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center;">No service records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Service Form -->
        <div class="user-form-card">
            <h3 style="margin-bottom: 20px;">Add Service Record</h3>
            <form method="POST" action="services.php">
                <input type="hidden" name="action" value="add_service">
                
                <div class="form-group">
                    <label class="form-label">Bus</label>
                    <select name="bus_id" class="form-select" required>
                        <option value="">Select Bus...</option>
                        <?php foreach($buses as $bus): ?>
                            <option value="<?php echo $bus['id']; ?>"><?php echo htmlspecialchars($bus['bus_number']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group"><label class="form-label">Service Type</label><input type="text" name="service_type" class="form-input" placeholder="e.g. Oil Change" required></div>
                
                <div class="form-group"><label class="form-label">Description / Notes</label><input type="text" name="description" class="form-input"></div>
                
                <div class="form-group"><label class="form-label">Cost (₹)</label><input type="number" step="0.01" name="cost" class="form-input" value="0.00"></div>
                
                <div class="form-group"><label class="form-label">Scheduled Date</label><input type="date" name="service_date" class="form-input" required></div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Add Record</button>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
