<?php
// includes/auth_middleware.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensures the currently logged-in user's role is within the allowed roles array.
 * If not, they are redirected to the dashboard (or login if no session).
 *
 * @param array|string $allowed_roles Array of allowed roles or a single role string
 */
function requireRole($allowed_roles) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: login.php?error=unauthorized");
        exit;
    }

    $user_role = $_SESSION['role'];

    // Convert string to array for easier checking
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    // Convert everything to lowercase to avoid case-sensitivity issues
    $allowed_roles = array_map('strtolower', $allowed_roles);
    $user_role = strtolower($user_role);

    if (!in_array($user_role, $allowed_roles)) {
        header("Location: dashboard.php");
        exit;
    }
}
?>
