<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';

requireRole('admin');

$page = 'staff';
$page_title = 'Drivers & Conductors - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

$msg = '';
$error = '';

// ── Add Staff Member ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_staff') {
    $username = trim($_POST['username']);
    $role = in_array($_POST['role'], ['driver', 'conductor']) ? $_POST['role'] : 'driver';
    $contact_no = trim($_POST['contact_no'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $blood_type = trim($_POST['blood_type'] ?? '');

    // Auto-generate account details — staff do not log in
    $email = strtolower(preg_replace('/\s+/', '.', $username)) . '.' . time() . '@staff.internal';
    $password = bin2hex(random_bytes(16)); // random, never shared
    if (empty($username)) {
        $error = 'Full name is required.';
    }
    else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already exists.';
        }
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, status, contact_no, license_number, emergency_contact, blood_type) VALUES (?, ?, ?, ?, 'approved', ?, ?, ?, ?)");
            try {
                $stmt->execute([$username, $email, $hash, $role, $contact_no, $role === 'driver' ? $license_number : null, $emergency_contact, $blood_type]);
                $msg = ucfirst($role) . ' "' . htmlspecialchars($username) . '" added successfully.';
            }
            catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// ── Edit Staff Member ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_staff') {
    $uid = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $role = in_array($_POST['role'], ['driver', 'conductor']) ? $_POST['role'] : 'driver';
    $contact_no = trim($_POST['contact_no'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $blood_type = trim($_POST['blood_type'] ?? '');

    if (empty($username)) {
        $error = 'Full name is required.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username=?, role=?, contact_no=?, license_number=?, emergency_contact=?, blood_type=? WHERE id=?");
        try {
            $stmt->execute([$username, $role, $contact_no, $role === 'driver' ? $license_number : null, $emergency_contact, $blood_type, $uid]);
            $msg = 'Staff member updated successfully.';
        } catch (PDOException $e) {
            $error = 'Error updating staff: ' . $e->getMessage();
        }
    }
}

// ── Change Role ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_role') {
    $uid = (int)$_POST['user_id'];
    $new_role = in_array($_POST['new_role'], ['driver', 'conductor']) ? $_POST['new_role'] : 'driver';
    $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $uid]);
    $msg = 'Role updated.';
}

// ── Toggle Status ─────────────────────────────────────────────────────────
if (isset($_GET['toggle_id'])) {
    $uid = (int)$_GET['toggle_id'];
    $current = $pdo->query("SELECT status FROM users WHERE id = $uid")->fetchColumn();
    $new_status = $current === 'approved' ? 'pending' : 'approved';
    $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new_status, $uid]);
    $msg = 'Status updated to ' . $new_status . '.';
}

// ── Delete ────────────────────────────────────────────────────────────────
if (isset($_GET['delete_id'])) {
    $uid = (int)$_GET['delete_id'];
    if ($uid === (int)$_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    }
    else {
        $pdo->prepare("UPDATE users SET status = 'deleted' WHERE id = ?")->execute([$uid]);
        $msg = 'Staff member removed.';
    }
}

