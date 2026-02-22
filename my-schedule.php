<?php
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole('user');

$page = 'my-schedule';
$page_title = 'My Schedule - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

include __DIR__ . '/includes/header.php';
?>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">My Booked Schedule</h1>
        <p class="page-subtitle">View your upcoming trips and bus boarding times.</p>
    </div>
</header>

<section class="content-section">
    <?php include __DIR__ . '/includes/weather_alert.php'; ?>
    <div class="user-form-card">
        <h3>Upcoming Trip</h3>
        <p style="color: #64748b; margin-top: 10px;">You are scheduled for the 8:00 AM route to Downtown.</p>
        <div style="margin-top: 20px;">
            <p><strong>Boarding Time:</strong> 7:45 AM</p>
            <p><strong>Bus:</strong> BUS-001</p>
            <p><strong>Status:</strong> On Time</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
