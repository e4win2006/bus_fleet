<?php
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole(['driver', 'conductor']);

$page = 'driver-alerts';
$page_title = 'Maintenance Alerts - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

include __DIR__ . '/includes/header.php';
?>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">Maintenance Alerts</h1>
        <p class="page-subtitle">Important service reminders and safety alerts.</p>
    </div>
</header>

<section class="content-section">
    <?php include __DIR__ . '/includes/weather_alert.php'; ?>
    <div class="alert alert-error" style="background: #fef08a; color: #854d0e; border: 1px solid #fde047; padding: 16px; border-radius: 8px;">
        <strong>Notice:</strong> Your bus is scheduled for an oil change in 200km. Please notify the fleet manager if you notice any engine irregularities before then.
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
