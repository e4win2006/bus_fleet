<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';

// Allow Admin and Fleet Manager
requireRole(['admin', 'fleet manager', 'fleet_manager']);

$page = 'live-tracking';
$page_title = 'Live Tracking & CCTV - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

// Fetch all active buses that have at least one tracking/CCTV link
$stmt = $pdo->query("SELECT id, bus_number, make, model, gps_url, cctv_url_1, cctv_url_2 FROM buses WHERE status = 'active' ORDER BY bus_number ASC");
$trackable_buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<style>
.tracking-container {
    display: flex;
    gap: 24px;
    height: calc(100vh - 140px);
}

.bus-selector-panel {
    width: 300px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

body.dark-theme .bus-selector-panel {
    background: #1e293b;
    border-color: #334155;
}

.selector-header {
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 600;
}

body.dark-theme .selector-header {
    background: #0f172a;
    border-color: #334155;
}

.bus-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.bus-item {
    padding: 14px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 8px;
    border: 1px solid transparent;
}

.bus-item:hover {
    background: #f1f5f9;
}

body.dark-theme .bus-item:hover {
    background: #334155;
}

.bus-item.active {
    background: #eff6ff;
    border-color: #bfdbfe;
}

body.dark-theme .bus-item.active {
    background: #1e3a8a;
    border-color: #1e40af;
}

.bus-number {
    font-weight: 600;
    font-size: 15px;
    margin-bottom: 4px;
}

.bus-meta {
    font-size: 12px;
    color: #64748b;
    display: flex;
    gap: 12px;
}

.viewer-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.cctv-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    height: 35%;
}

.cctv-frame-container {
    background: #000;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    border: 1px solid #334155;
}

.map-frame-container {
    flex: 1;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    position: relative;
}

body.dark-theme .map-frame-container {
    background: #1e293b;
    border-color: #334155;
}

.feed-label {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 6px;
}

.empty-state {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #64748b;
    background: #f8fafc;
}

body.dark-theme .empty-state {
    background: #0f172a;
    color: #94a3b8;
}

.empty-state.black-bg {
    background: #111;
    color: #555;
}

iframe, .video-stream {
    width: 100%;
    height: 100%;
    border: none;
    background: transparent;
    object-fit: contain;
}
</style>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">Live Tracking & CCTV Monitoring</h1>
        <p class="page-subtitle">Select a bus to view real-time GPS location and camera feeds</p>
    </div>
</header>

<section class="content-section" style="padding-bottom: 0;">
    <div class="tracking-container">
        
        <!-- Sidebar Selector -->
        <div class="bus-selector-panel">
            <div class="selector-header">Active Fleet</div>
            <div class="bus-list">
                <?php if (count($trackable_buses) > 0): ?>
                    <?php foreach ($trackable_buses as $bus): ?>
                        <div class="bus-item" 
                             onclick="loadFeeds('<?php echo htmlspecialchars($bus['gps_url'] ?? ''); ?>', '<?php echo htmlspecialchars($bus['cctv_url_1'] ?? ''); ?>', '<?php echo htmlspecialchars($bus['cctv_url_2'] ?? ''); ?>', this)">
                            <div class="bus-number"><?php echo htmlspecialchars($bus['bus_number']); ?></div>
                            <div class="bus-meta">
                                <span><i data-lucide="bus" style="width:12px; height:12px; display:inline-block; vertical-align:middle;"></i> <?php echo htmlspecialchars($bus['make']); ?></span>
                                <?php if(!empty($bus['gps_url'])): ?><span title="GPS Enabled" style="color: #10b981;">📍</span><?php endif; ?>
                                <?php if(!empty($bus['cctv_url_1']) || !empty($bus['cctv_url_2'])): ?><span title="Camera Enabled" style="color: #3b82f6;">📹</span><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #64748b; font-size: 14px;">
                        No active buses found. Please add a bus first.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Viewer Panel -->
        <div class="viewer-panel">
            
            <!-- CCTV Grid -->
            <div class="cctv-grid">
                <div class="cctv-frame-container">
                    <div class="feed-label"><i data-lucide="video" style="width:14px; height:14px;"></i> Cam 1 - Front/Dash</div>
                    <div id="cctv1-container" class="empty-state black-bg">
                        <i data-lucide="video-off" style="width:32px; height:32px; margin-bottom: 8px;"></i>
                        <span>No Stream Available</span>
                    </div>
                </div>
                <div class="cctv-frame-container">
                    <div class="feed-label"><i data-lucide="video" style="width:14px; height:14px;"></i> Cam 2 - Cabin/Rear</div>
                    <div id="cctv2-container" class="empty-state black-bg">
                        <i data-lucide="video-off" style="width:32px; height:32px; margin-bottom: 8px;"></i>
                        <span>No Stream Available</span>
                    </div>
                </div>
            </div>

            <!-- GPS Map -->
            <div class="map-frame-container">
                <div class="feed-label" style="background: rgba(255, 255, 255, 0.9); color: #0f172a; border: 1px solid #e2e8f0;"><i data-lucide="map-pinned" style="width:14px; height:14px;"></i> Live GPS Location</div>
                <div id="gps-container" class="empty-state">
                    <i data-lucide="map" style="width:48px; height:48px; margin-bottom: 12px; opacity: 0.5;"></i>
                    <span style="font-size: 16px; font-weight: 500;">Select a bus from the list to view its real-time location</span>
                    <span style="font-size: 13px; margin-top: 6px;">Map tracking link must be configured in Bus Management</span>
                </div>
            </div>
            
        </div>
    </div>
</section>

<script>
function loadFeeds(gpsUrl, cctv1Url, cctv2Url, element) {
    // UI state active class
    document.querySelectorAll('.bus-item').forEach(el => el.classList.remove('active'));
    if(element) element.classList.add('active');

    // Loader Function for CCTV (supports both Mjpeg img and iframes)
    const loadCctv = (url, containerId) => {
        const container = document.getElementById(containerId);
        if (!url || url.trim() === '') {
            container.innerHTML = '<i data-lucide="video-off" style="width:32px; height:32px; margin-bottom: 8px;"></i><span>No Feed Configured</span>';
            container.classList.add('empty-state', 'black-bg');
        } else {
            container.classList.remove('empty-state', 'black-bg');
            // If it's a direct MJPEG stream layout as an img, otherwise iframe it
            if(url.endsWith('.mjpg') || url.endsWith('.cgi') || url.includes('stream')) {
                container.innerHTML = `<img src="${url}" class="video-stream" alt="CCTV Feed" onerror="this.outerHTML='<div class=\\'empty-state black-bg\\'><span>Error connecting to feed</span></div>'">`;
            } else {
                container.innerHTML = `<iframe src="${url}" allowfullscreen></iframe>`;
            }
        }
    };

    // Load CCTV 1
    loadCctv(cctv1Url, 'cctv1-container');
    
    // Load CCTV 2
    loadCctv(cctv2Url, 'cctv2-container');

    // Load GPS Map
    const gpsContainer = document.getElementById('gps-container');
    if (!gpsUrl || gpsUrl.trim() === '') {
        gpsContainer.innerHTML = '<i data-lucide="map" style="width:48px; height:48px; margin-bottom: 12px; opacity: 0.5;"></i><span style="font-size: 16px; font-weight: 500;">No GPS Tracker Configured</span>';
        gpsContainer.classList.add('empty-state');
    } else {
        gpsContainer.classList.remove('empty-state');
        gpsContainer.innerHTML = `<iframe src="${gpsUrl}" allowfullscreen></iframe>`;
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