// ── Fetch Drivers & Conductors ────────────────────────────────────────────
$staff = $pdo->query("
    SELECT u.*,
           b.bus_number AS assigned_bus
    FROM users u
    LEFT JOIN buses b ON b.driver_id = u.id OR b.conductor_id = u.id
    WHERE u.role IN ('driver','conductor') AND u.status != 'deleted'
    GROUP BY u.id
    ORDER BY u.role ASC, u.username ASC
")->fetchAll(PDO::FETCH_ASSOC);

$drivers = array_filter($staff, fn($s) => $s['role'] === 'driver');
$conductors = array_filter($staff, fn($s) => $s['role'] === 'conductor');

include __DIR__ . '/includes/header.php';
?>

<style>
.staff-layout     { display: flex; gap: 24px; align-items: flex-start; }
.staff-main       { flex: 2; }
.staff-sidebar    { flex: 1; position: sticky; top: 24px; }
.user-form-card   { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
.form-group       { margin-bottom: 14px; }
.form-label       { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; color: #475569; }
.form-input, .form-select { width: 100%; padding: 9px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; font-family: inherit; }
.form-input:focus, .form-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.btn-submit       { width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-submit:hover { background: #2563eb; }
.alert            { padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
.alert-success    { background: #dcfce3; color: #15803d; border: 1px solid #bbf7d0; }
.alert-error      { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.section-label    { font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 20px 0 10px; }
.action-link      { font-size: 12px; font-weight: 600; text-decoration: none; margin-right: 8px; }
.action-link.toggle-on  { color: #f59e0b; }
.action-link.toggle-off { color: #10b981; }
.action-link.delete     { color: #ef4444; }
.role-pill        { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.role-pill.driver     { background: #dbeafe; color: #1d4ed8; }
.role-pill.conductor  { background: #fef3c7; color: #92400e; }
.empty-row td     { text-align: center; color: #94a3b8; padding: 24px !important; }
.hover-row        { transition: background-color 0.2s ease; }
.hover-row:hover  { background-color: #f8fafc; }
@media (max-width: 992px) { .staff-layout { flex-direction: column-reverse; } .staff-sidebar { width: 100%; position: static; } }
</style>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">Drivers & Conductors</h1>
        <p class="page-subtitle">Manage fleet staff — add, assign roles, and control access</p>
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

    <div class="staff-layout">
        <!-- Left: Tables -->
        <div class="staff-main">

            <!-- Drivers -->
            <div class="section-label">🚗 Drivers (<?php echo count($drivers); ?>)</div>
            <div class="table-container" style="margin-bottom: 24px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>License No.</th>
                            <th>Blood Type</th>
                            <th>Emergency</th>
                            <th>Assigned Bus</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($drivers) > 0): ?>
                            <?php foreach ($drivers as $d): ?>
                                <?php
                                $jsdata = htmlspecialchars(json_encode([
                                    'id' => $d['id'], 'username' => $d['username'], 'role' => $d['role'], 'contact_no' => $d['contact_no'], 
                                    'license_number' => $d['license_number'], 'emergency_contact' => $d['emergency_contact'], 'blood_type' => $d['blood_type']
                                ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr onclick="editStaff(this)" data-staff="<?php echo $jsdata; ?>" class="hover-row" style="cursor: pointer;" title="Click to edit">
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($d['username']); ?></td>
                                    <td><?php echo htmlspecialchars($d['contact_no'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($d['license_number'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($d['blood_type'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($d['emergency_contact'] ?: '—'); ?></td>
                                    <td><?php echo $d['assigned_bus'] ? '<strong>' . htmlspecialchars($d['assigned_bus']) . '</strong>' : '<span style="color:#94a3b8;">Unassigned</span>'; ?></td>
                                    <td>
                                        <?php $sc = $d['status'] === 'approved' ? 'active' : 'idle'; ?>
                                        <span class="status-badge <?php echo $sc; ?>"><?php echo ucfirst($d['status']); ?></span>
                                    </td>
                                    <td>
                                        <a href="staff.php?toggle_id=<?php echo $d['id']; ?>" class="action-link <?php echo $d['status'] === 'approved' ? 'toggle-on' : 'toggle-off'; ?>" onclick="event.stopPropagation();">
                                            <?php echo $d['status'] === 'approved' ? '⏸ Suspend' : '▶ Activate'; ?>
                                        </a>
                                        <a href="staff.php?delete_id=<?php echo $d['id']; ?>" class="action-link delete" onclick="event.stopPropagation(); return confirm('Remove this driver?');">🗑 Remove</a>
                                    </td>
                                </tr>
                            <?php
    endforeach; ?>
                        <?php
else: ?>
                            <tr class="empty-row"><td colspan="8">No drivers added yet.</td></tr>
                        <?php
endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Conductors -->
            <div class="section-label">🎫 Conductors (<?php echo count($conductors); ?>)</div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Blood Type</th>
                            <th>Emergency</th>
                            <th>Assigned Bus</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($conductors) > 0): ?>
                            <?php foreach ($conductors as $c): ?>
                                <?php
                                $jsdata = htmlspecialchars(json_encode([
                                    'id' => $c['id'], 'username' => $c['username'], 'role' => $c['role'], 'contact_no' => $c['contact_no'], 
                                    'license_number' => $c['license_number'], 'emergency_contact' => $c['emergency_contact'], 'blood_type' => $c['blood_type']
                                ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr onclick="editStaff(this)" data-staff="<?php echo $jsdata; ?>" class="hover-row" style="cursor: pointer;" title="Click to edit">
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($c['username']); ?></td>
                                    <td><?php echo htmlspecialchars($c['contact_no'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($c['blood_type'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($c['emergency_contact'] ?: '—'); ?></td>
                                    <td><?php echo $c['assigned_bus'] ? '<strong>' . htmlspecialchars($c['assigned_bus']) . '</strong>' : '<span style="color:#94a3b8;">Unassigned</span>'; ?></td>
                                    <td>
                                        <?php $sc = $c['status'] === 'approved' ? 'active' : 'idle'; ?>
                                        <span class="status-badge <?php echo $sc; ?>"><?php echo ucfirst($c['status']); ?></span>
                                    </td>
                                    <td>
                                        <a href="staff.php?toggle_id=<?php echo $c['id']; ?>" class="action-link <?php echo $c['status'] === 'approved' ? 'toggle-on' : 'toggle-off'; ?>" onclick="event.stopPropagation();">
                                            <?php echo $c['status'] === 'approved' ? '⏸ Suspend' : '▶ Activate'; ?>
                                        </a>
                                        <a href="staff.php?delete_id=<?php echo $c['id']; ?>" class="action-link delete" onclick="event.stopPropagation(); return confirm('Remove this conductor?');">🗑 Remove</a>
                                    </td>
                                </tr>
                            <?php
    endforeach; ?>
                        <?php
else: ?>
                            <tr class="empty-row"><td colspan="7">No conductors added yet.</td></tr>
                        <?php
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Add Form -->
        <div class="staff-sidebar">
            <div class="user-form-card">
                <h3 id="form-title" style="margin-bottom:18px; font-size:17px;">Add Driver / Conductor</h3>
                <form method="POST" action="staff.php" id="staff-form">
                    <input type="hidden" name="action" id="form-action" value="add_staff">
                    <input type="hidden" name="user_id" id="form-user-id" value="">

                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" id="form-role" class="form-select" required onchange="toggleLicense(this.value)">
                            <option value="driver">🚗 Driver</option>
                            <option value="conductor">🎫 Conductor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="username" id="form-username" class="form-input" placeholder="e.g. John Kumar" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="tel" name="contact_no" id="form-contact_no" class="form-input" placeholder="e.g. +91 98765 43210">
                    </div>

                    <div class="form-group" id="license-field">
                        <label class="form-label">License Number <span style="color:#3b82f6;">(Driver only)</span></label>
                        <input type="text" name="license_number" id="form-license_number" class="form-input" placeholder="e.g. KL-01-20200012345">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" name="emergency_contact" id="form-emergency_contact" class="form-input" placeholder="Name & phone number">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Blood Type</label>
                        <input type="text" name="blood_type" id="form-blood_type" class="form-input" placeholder="e.g. A+, B-, O+, AB+">
                    </div>

                    <button type="submit" class="btn-submit" id="btn-submit">Add Staff Member</button>
                    <button type="button" class="btn-cancel" id="btn-cancel" style="margin-top: 10px; display:none; background:#94a3b8; color:white; width:100%; padding:10px; border:none; border-radius:6px; font-weight:600; cursor:pointer;" onclick="cancelEdit()">Cancel Edit</button>
                    <script>
                    function toggleLicense(role) {
                        document.getElementById('license-field').style.display = role === 'driver' ? 'block' : 'none';
                    }
                    function editStaff(el) {
                        var data = JSON.parse(el.getAttribute('data-staff'));
                        document.getElementById('form-action').value = 'edit_staff';
                        document.getElementById('form-user-id').value = data.id;
                        document.getElementById('form-role').value = data.role || 'driver';
                        document.getElementById('form-username').value = data.username || '';
                        document.getElementById('form-contact_no').value = data.contact_no || '';
                        document.getElementById('form-license_number').value = data.license_number || '';
                        document.getElementById('form-emergency_contact').value = data.emergency_contact || '';
                        document.getElementById('form-blood_type').value = data.blood_type || '';
                        toggleLicense(data.role || 'driver');
                        
                        document.getElementById('form-title').innerText = 'Edit Staff Details';
                        document.getElementById('btn-submit').innerText = 'Save Changes';
                        document.getElementById('btn-cancel').style.display = 'block';
                        window.scrollTo({top: 0, behavior: 'smooth'});
                    }
                    function cancelEdit() {
                        document.getElementById('form-action').value = 'add_staff';
                        document.getElementById('form-user-id').value = '';
                        document.getElementById('form-role').value = 'driver';
                        document.getElementById('form-username').value = '';
                        document.getElementById('form-contact_no').value = '';
                        document.getElementById('form-license_number').value = '';
                        document.getElementById('form-emergency_contact').value = '';
                        document.getElementById('form-blood_type').value = '';
                        toggleLicense('driver');
                        
                        document.getElementById('form-title').innerText = 'Add Driver / Conductor';
                        document.getElementById('btn-submit').innerText = 'Add Staff Member';
                        document.getElementById('btn-cancel').style.display = 'none';
                    }
                    </script>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
