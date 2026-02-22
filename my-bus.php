<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole(['driver', 'conductor']);

$page = 'my-bus';
$page_title = 'My Bus - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

// Fetch the bus assigned to this specific driver or conductor
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT b.bus_number, b.make, b.model, b.capacity, b.status,
           r.route_name, r.start_location, r.end_location,
           uc.username as conductor_name,
           ud.username as driver_name
    FROM buses b
    LEFT JOIN routes r ON b.route_id = r.id
    LEFT JOIN users uc ON b.conductor_id = uc.id
    LEFT JOIN users ud ON b.driver_id = ud.id
    WHERE (b.driver_id = ? OR b.conductor_id = ?) AND b.status != 'deleted'
    LIMIT 1
");
$stmt->execute([$user_id, $user_id]);
$my_bus = $stmt->fetch(PDO::FETCH_ASSOC);

$current_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
$is_driver = ($current_role === 'driver');
$partner_label = $is_driver ? 'Conductor' : 'Driver';
$partner_name = $is_driver ? ($my_bus['conductor_name'] ?? null) : ($my_bus['driver_name'] ?? null);

include __DIR__ . '/includes/header.php';
?>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">My Bus Status</h1>
        <p class="page-subtitle">Check the current status and metrics of your assigned vehicle.</p>
    </div>
</header>

<section class="content-section">
    <?php include __DIR__ . '/includes/weather_alert.php'; ?>
    
    <?php if ($my_bus): ?>
        <div class="user-form-card">
            <h3><?php echo htmlspecialchars($my_bus['bus_number']); ?> (<?php echo htmlspecialchars($my_bus['make'] . ' ' . $my_bus['model']); ?>)</h3>
            <p style="color: #64748b; margin-top: 10px;">
                Status: <span class="status-badge <?php echo $my_bus['status'] === 'active' ? 'active' : 'idle'; ?>"><?php echo ucfirst(htmlspecialchars($my_bus['status'])); ?></span>
            </p>
            
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h4 style="color: #475569; margin-bottom: 12px;"><i data-lucide="route" style="width: 16px; height: 16px; vertical-align: middle;"></i> Route Assignment</h4>
                    <?php if ($my_bus['route_name']): ?>
                        <p style="font-weight: 500; font-size: 16px;"><?php echo htmlspecialchars($my_bus['route_name']); ?></p>
                        <p style="color: #64748b; font-size: 14px; margin-top: 4px;"><?php echo htmlspecialchars($my_bus['start_location'] . ' ➔ ' . $my_bus['end_location']); ?></p>
                    <?php else: ?>
                        <p style="color: #94a3b8;">No active route assigned.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <h4 style="color: #475569; margin-bottom: 12px;"><i data-lucide="users" style="width: 16px; height: 16px; vertical-align: middle;"></i> Crew Partner</h4>
                    <?php if ($partner_name): ?>
                        <p style="font-weight: 500; font-size: 16px;"><?php echo $partner_label; ?>: <?php echo htmlspecialchars($partner_name); ?></p>
                        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Capacity Limit: <?php echo htmlspecialchars($my_bus['capacity']); ?> passengers</p>
                    <?php else: ?>
                        <p style="color: #94a3b8;">No <?php echo strtolower($partner_label); ?> assigned.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
            
            <div style="margin-top: 20px;">
                <p><strong>Fuel Level:</strong> 68%</p>
                <p><strong>Estimated Range:</strong> 320km</p>
            </div>
        </div>
    <?php else: ?>
        <div class="user-form-card" style="text-align: center; padding: 40px 20px;">
            <i data-lucide="bus" style="width: 48px; height: 48px; color: #94a3b8; margin-bottom: 16px;"></i>
            <h3>No Bus Assigned</h3>
            <p style="color: #64748b; margin-top: 8px;">You currently are not assigned to any active vehicle. Please contact your Fleet Manager.</p>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
