<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole(['admin', 'fleet_manager', 'fleet manager']);

$page = 'fleet-overview';
$page_title = 'Fleet Overview - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

// Fetch all buses with their recent routing and maintenance
$stmt = $pdo->query("
    SELECT b.*, 
           COALESCE(curr_r.route_name, r.route_name) AS route_name,
           COALESCE(curr_ud.username, ud.username) AS driver_name,
           COALESCE(curr_uc.username, uc.username) AS conductor_name,
           curr_trip.status AS curr_trip_status,
           (SELECT MIN(service_date) FROM services WHERE bus_id = b.id AND status = 'scheduled') as next_maintenance,
           (SELECT COALESCE(SUM(r2.distance), 0) FROM trips t2 JOIN routes r2 ON t2.route_id = r2.id WHERE t2.bus_id = b.id AND t2.status = 'completed') as total_distance
    FROM buses b
    LEFT JOIN routes r ON b.route_id = r.id
    LEFT JOIN users ud ON b.driver_id = ud.id
    LEFT JOIN users uc ON b.conductor_id = uc.id
    LEFT JOIN (
        SELECT bus_id, MIN(start_time) as min_start_time
        FROM trips
        WHERE status IN ('ongoing', 'scheduled')
        GROUP BY bus_id
    ) latest_trip ON latest_trip.bus_id = b.id
    LEFT JOIN trips curr_trip ON curr_trip.bus_id = b.id 
                             AND curr_trip.start_time = latest_trip.min_start_time 
                             AND curr_trip.status IN ('ongoing', 'scheduled')
    LEFT JOIN routes curr_r ON curr_trip.route_id = curr_r.id
    LEFT JOIN users curr_ud ON curr_trip.driver_id = curr_ud.id
    LEFT JOIN users curr_uc ON curr_trip.conductor_id = curr_uc.id
    WHERE b.status != 'deleted'
    ORDER BY b.id DESC
");
$fleet_buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<style>
/* Fleet overview AJAX styles */
.refresh-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 7px; border: 1px solid #cbd5e1;
    background: #fff; color: #475569; font-size: 13px; font-weight: 500;
    cursor: pointer; transition: background 0.15s, transform 0.2s;
    margin-left: 10px;
}
.refresh-btn:hover { background: #f1f5f9; }
.refresh-btn.spinning i, .refresh-btn.spinning svg { animation: spin360 0.7s linear; }
@keyframes spin360 { to { transform: rotate(360deg); } }
.last-updated {
    font-size: 12px; color: #94a3b8; margin-left: 10px; vertical-align: middle;
}
.status-badge { transition: background 0.4s, color 0.4s; }

/* Toast */
#fleet-toast {
    position: fixed; bottom: 28px; right: 28px; z-index: 9999;
    padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 500;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15); display: none; opacity: 0;
    transition: opacity 0.3s;
}
#fleet-toast.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
#fleet-toast.info    { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
</style>

            <!-- Header -->
            <header class="top-header">
                <div class="header-left">
                    <h1 class="page-title">Fleet Overview</h1>
                    <p class="page-subtitle">Monitor and manage all your vehicles</p>
                </div>
                <div class="header-right">
                    <div class="date-display">📅</div>
                </div>
            </header>

            <!-- Fleet Overview Section -->
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Vehicles</h2>
                    <div class="section-actions">
                        <input type="text" class="search-input" placeholder="Search buses...">
                        <button class="refresh-btn" id="fleet-refresh-btn" onclick="refreshFleetStatus()" title="Refresh bus statuses">
                            <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Refresh
                        </button>
                        <span class="last-updated" id="fleet-last-updated"></span>
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Bus Number</th>
                                <th>Status</th>
                                <th>Driver / Conductor</th>
                                <th>Current Route</th>
                                <th>Next Maintenance</th>
                                <th>Total Distance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($fleet_buses) > 0): ?>
                                <?php foreach ($fleet_buses as $bus): ?>
                                    <tr data-bus-id="<?php echo $bus['id']; ?>">
                                        <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $disp_status = ucfirst(htmlspecialchars($bus['status']));
                                            $badge = $bus['status'] === 'active' ? 'active' : ($bus['status'] === 'maintenance' ? 'maintenance' : 'idle');
                                            
                                            // If not in maintenance and has an active trip assignment
                                            if ($bus['status'] !== 'maintenance' && !empty($bus['curr_trip_status'])) {
                                                $disp_status = ucfirst(htmlspecialchars($bus['curr_trip_status']));
                                                $badge = 'ongoing';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $badge; ?>"><?php echo $disp_status; ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($bus['driver_name'])): ?><div style="font-size: 13px; color: #10b981;">👨‍✈️ <?php echo htmlspecialchars($bus['driver_name']); ?></div><?php endif; ?>
                                            <?php if (!empty($bus['conductor_name'])): ?><div style="font-size: 13px; color: #f59e0b; margin-top:2px;">🎫 <?php echo htmlspecialchars($bus['conductor_name']); ?></div><?php endif; ?>
                                            <?php if (empty($bus['driver_name']) && empty($bus['conductor_name'])) echo '<span style="color:#94a3b8;">Unassigned</span>'; ?>
                                        </td>
                                        <td><?php echo !empty($bus['route_name']) ? htmlspecialchars($bus['route_name']) : '<span style="color: #94a3b8;">Unassigned</span>'; ?></td>
                                        <td><?php echo !empty($bus['next_maintenance']) ? date('d M Y', strtotime($bus['next_maintenance'])) : '<span style="color:#94a3b8;">Not scheduled</span>'; ?></td>
                                        <td><?php echo number_format((float)$bus['total_distance'], 1) . ' km'; ?></td>
                                    </tr>
                                <?php
    endforeach; ?>
                            <?php
else: ?>
                                <tr><td colspan="6" style="text-align: center;">No vehicles found in fleet.</td></tr>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Toast -->
<div id="fleet-toast"></div>

<script>
// ── Fleet Overview: AJAX Live Status Refresh ──────────────────────────────
var FLEET_AUTO_INTERVAL = 30000; // 30 seconds
var _fleetTimer = null;

// Badge class map
var BADGE_CLASSES = ['active', 'idle', 'ongoing', 'maintenance'];

function refreshFleetStatus() {
    var $btn = $('#fleet-refresh-btn');
    $btn.addClass('spinning').prop('disabled', true);

    $.ajax({
        url: 'ajax_handlers.php',
        method: 'GET',
        data: { action: 'ajax_fleet_status' },
        dataType: 'json',
        success: function(res) {
            if (res.success && res.data) {
                var changed = 0;
                res.data.forEach(function(bus) {
                    var $row = $('tr[data-bus-id="' + bus.id + '"]');
                    if (!$row.length) return;

                    var $badge = $row.find('.status-badge');
                    var curBadge  = BADGE_CLASSES.find(function(c) { return $badge.hasClass(c); });
                    var curText   = $badge.text().trim();

                    // Only animate if something actually changed
                    if (bus.badge !== curBadge || bus.disp_status !== curText) {
                        changed++;
                        $badge.fadeOut(200, function() {
                            $badge.removeClass(BADGE_CLASSES.join(' '))
                                  .addClass(bus.badge)
                                  .text(bus.disp_status)
                                  .fadeIn(300);
                        });
                        // Briefly highlight the row
                        $row.css({ background: '#eff6ff', transition: 'background 0.4s' });
                        setTimeout(function() {
                            $row.css('background', '');
                        }, 1000);
                    }
                });

                // Update last-refreshed timestamp
                var now = new Date();
                var hh  = String(now.getHours()).padStart(2,'0');
                var mm  = String(now.getMinutes()).padStart(2,'0');
                var ss  = String(now.getSeconds()).padStart(2,'0');
                $('#fleet-last-updated').text('Updated ' + hh + ':' + mm + ':' + ss);

                if (changed > 0) {
                    showFleetToast(changed + ' status' + (changed > 1 ? 'es' : '') + ' updated.', 'success');
                }
            }
        },
        error: function() {
            showFleetToast('Could not refresh status. Retrying…', 'info');
        },
        complete: function() {
            setTimeout(function() {
                $btn.removeClass('spinning').prop('disabled', false);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 600);
        }
    });
}

function showFleetToast(msg, type) {
    var $t = $('#fleet-toast');
    $t.removeClass('success info').addClass(type).text(msg);
    $t.stop(true).css({ display: 'block', opacity: 0 })
      .animate({ opacity: 1 }, 250);
    clearTimeout(window._fleetToastTimer);
    window._fleetToastTimer = setTimeout(function() {
        $t.animate({ opacity: 0 }, 400, function() { $t.hide(); });
    }, 3000);
}

// Auto-refresh every 30 seconds
$(function() {
    _fleetTimer = setInterval(refreshFleetStatus, FLEET_AUTO_INTERVAL);
     // Show initial last-updated time on page load
    var now = new Date();
    var hh  = String(now.getHours()).padStart(2,'0');
    var mm  = String(now.getMinutes()).padStart(2,'0');
    var ss  = String(now.getSeconds()).padStart(2,'0');
    $('#fleet-last-updated').text('Loaded ' + hh + ':' + mm + ':' + ss);
});
</script>
