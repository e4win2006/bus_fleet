<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';

requireRole(['admin', 'fleet_manager', 'fleet manager']);

$page = 'maintenance';
$page_title = 'Maintenance - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

$msg = '';
$error = '';

$today = date('Y-m-d');

// ── Add Maintenance (writes to services table) ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_maintenance') {
    $bus_id = (int)$_POST['bus_id'];
    $service_type = trim($_POST['service_type']);
    $description = trim($_POST['description'] ?? '');
    $service_date = $_POST['service_date'];
    $priority = $_POST['priority'] ?? 'medium';

    if (empty($bus_id) || empty($service_type) || empty($service_date)) {
        $error = 'Bus, service type and date are required.';
    }
    else {
        $stmt = $pdo->prepare("INSERT INTO services (bus_id, service_type, description, service_date, status, cost) VALUES (?, ?, ?, ?, 'scheduled', 0)");
        try {
            $stmt->execute([$bus_id, $service_type, $description, $service_date]);
            $msg = 'Maintenance scheduled successfully.';
        }
        catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// ── Mark as Complete ──────────────────────────────────────────────────────
if (isset($_GET['complete_id'])) {
    $pdo->prepare("UPDATE services SET status = 'completed' WHERE id = ?")->execute([(int)$_GET['complete_id']]);
    $msg = 'Marked as completed.';
}

// ── Delete ────────────────────────────────────────────────────────────────
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([(int)$_GET['delete_id']]);
    $msg = 'Record deleted.';
}

