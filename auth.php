<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty");
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, password_hash, username, role, status FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] === 'pending') {
            header("Location: login.php?error=pending");
            exit;
        } elseif ($user['status'] === 'rejected') {
            header("Location: login.php?error=rejected");
            exit;
        }

        // Login success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Prevent session fixation
        session_regenerate_id(true);

        header("Location: dashboard.php");
        exit;
    } else {
        // Login failed
        header("Location: login.php?error=invalid");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
