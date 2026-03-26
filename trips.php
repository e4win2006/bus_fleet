<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';

requireRole(['admin', 'fleet_manager', 'fleet manager']);

$page       = 'trips';
$page_title = 'Trips - FleetVision';
$page_css   = 'dashboard.css';
$show_sidebar = true;

$msg   = '';
$error = '';



// ── Add Trip ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_trip') {
    $bus_id     = empty($_POST['bus_id'])    ? null : (int)$_POST['bus_id'];
    $start_time = trim($_POST['start_time'] ?? '');
    $status     = $_POST['status'] ?? 'scheduled';
    $notes      = trim($_POST['notes'] ?? '');
    
    // Auto-fetch the route, driver and conductor permanently assigned to this bus
    $route_id = null;
    $driver_id = null;
    $conductor_id = null;
    if ($bus_id) {
        $stmt = $pdo->prepare("SELECT route_id, driver_id, conductor_id FROM buses WHERE id = ?");
        $stmt->execute([$bus_id]);
        $b = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($b) {
            $route_id = $b['route_id'];
            $driver_id = $b['driver_id'];
            $conductor_id = $b['conductor_id'];
        }
    }

    if (empty($start_time)) {
        $error = 'Start time is required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO trips (bus_id, route_id, driver_id, conductor_id, start_time, end_time, status, notes) VALUES (?, ?, ?, ?, ?, NULL, ?, ?)");
        try {
            $stmt->execute([$bus_id, $route_id, $driver_id, $conductor_id, $start_time, $status, $notes]);
            $msg = 'Trip added successfully.';
        } catch (PDOException $e) {
            $error = 'Error adding trip: ' . $e->getMessage();
        }
    }
}

// ── Edit Trip ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_trip') {
    $trip_id    = (int)$_POST['trip_id'];
    $bus_id     = empty($_POST['bus_id'])    ? null : (int)$_POST['bus_id'];
    $start_time = trim($_POST['start_time'] ?? '');
    $status     = $_POST['status'] ?? 'scheduled';
    $notes      = trim($_POST['notes'] ?? '');

    // Auto-fetch the route, driver and conductor permanently assigned to this bus
    $route_id = null;
    $driver_id = null;
    $conductor_id = null;
    if ($bus_id) {
        $stmt = $pdo->prepare("SELECT route_id, driver_id, conductor_id FROM buses WHERE id = ?");
        $stmt->execute([$bus_id]);
        $b = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($b) {
            $route_id = $b['route_id'];
            $driver_id = $b['driver_id'];
            $conductor_id = $b['conductor_id'];
        }
    }

    if (empty($start_time)) {
        $error = 'Start time is required.';
    } else {
        $stmt = $pdo->prepare("UPDATE trips SET bus_id=?, route_id=?, driver_id=?, conductor_id=?, start_time=?, status=?, notes=? WHERE id=?");
        try {
            $stmt->execute([$bus_id, $route_id, $driver_id, $conductor_id, $start_time, $status, $notes, $trip_id]);
            $msg = 'Trip updated successfully.';
        } catch (PDOException $e) {
            $error = 'Error updating trip: ' . $e->getMessage();
        }
    }
}

// ── Update Status ─────────────────────────────────────────────────────────
if (isset($_GET['complete_id'])) {
    $id = (int)$_GET['complete_id'];
    
    $pdo->prepare("UPDATE trips SET status = 'completed', end_time = datetime('now','localtime') WHERE id = ?")->execute([$id]);
    $msg = 'Trip marked as completed and bus released.';
}

// ── Delete Trip ───────────────────────────────────────────────────────────
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    
    // Clear the assignment from the bus when trip is deleted
    $stmt = $pdo->prepare("SELECT bus_id FROM trips WHERE id = ?");
    $stmt->execute([$id]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($t && $t['bus_id']) {
        $pdo->prepare("UPDATE buses SET route_id=NULL, driver_id=NULL, conductor_id=NULL WHERE id=?")->execute([$t['bus_id']]);
    }

    $pdo->prepare("DELETE FROM trips WHERE id = ?")->execute([$id]);
    $msg = 'Trip deleted.';
}