// ── Fetch pending/overdue services ────────────────────────────────────────
$pending = $pdo->query("
    SELECT s.*, b.bus_number
    FROM services s
    LEFT JOIN buses b ON s.bus_id = b.id
    WHERE s.status IN ('scheduled', 'ongoing')
    ORDER BY s.service_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Fetch completed for history ───────────────────────────────────────────
$completed = $pdo->query("
    SELECT s.*, b.bus_number
    FROM services s
    LEFT JOIN buses b ON s.bus_id = b.id
    WHERE s.status = 'completed'
    ORDER BY s.service_date DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// ── Buses dropdown ────────────────────────────────────────────────────────
$buses = $pdo->query("SELECT id, bus_number FROM buses WHERE status != 'deleted' ORDER BY bus_number")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<style>
.maint-layout       { display: flex; gap: 24px; align-items: flex-start; }
.maint-main         { flex: 2; }
.maint-sidebar      { flex: 1; position: sticky; top: 24px; }
.user-form-card     { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
.form-group         { margin-bottom: 14px; }
.form-label         { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; color: #475569; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 9px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; font-family: inherit; }
.form-textarea      { resize: vertical; min-height: 56px; }
.btn-submit         { width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-submit:hover   { background: #2563eb; }
.alert              { padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
.alert-success      { background: #dcfce3; color: #15803d; border: 1px solid #bbf7d0; }
.alert-error        { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

/* Maintenance cards */
.maintenance-grid   { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin-bottom: 32px; }
.maintenance-card   { background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; border-left: 4px solid #e2e8f0; }
.maintenance-card.overdue { border-left-color: #ef4444; background: #fff5f5; }
.maintenance-card.due-soon { border-left-color: #f59e0b; background: #fffbeb; }
.maintenance-card.ongoing-card { border-left-color: #3b82f6; }
.m-header           { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.m-bus              { font-weight: 700; font-size: 15px; color: #1e293b; }
.m-badge            { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
.m-badge.overdue    { background: #fee2e2; color: #b91c1c; }
.m-badge.due-soon   { background: #fef3c7; color: #92400e; }
.m-badge.ongoing-b  { background: #dbeafe; color: #1d4ed8; }
.m-badge.scheduled  { background: #f1f5f9; color: #475569; }
.m-type             { font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 6px; }
.m-detail           { font-size: 12px; color: #64748b; margin-bottom: 3px; }
.m-actions          { margin-top: 12px; display: flex; gap: 8px; }
.m-btn              { flex: 1; padding: 6px; text-align: center; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; }
.m-btn.complete-btn { background: #dcfce7; color: #15803d; }
.m-btn.complete-btn:hover { background: #bbf7d0; }
.m-btn.delete-btn   { background: #fee2e2; color: #b91c1c; }
.m-btn.delete-btn:hover   { background: #fecaca; }
.empty-state        { text-align: center; padding: 40px; color: #94a3b8; background: white; border-radius: 12px; border: 1px dashed #e2e8f0; }
.section-label      { font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 14px; margin-top: 4px; }
@media (max-width: 992px) { .maint-layout { flex-direction: column-reverse; } .maint-sidebar { width: 100%; position: static; } }
</style>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">Maintenance Tracking</h1>
        <p class="page-subtitle">Monitor and schedule vehicle maintenance</p>
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

    <div class="maint-layout">
        <!-- Left: Cards -->
        <div class="maint-main">
            <div class="section-label">Pending Maintenance</div>

            <?php if (count($pending) > 0): ?>
                <div class="maintenance-grid">
                    <?php foreach ($pending as $s):
        $isOverdue = $s['service_date'] < $today && $s['status'] === 'scheduled';
        $daysLeft = (strtotime($s['service_date']) - strtotime($today)) / 86400;
        $isDueSoon = !$isOverdue && $daysLeft <= 7;
        $cardClass = $isOverdue ? 'overdue' : ($isDueSoon ? 'due-soon' : ($s['status'] === 'ongoing' ? 'ongoing-card' : ''));
        $badgeClass = $isOverdue ? 'overdue' : ($isDueSoon ? 'due-soon' : ($s['status'] === 'ongoing' ? 'ongoing-b' : 'scheduled'));
        $badgeText = $isOverdue ? 'Overdue' : ($isDueSoon ? 'Due Soon' : ucfirst($s['status']));
?>
                        <div class="maintenance-card <?php echo $cardClass; ?>">
                            <div class="m-header">
                                <span class="m-bus"><?php echo htmlspecialchars($s['bus_number'] ?? 'Unknown'); ?></span>
                                <span class="m-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                            </div>
                            <div class="m-type"><?php echo htmlspecialchars($s['service_type']); ?></div>
                            <?php if ($s['description']): ?>
                                <div class="m-detail">📝 <?php echo htmlspecialchars($s['description']); ?></div>
                            <?php
        endif; ?>
                            <div class="m-detail">📅 <?php echo date('d M Y', strtotime($s['service_date'])); ?></div>
                            <?php if ($s['cost'] > 0): ?>
                                <div class="m-detail">💰 ₹<?php echo number_format($s['cost'], 2); ?></div>
                            <?php
        endif; ?>
                            <div class="m-actions">
                                <a href="maintenance.php?complete_id=<?php echo $s['id']; ?>" class="m-btn complete-btn" onclick="return confirm('Mark as complete?');">✓ Complete</a>
                                <a href="maintenance.php?delete_id=<?php echo $s['id']; ?>"  class="m-btn delete-btn"   onclick="return confirm('Delete this record?');">🗑 Delete</a>
                            </div>
                        </div>
                    <?php
    endforeach; ?>
                </div>
            <?php
else: ?>
                <div class="empty-state">
                    <div style="font-size:2rem; margin-bottom:8px;">✅</div>
                    <div style="font-weight:600; color:#334155;">All clear!</div>
                    <div style="margin-top:4px;">No pending maintenance. Schedule one using the form →</div>
                </div>
            <?php
endif; ?>

            <!-- Completed History -->
            <?php if (count($completed) > 0): ?>
                <div class="section-label" style="margin-top:28px;">Recent Completed</div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr><th>Bus</th><th>Type</th><th>Date</th><th>Cost</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed as $c): ?>
                                <tr>
                                    <td style="font-weight:500;"><?php echo htmlspecialchars($c['bus_number'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($c['service_type']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($c['service_date'])); ?></td>
                                    <td>₹<?php echo number_format($c['cost'], 2); ?></td>
                                </tr>
                            <?php
    endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php
endif; ?>
        </div>

        <!-- Right: Add Form -->
        <div class="maint-sidebar">
            <div class="user-form-card">
                <h3 style="margin-bottom:18px;">Schedule Maintenance</h3>
                <form method="POST" action="maintenance.php">
                    <input type="hidden" name="action" value="add_maintenance">

                    <div class="form-group">
                        <label class="form-label">Bus *</label>
                        <select name="bus_id" class="form-select" required>
                            <option value="">Select Bus...</option>
                            <?php foreach ($buses as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['bus_number']); ?></option>
                            <?php
endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Service Type *</label>
                        <input type="text" name="service_type" class="form-input" placeholder="e.g. Oil Change" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description / Notes</label>
                        <textarea name="description" class="form-textarea" placeholder="Optional details..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Due Date *</label>
                        <input type="date" name="service_date" class="form-input" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>

                    <button type="submit" class="btn-submit">Schedule</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
