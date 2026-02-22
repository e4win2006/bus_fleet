<?php
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole('admin');

$page = 'settings';
$page_title = 'System Settings - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

include __DIR__ . '/includes/header.php';
?>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">System Settings</h1>
        <p class="page-subtitle">Manage global application configurations.</p>
    </div>
</header>

<section class="content-section">
    <div class="user-form-card" style="max-width: 600px;">
        <h3>General Preferences</h3>
        
        <form style="margin-top: 20px;">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 500; margin-bottom: 5px;">Company Name</label>
                <input type="text" value="FleetVision Transit" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" disabled>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 500; margin-bottom: 5px;">Default Timezone</label>
                <select style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" disabled>
                    <option>UTC-5 (Eastern Time)</option>
                    <option>UTC+0 (GMT)</option>
                </select>
            </div>
            
            <button type="button" disabled style="padding: 10px 15px; background: #94a3b8; color: white; border: none; border-radius: 4px; cursor: not-allowed;">Save Settings (Demo)</button>
        </form>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
