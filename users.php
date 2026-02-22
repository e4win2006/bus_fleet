<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';

requireRole('admin');

$page = 'users';
$page_title = 'User Management - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

$msg = '';
$error = '';

// Handle form submission for adding a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = isset($_POST['status']) ? $_POST['status'] : 'approved';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Username, email, and password are required.";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username or email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$username, $email, $hash, $role, $status]);
                $msg = "User created successfully.";
            } catch (PDOException $e) {
                $error = "Error adding user: " . $e->getMessage();
            }
        }
    }
}

// Handle deleting a user
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    if ($delete_id === (int)$_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET status = 'deleted' WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            $msg = "User successfully deleted.";
        } else {
            $error = "Failed to delete user.";
        }
    }
}

// Fetch all non-deleted users
$stmt = $pdo->query("SELECT id, username, email, role, status, created_at FROM users WHERE status != 'deleted' ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<style>
.users-container {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}

.users-list {
    flex: 2;
}

.user-form-card {
    flex: 1;
    background: white;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    position: sticky;
    top: 24px;
}

.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: 500;
    color: #475569;
}

.form-input, .form-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    box-sizing: border-box;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-submit {
    width: 100%;
    padding: 10px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s;
}

.btn-submit:hover {
    background: #2563eb;
}

.alert {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 14px;
}

.alert-success { background: #dcfce3; color: #15803d; border: 1px solid #bbf7d0; }
.alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

@media (max-width: 992px) {
    .users-container {
        flex-direction: column-reverse;
    }
    .user-form-card {
        position: static;
        width: 100%;
    }
}
</style>

<!-- Header -->
<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage system users, roles, and access.</p>
    </div>
</header>

<section class="content-section">
    <?php if ($msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="users-container">
        <!-- Users List Table -->
        <div class="users-list table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td style="font-weight: 500; color: #0f172a;"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <form method="POST" action="users.php" style="margin: 0;">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="new_role" class="form-select role-select" onchange="this.form.submit()">
                                            <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>Normal User</option>
                                            <option value="driver" <?php echo $u['role'] === 'driver' ? 'selected' : ''; ?>>Driver</option>
                                            <option value="conductor" <?php echo $u['role'] === 'conductor' ? 'selected' : ''; ?>>Conductor</option>
                                            <option value="fleet_manager" <?php echo $u['role'] === 'fleet_manager' ? 'selected' : ''; ?>>Fleet Manager</option>
                                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <?php
                                        $statusClass = $u['status'] === 'approved' ? 'active' : ($u['status'] === 'pending' ? 'idle' : 'cancelled');
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst(htmlspecialchars($u['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                    <a href="users.php?delete_id=<?php echo $u['id']; ?>" style="color: #ef4444; font-size: 13px; font-weight: 500; text-decoration: none;" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center;">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add User Form -->
        <div class="user-form-card">
            <h3 style="margin-bottom: 20px; font-size: 18px; color: #0f172a;">Add New User</h3>
            <form method="POST" action="users.php">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Temporary Password</label>
                    <input type="text" id="password" name="password" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="role">Role</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="driver">Driver</option>
                        <option value="technician">Technician</option>
                        <option value="conductor">Conductor</option>
                        <option value="service">Service Staff</option>
                        <option value="user">Standard User</option>
                        <option value="admin">Admin / Owner</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Account Status</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="approved">Approved (Active)</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Create User</button>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