// ── Fetch data ────────────────────────────────────────────────────────────
$trips = $pdo->query("
    SELECT t.*,
           b.bus_number,
           r.route_name, r.distance,
           u.username AS driver_name,
           c.username AS conductor_name
    FROM trips t
    LEFT JOIN buses  b ON t.bus_id   = b.id
    LEFT JOIN routes r ON t.route_id = r.id
    LEFT JOIN users  u ON t.driver_id = u.id
    LEFT JOIN users  c ON t.conductor_id = c.id
    ORDER BY t.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$buses   = $pdo->query("SELECT id, bus_number FROM buses WHERE status != 'deleted' ORDER BY bus_number")->fetchAll(PDO::FETCH_ASSOC);
$routes  = $pdo->query("SELECT id, route_name FROM routes WHERE status = 'active' ORDER BY route_name")->fetchAll(PDO::FETCH_ASSOC);
$drivers = $pdo->query("SELECT id, username FROM users WHERE role = 'driver' AND status = 'approved' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$conductors = $pdo->query("SELECT id, username FROM users WHERE role = 'conductor' AND status = 'approved' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<style>
.users-container  { display: flex; gap: 24px; align-items: flex-start; }
.users-list       { flex: 2; }
.user-form-card   { flex: 1; background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; position: sticky; top: 24px; }
.form-group       { margin-bottom: 16px; }
.form-label       { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 500; color: #475569; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; font-family: inherit; }
.form-textarea    { resize: vertical; min-height: 60px; }
.btn-submit       { width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-submit:hover { background: #2563eb; }
.alert            { padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
.alert-success    { background: #dcfce3; color: #15803d; border: 1px solid #bbf7d0; }
.alert-error      { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.action-link      { font-size: 13px; font-weight: 500; text-decoration: none; margin-right: 8px; }
.action-link.complete { color: #10b981; }
.action-link.delete   { color: #ef4444; }
.hover-row { transition: background-color 0.2s ease; }
.hover-row:hover { background-color: #f8fafc; }
@media (max-width: 992px) { .users-container { flex-direction: column-reverse; } .user-form-card { width: 100%; position: static; } }

/* AJAX button styles */
.btn-ajax-action {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 13px; font-weight: 500; cursor: pointer;
    border: none; background: none; padding: 3px 6px;
    border-radius: 4px; transition: background 0.15s, opacity 0.15s;
    margin-right: 4px;
}
.btn-ajax-action.complete { color: #10b981; }
.btn-ajax-action.complete:hover { background: #d1fae5; }
.btn-ajax-action.delete   { color: #ef4444; }
.btn-ajax-action.delete:hover   { background: #fee2e2; }
.btn-ajax-action:disabled { opacity: 0.45; cursor: not-allowed; }

/* Toast notification */
#trip-toast {
    position: fixed; bottom: 28px; right: 28px; z-index: 9999;
    padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 500;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    display: none; opacity: 0;
    transition: opacity 0.3s;
}
#trip-toast.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
#trip-toast.error   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
</style>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">Trips & Operations</h1>
        <p class="page-subtitle">Track and manage all trip activities</p>
    </div>
    <div class="header-right">
        <div class="date-display"></div>
    </div>
</header>

<section class="content-section">
    <?php if ($msg):   echo "<div class='alert alert-success'>$msg</div>"; endif; ?>
    <?php if ($error): echo "<div class='alert alert-error'>$error</div>"; endif; ?>

    <div class="users-container">
        <!-- Trips Table -->
        <div class="users-list table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bus</th>
                        <th>Route</th>
                        <th>Driver</th>
                        <th>Conductor</th>
                        <th>Start Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($trips) > 0): ?>
                        <?php foreach ($trips as $t): ?>
                            <?php
                            $jsdata = htmlspecialchars(json_encode([
                                'id' => $t['id'], 'bus_id'=> $t['bus_id'], 'driver_id'=>$t['driver_id'], 'conductor_id'=>$t['conductor_id'], 'start_time'=>$t['start_time'], 'status'=>$t['status'], 'notes'=>$t['notes']
                            ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr onclick="editTrip(this)" data-trip="<?php echo $jsdata; ?>" class="hover-row" style="cursor: pointer;" title="Click to edit">
                                <td><strong>#<?php echo $t['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($t['bus_number'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($t['route_name'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($t['driver_name'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($t['conductor_name'] ?? '—'); ?></td>
                                <td><?php echo $t['start_time'] ? date('d M, h:i A', strtotime($t['start_time'])) : '—'; ?></td>
                                <td>
                                    <?php
                                    $sc = match($t['status']) {
                                        'completed' => 'active',
                                        'ongoing'   => 'ongoing',
                                        'cancelled' => 'maintenance',
                                        default     => 'idle'
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $sc; ?>"><?php echo ucfirst($t['status']); ?></span>
                                </td>
                                <td onclick="event.stopPropagation()">
                                    <?php if ($t['status'] === 'ongoing' || $t['status'] === 'scheduled'): ?>
                                        <button
                                            class="btn-ajax-action complete"
                                            data-id="<?php echo $t['id']; ?>"
                                            onclick="ajaxCompleteTrip(this)"
                                            title="Mark as completed"
                                        >✓ Complete</button>
                                    <?php endif; ?>
                                    <button
                                        class="btn-ajax-action delete"
                                        data-id="<?php echo $t['id']; ?>"
                                        onclick="ajaxDeleteTrip(this)"
                                        title="Delete trip"
                                    >🗑 Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; color:#64748b;">No trips recorded yet. Add one →</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Trip Form -->
        <div class="user-form-card">
            <h3 id="form-title" style="margin-bottom: 20px;">Add New Trip</h3>
            <form method="POST" action="trips.php" id="trip-form">
                <input type="hidden" name="action" id="form-action" value="add_trip">
                <input type="hidden" name="trip_id" id="form-trip-id" value="">

                <div class="form-group">
                    <label class="form-label">Bus</label>
                    <select name="bus_id" id="form-bus_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($buses as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['bus_number']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Start Date & Time *</label>
                    <input type="datetime-local" name="start_time" id="form-start_time" class="form-input" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="form-status" class="form-select">
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="form-notes" class="form-textarea" placeholder="Optional notes..."></textarea>
                </div>

                <button type="submit" class="btn-submit" id="btn-submit">Add Trip</button>
                <button type="button" class="btn-cancel" id="btn-cancel" style="margin-top: 10px; display:none; background:#94a3b8; color:white; width:100%; padding:10px; border:none; border-radius:6px; font-weight:600; cursor:pointer;" onclick="cancelEdit()">Cancel Edit</button>
            </form>
            <script>
            function editTrip(el) {
                var data = JSON.parse(el.getAttribute('data-trip'));
                document.getElementById('form-action').value = 'edit_trip';
                document.getElementById('form-trip-id').value = data.id;
                document.getElementById('form-bus_id').value = data.bus_id || '';
                
                // Format datetime strings correctly if they come from DB "YYYY-MM-DD HH:MM:SS" -> "YYYY-MM-DDTHH:MM"
                var st = data.start_time ? data.start_time.replace(' ', 'T') : '';
                // Limit to YYYY-MM-DDTHH:MM format length
                if(st.length > 16) st = st.substring(0,16);

                document.getElementById('form-start_time').value = st;
                document.getElementById('form-status').value = data.status || 'scheduled';
                document.getElementById('form-notes').value = data.notes || '';
                
                document.getElementById('form-title').innerText = 'Edit Trip';
                document.getElementById('btn-submit').innerText = 'Save Changes';
                document.getElementById('btn-cancel').style.display = 'block';
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
            function cancelEdit() {
                document.getElementById('form-action').value = 'add_trip';
                document.getElementById('form-trip-id').value = '';
                document.getElementById('form-bus_id').value = '';
                
                // Reset to current time
                var now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                var nowStr = now.toISOString().slice(0,16);
                
                document.getElementById('form-start_time').value = nowStr;
                document.getElementById('form-status').value = 'scheduled';
                document.getElementById('form-notes').value = '';
                
                document.getElementById('form-title').innerText = 'Add New Trip';
                document.getElementById('btn-submit').innerText = 'Add Trip';
                document.getElementById('btn-cancel').style.display = 'none';
            }
            </script>
        </div>
    </div>
</section>

<!-- Toast notification container -->
<div id="trip-toast"></div>

<script>
// ── AJAX: Mark trip as completed ─────────────────────────────────────────
function ajaxCompleteTrip(btn) {
    if (!confirm('Mark this trip as completed?')) return;
    var tripId = btn.getAttribute('data-id');
    var $row   = $(btn).closest('tr');

    btn.disabled = true;
    btn.textContent = '⏳ Saving…';

    $.ajax({
        url: 'ajax_handlers.php',
        method: 'POST',
        data: { action: 'ajax_complete_trip', trip_id: tripId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                // Update status badge in-place
                var $badge = $row.find('.status-badge');
                $badge.removeClass('idle ongoing active maintenance')
                      .addClass('active')
                      .text('Completed');

                // Remove the Complete button with animation
                $(btn).fadeOut(300, function() { $(this).remove(); });

                // Flash the row green
                $row.css('background', '#d1fae5');
                setTimeout(function() {
                    $row.css('transition', 'background 0.8s');
                    $row.css('background', '');
                    setTimeout(function() { $row.css('transition', ''); }, 900);
                }, 600);

                showTripToast(res.message, 'success');
            } else {
                btn.disabled = false;
                btn.textContent = '✓ Complete';
                showTripToast(res.message || 'Failed to update.', 'error');
            }
        },
        error: function() {
            btn.disabled = false;
            btn.textContent = '✓ Complete';
            showTripToast('Network error. Please try again.', 'error');
        }
    });
}

// ── AJAX: Delete trip ────────────────────────────────────────────────────
function ajaxDeleteTrip(btn) {
    if (!confirm('Delete this trip? This cannot be undone.')) return;
    var tripId = btn.getAttribute('data-id');
    var $row   = $(btn).closest('tr');

    btn.disabled = true;
    btn.textContent = '⏳ Deleting…';

    $.ajax({
        url: 'ajax_handlers.php',
        method: 'POST',
        data: { action: 'ajax_delete_trip', trip_id: tripId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                // Animate row removal
                $row.css({ background: '#fee2e2', transition: 'background 0.3s' });
                setTimeout(function() {
                    $row.fadeOut(400, function() {
                        $row.remove();
                        // Show "No trips" if table is now empty
                        var $tbody = $('.data-table tbody');
                        if ($tbody.find('tr').length === 0) {
                            $tbody.append('<tr><td colspan="8" style="text-align:center;color:#64748b;">No trips recorded yet. Add one →</td></tr>');
                        }
                    });
                }, 300);
                showTripToast(res.message, 'success');
            } else {
                btn.disabled = false;
                btn.textContent = '🗑 Delete';
                showTripToast(res.message || 'Failed to delete.', 'error');
            }
        },
        error: function() {
            btn.disabled = false;
            btn.textContent = '🗑 Delete';
            showTripToast('Network error. Please try again.', 'error');
        }
    });
}

// ── Toast helper ──────────────────────────────────────────────────────────
function showTripToast(msg, type) {
    var $t = $('#trip-toast');
    $t.removeClass('success error').addClass(type).text(msg);
    $t.stop(true).css({ display: 'block', opacity: 0 })
      .animate({ opacity: 1 }, 250);
    clearTimeout(window._tripToastTimer);
    window._tripToastTimer = setTimeout(function() {
        $t.animate({ opacity: 0 }, 400, function() { $t.hide(); });
    }, 3500);
}

// ── Auto-start poller: scheduled → ongoing when start_time reached ────────
function checkAutoStartTrips() {
    $.ajax({
        url: 'ajax_handlers.php',
        method: 'POST',
        data: { action: 'ajax_auto_start_trips' },
        dataType: 'json',
        success: function(res) {
            if (res.success && res.started_ids && res.started_ids.length > 0) {
                res.started_ids.forEach(function(id) {
                    // Find the row that contains an action button with this trip id
                    var $row = $('tr').filter(function() {
                        return $(this).find('[data-id="' + id + '"]').length > 0;
                    });
                    if (!$row.length) return;

                    // Update the status badge to Ongoing
                    var $badge = $row.find('.status-badge');
                    $badge.removeClass('idle active ongoing maintenance')
                          .addClass('ongoing')
                          .text('Ongoing');

                    // Add a Complete button if it isn't already there
                    var $actions = $row.find('td').last();
                    if (!$actions.find('.btn-ajax-action.complete').length) {
                        var $btn = $('<button>', {
                            'class': 'btn-ajax-action complete',
                            'data-id': id,
                            'title': 'Mark as completed',
                            'text': '✓ Complete'
                        }).on('click', function() { ajaxCompleteTrip(this); });
                        $actions.prepend($btn);
                    }

                    // Flash the row blue to signal the auto-start
                    $row.css('background', '#dbeafe');
                    setTimeout(function() {
                        $row.css('transition', 'background 1s');
                        $row.css('background', '');
                        setTimeout(function() { $row.css('transition', ''); }, 1100);
                    }, 700);
                });

                showTripToast(res.started_ids.length + ' trip(s) automatically started.', 'success');
            }
        }
        // Silently ignore network errors — poller will retry on the next interval
    });
}

// Run once on page load, then every 30 seconds
$(document).ready(function() {
    checkAutoStartTrips();
    setInterval(checkAutoStartTrips, 30000);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
