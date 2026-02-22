<?php
session_start();
$error_msg = '';

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid') {
        $error_msg = 'Invalid username or password.';
    } elseif ($_GET['error'] === 'empty') {
        $error_msg = 'Please enter both username and password.';
    } elseif ($_GET['error'] === 'unauthorized') {
        $error_msg = 'Please log in to access the dashboard.';
    } elseif ($_GET['error'] === 'pending') {
        $error_msg = 'Your account is pending approval by an administrator.';
    } elseif ($_GET['error'] === 'rejected') {
        $error_msg = 'Your account has been rejected by an administrator.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FleetVision</title>
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
        .link-home { display: inline-block; margin-top: 1rem; color: #64748b; text-decoration: none; font-size: 0.875rem; }
        .link-home:hover { color: #333; }
        .btn-google { display: flex; align-items: center; justify-content: center; width: 100%; padding: 0.75rem; background: white; color: #333; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s, box-shadow 0.2s; font-family: inherit; margin-bottom: 1rem; text-decoration: none; box-sizing: border-box; }
        .btn-google:hover { background: #f8fafc; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); }
        .btn-google img { height: 18px; margin-right: 10px; }
        .divider { display: flex; align-items: center; text-align: center; margin: 1.5rem 0; color: #94a3b8; font-size: 0.875rem; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #e2e8f0; }
        .divider::before { margin-right: 0.75rem; }
        .divider::after { margin-left: 0.75rem; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="auth-logo">🚌</div>
            <h1 class="auth-title">Welcome Back</h1>
        </div>

        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <a href="google_auth.php" class="btn-google">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google logo">
            Sign in with Google
        </a>

        <div class="divider">or sign in with email</div>

        <form method="POST" action="auth.php">
            <div class="form-group">
                <label class="form-label" for="username">Username or Email</label>
                <input type="text" id="username" name="username" class="form-input" required autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Sign up</a><br>
            <a href="index.php" class="link-home">&larr; Back to Home</a>
        </div>
    </div>
</body>
</html>
