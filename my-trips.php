<?php
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole(['driver', 'conductor']);

$page = 'my-trips';
$page_title = 'My Trips - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

include __DIR__ . '/includes/header.php';
?>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">My Trips</h1>
        <p class="page-subtitle">View your currently assigned routes and schedule.</p>
    </div>
</header>

<section class="content-section">
    <?php include __DIR__ . '/includes/weather_alert.php'; ?>
    <div class="user-form-card">
        <h3>Today's Schedule</h3>
        <p style="color: #64748b; margin-top: 10px;">You have 4 trips assigned today. Route A - Downtown starts at 08:00 AM.</p>
        
        <div style="margin-top: 20px;">
            <p><strong>08:00 AM</strong> - Route A (Downtown) - <em>Pending</em></p>
            <p><strong>10:30 AM</strong> - Route A (Downtown) - <em>Pending</em></p>
            <p><strong>01:00 PM</strong> - Route B (Suburbs) - <em>Pending</em></p>
            <p><strong>03:30 PM</strong> - Route B (Suburbs) - <em>Pending</em></p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
