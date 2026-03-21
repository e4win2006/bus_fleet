<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already taken.';
        } else {
            // Determine role and status based on email
            $role = ($email === 'edwintomjoseph41@gmail.com') ? 'admin' : 'user';
            $status = ($email === 'edwintomjoseph41@gmail.com') ? 'approved' : 'pending';

            // Hash password and insert
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$username, $email, $hash, $role, $status]);
                
                if ($status === 'pending') {
                    $success = 'Registration successful! Your account is <strong>pending approval</strong> by an administrator. You cannot log in yet.';
                } else {
                    $success = 'Registration successful! You can now <a href="login.php">login</a>.';
                }
            } catch (PDOException $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FleetVision</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f8fafc; margin: 0; }
        .auth-container { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; }
        .auth-header { text-align: center; margin-bottom: 2rem; }
        .auth-logo { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .auth-title { font-size: 1.5rem; color: #1e293b; font-weight: 600; margin: 0; }
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: #475569; font-size: 0.875rem; font-weight: 500; }
        .form-input { width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; box-sizing: border-box; }
        .form-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-submit { width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; font-family: inherit; }
        .btn-submit:hover { background: #2563eb; }
        .auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #64748b; }
        .auth-footer a { color: #3b82f6; text-decoration: none; font-weight: 500; }
        .alert { padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce3; color: #15803d; border: 1px solid #bbf7d0; }

    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <img src="FleetVision Logo.png" alt="FleetVision Logo" class="auth-logo" style="height:64px;width:auto;object-fit:contain;">
            <h1 class="auth-title">Create an Account</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>


            <form method="POST" action="register.php">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn-submit">Sign Up</button>
            </form>
        <?php endif; ?>
        
        <div class="auth-footer">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</body>
</html>
