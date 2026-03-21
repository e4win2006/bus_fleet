<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';

requireRole(['admin', 'fleet_manager', 'fleet manager']);

$page       = 'trips';
$page_title = 'Trips - FleetVision';
$page_css   = 'dashboard.css';
$show_sidebar = true;

$msg   = '';
$error = '';

// ── Auto-update trip statuses based on current time ───────────────────────
// Normalize stored datetimes: HTML datetime-local saves "2026-03-22T00:13:00"
// but SQLite datetime() returns "2026-03-22 00:13:00" — REPLACE fixes the T gap.

// scheduled → ongoing : start_time has passed, end_time not yet reached (or not set)
$pdo->exec("
    UPDATE trips
    SET status = 'ongoing'
    WHERE status = 'scheduled'
      AND REPLACE(start_time, 'T', ' ') <= datetime('now', 'localtime')
      AND (end_time IS NULL OR REPLACE(end_time, 'T', ' ') > datetime('now', 'localtime'))
");

// ongoing / scheduled → completed : end_time has passed
$pdo->exec("
    UPDATE trips
    SET status = 'completed'
    WHERE status IN ('ongoing', 'scheduled')
      AND end_time IS NOT NULL
      AND REPLACE(end_time, 'T', ' ') <= datetime('now', 'localtime')
");

// ── Add Trip ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_trip') {
    $bus_id     = empty($_POST['bus_id'])    ? null : (int)$_POST['bus_id'];
    $route_id   = empty($_POST['route_id'])  ? null : (int)$_POST['route_id'];
    $driver_id  = empty($_POST['driver_id']) ? null : (int)$_POST['driver_id'];
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time   = trim($_POST['end_time']   ?? '') ?: null;
    $status     = $_POST['status'] ?? 'scheduled';
    $notes      = trim($_POST['notes'] ?? '');

    if (empty($start_time)) {
        $error = 'Start time is required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO trips (bus_id, route_id, driver_id, start_time, end_time, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$bus_id, $route_id, $driver_id, $start_time, $end_time, $status, $notes]);
            $msg = 'Trip added successfully.';
        } catch (PDOException $e) {
            $error = 'Error adding trip: ' . $e->getMessage();
        }
    }
}

// ── Update Status ─────────────────────────────────────────────────────────
if (isset($_GET['complete_id'])) {
    $id = (int)$_GET['complete_id'];
    $pdo->prepare("UPDATE trips SET status = 'completed', end_time = datetime('now','localtime') WHERE id = ?")->execute([$id]);
    $msg = 'Trip marked as completed.';
}

// ── Delete Trip ───────────────────────────────────────────────────────────
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM trips WHERE id = ?")->execute([(int)$_GET['delete_id']]);
    $msg = 'Trip deleted.';
}

// ── Fetch data ────────────────────────────────────────────────────────────
$trips = $pdo->query("
    SELECT t.*,
           b.bus_number,
           r.route_name, r.distance,
           u.username AS driver_name
    FROM trips t
    LEFT JOIN buses  b ON t.bus_id   = b.id
    LEFT JOIN routes r ON t.route_id = r.id
    LEFT JOIN users  u ON t.driver_id = u.id
    ORDER BY t.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$buses   = $pdo->query("SELECT id, bus_number FROM buses WHERE status != 'deleted' ORDER BY bus_number")->fetchAll(PDO::FETCH_ASSOC);
$routes  = $pdo->query("SELECT id, route_name FROM routes WHERE status = 'active' ORDER BY route_name")->fetchAll(PDO::FETCH_ASSOC);
$drivers = $pdo->query("SELECT id, username FROM users WHERE role = 'driver' AND status = 'approved' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<style>
.users-container  { display: flex; gap: 24px; align-items: flex-start; }
.users-list       { flex: 2; }
.user-form-card   { flex: 1; background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; position: sticky; top: 24px; }
.form-group       { margin-bottom: 16px; }
.form-label       { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 500; color: #475569; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; font-family: inherit; }
.form-textarea    { resize: vertical; min-height: 60px; }
.btn-submit       { width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-submit:hover { background: #2563eb; }
.alert            { padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
.alert-success    { background: #dcfce3; color: #15803d; border: 1px solid #bbf7d0; }
.alert-error      { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.action-link      { font-size: 13px; font-weight: 500; text-decoration: none; margin-right: 8px; }
.action-link.complete { color: #10b981; }
.action-link.delete   { color: #ef4444; }
@media (max-width: 992px) { .users-container { flex-direction: column-reverse; } .user-form-card { width: 100%; position: static; } }
</style>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">Trips & Operations</h1>
        <p class="page-subtitle">Track and manage all trip activities</p>
    </div>
    <div class="header-right">
        <div class="date-display"></div>
    </div>
</header>

<section class="content-section">
    <?php if ($msg):   echo "<div class='alert alert-success'>$msg</div>"; endif; ?>
    <?php if ($error): echo "<div class='alert alert-error'>$error</div>"; endif; ?>

    <div class="users-container">
        <!-- Trips Table -->
        <div class="users-list table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bus</th>
                        <th>Route</th>
                        <th>Driver</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($trips) > 0): ?>
                        <?php foreach ($trips as $t): ?>
                            <tr>
                                <td><strong>#<?php echo $t['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($t['bus_number'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($t['route_name'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($t['driver_name'] ?? '—'); ?></td>
                                <td><?php echo $t['start_time'] ? date('d M, h:i A', strtotime($t['start_time'])) : '—'; ?></td>
                                <td><?php echo $t['end_time']   ? date('d M, h:i A', strtotime($t['end_time']))   : '—'; ?></td>
                                <td>
                                    <?php
                                    $sc = match($t['status']) {
                                        'completed' => 'active',
                                        'ongoing'   => 'ongoing',
                                        'cancelled' => 'maintenance',
                                        default     => 'idle'
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $sc; ?>"><?php echo ucfirst($t['status']); ?></span>
                                </td>
                                <td>
                                    <?php if ($t['status'] === 'ongoing' || $t['status'] === 'scheduled'): ?>
                                        <a href="trips.php?complete_id=<?php echo $t['id']; ?>" class="action-link complete" onclick="return confirm('Mark as completed?');">✓ Complete</a>
                                    <?php endif; ?>
                                    <a href="trips.php?delete_id=<?php echo $t['id']; ?>" class="action-link delete" onclick="return confirm('Delete this trip?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; color:#64748b;">No trips recorded yet. Add one →</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Trip Form -->
        <div class="user-form-card">
            <h3 style="margin-bottom: 20px;">Add New Trip</h3>
            <form method="POST" action="trips.php">
                <input type="hidden" name="action" value="add_trip">

                <div class="form-group">
                    <label class="form-label">Bus</label>
                    <select name="bus_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($buses as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['bus_number']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Route</label>
                    <select name="route_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($routes as $r): ?>
                            <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['route_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Driver</label>
                    <select name="driver_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Start Date & Time *</label>
                    <input type="datetime-local" name="start_time" class="form-input" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">End Date & Time</label>
                    <input type="datetime-local" name="end_time" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-textarea" placeholder="Optional notes..."></textarea>
                </div>

                <button type="submit" class="btn-submit">Add Trip</button>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
