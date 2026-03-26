<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';

header('Content-Type: application/json');

// Only allow logged-in users with appropriate roles
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Complete Trip (AJAX) ────────────────────────────────────────────────────
if ($action === 'ajax_complete_trip') {
    requireRole(['admin', 'fleet_manager', 'fleet manager']);
    $id = (int)($_POST['trip_id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid trip ID']); exit; }

    try {
        $pdo->prepare("UPDATE trips SET status = 'completed', end_time = datetime('now','localtime') WHERE id = ?")
            ->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Trip marked as completed.', 'new_status' => 'completed']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── Delete Trip (AJAX) ──────────────────────────────────────────────────────
if ($action === 'ajax_delete_trip') {
    requireRole(['admin', 'fleet_manager', 'fleet manager']);
    $id = (int)($_POST['trip_id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid trip ID']); exit; }

    try {
        // Clear bus assignment
        $stmt = $pdo->prepare("SELECT bus_id FROM trips WHERE id = ?");
        $stmt->execute([$id]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($t && $t['bus_id']) {
            $pdo->prepare("UPDATE buses SET route_id=NULL, driver_id=NULL, conductor_id=NULL WHERE id=?")
                ->execute([$t['bus_id']]);
        }
        $pdo->prepare("DELETE FROM trips WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Trip deleted.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── Fleet Overview: Live Status Data (AJAX GET) ─────────────────────────────
if ($action === 'ajax_fleet_status') {
    requireRole(['admin', 'fleet_manager', 'fleet manager']);

    $stmt = $pdo->query("
        SELECT b.id, b.bus_number, b.status,
               curr_trip.status AS curr_trip_status
        FROM buses b
        LEFT JOIN (
            SELECT bus_id, MIN(start_time) as min_start_time
            FROM trips
            WHERE status IN ('ongoing', 'scheduled')
            GROUP BY bus_id
        ) latest_trip ON latest_trip.bus_id = b.id
        LEFT JOIN trips curr_trip ON curr_trip.bus_id = b.id
                                 AND curr_trip.start_time = latest_trip.min_start_time
                                 AND curr_trip.status IN ('ongoing', 'scheduled')
        WHERE b.status != 'deleted'
        ORDER BY b.id DESC
    ");
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($buses as $bus) {
        $disp_status = ucfirst($bus['status']);
        $badge       = $bus['status'] === 'active' ? 'active' : ($bus['status'] === 'maintenance' ? 'maintenance' : 'idle');

        if ($bus['status'] !== 'maintenance' && !empty($bus['curr_trip_status'])) {
            $disp_status = ucfirst($bus['curr_trip_status']);
            $badge       = 'ongoing';
        }

        $result[] = [
            'id'          => $bus['id'],
            'disp_status' => $disp_status,
            'badge'       => $badge,
        ];
    }

    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

// ── Dashboard: Live KPI + Feed Refresh (AJAX GET) ──────────────────────────
if ($action === 'ajax_dashboard_stats') {
    requireRole(['admin', 'fleet_manager', 'fleet manager', 'driver', 'conductor']);

    $today = date('Y-m-d');
    $role  = strtolower($_SESSION['role'] ?? '');

    if ($role === 'admin' || $role === 'fleet_manager' || $role === 'fleet manager') {

        // KPI numbers
        $total_buses   = (int)$pdo->query("SELECT COUNT(*) FROM buses WHERE status != 'deleted'")->fetchColumn();
        $active_buses  = (int)$pdo->query("SELECT COUNT(*) FROM buses WHERE status = 'active'")->fetchColumn();
        $trips_today   = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(start_time) = '$today'")->fetchColumn();
        $ongoing_trips = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'ongoing'")->fetchColumn();
        $completed_trips = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(start_time) = '$today' AND status = 'completed'")->fetchColumn();
        $scheduled_trips_today = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(start_time) = '$today' AND status = 'scheduled'")->fetchColumn();
        $maint_due     = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE status IN ('scheduled','ongoing')")->fetchColumn();
        $maint_overdue = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE status = 'scheduled' AND service_date < '$today'")->fetchColumn();
        $total_drivers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'driver' AND status != 'deleted'")->fetchColumn();
        $available_drivers = (int)$pdo->query("SELECT COUNT(*) FROM users u LEFT JOIN buses b ON b.driver_id = u.id WHERE u.role = 'driver' AND u.status = 'approved' AND b.id IS NULL")->fetchColumn();

        // Buses on journey
        $boj_stmt = $pdo->query("
            SELECT b.bus_number, b.make, b.model, r.route_name, u.username as driver_name
            FROM trips t
            JOIN buses b ON t.bus_id = b.id
            JOIN routes r ON t.route_id = r.id
            LEFT JOIN users u ON t.driver_id = u.id
            WHERE t.status = 'ongoing'
        ");
        $buses_on_journey = $boj_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent trips feed (last 5)
        $lt_stmt = $pdo->query("
            SELECT t.id, t.status, t.start_time, b.bus_number, r.route_name, u.username as driver_name
            FROM trips t
            LEFT JOIN buses b ON t.bus_id = b.id
            LEFT JOIN routes r ON t.route_id = r.id
            LEFT JOIN users u ON t.driver_id = u.id
            ORDER BY t.start_time DESC LIMIT 5
        ");
        $latest_trips = $lt_stmt->fetchAll(PDO::FETCH_ASSOC);
        // Format dates for JS
        foreach ($latest_trips as &$lt) {
            $lt['start_time_fmt'] = $lt['start_time'] ? date('M d, g:i A', strtotime($lt['start_time'])) : '—';
        }
        unset($lt);

        $pct_comp = $trips_today > 0 ? round(($completed_trips / $trips_today) * 100) : 0;
        $pct_ong  = $trips_today > 0 ? round(($ongoing_trips  / $trips_today) * 100) : 0;
        $pct_sch  = $trips_today > 0 ? round(($scheduled_trips_today / $trips_today) * 100) : 0;

        echo json_encode([
            'success' => true,
            'role'    => 'admin',
            'kpi' => [
                'active_buses'      => $active_buses,
                'total_buses'       => $total_buses,
                'ongoing_trips'     => $ongoing_trips,
                'trips_today'       => $trips_today,
                'available_drivers' => $available_drivers,
                'total_drivers'     => $total_drivers,
                'maint_overdue'     => $maint_overdue,
                'maint_due'         => $maint_due,
            ],
            'progress' => [
                'completed'  => $completed_trips,
                'ongoing'    => $ongoing_trips,
                'scheduled'  => $scheduled_trips_today,
                'pct_comp'   => $pct_comp,
                'pct_ong'    => $pct_ong,
                'pct_sch'    => $pct_sch,
            ],
            'buses_on_journey' => $buses_on_journey,
            'latest_trips'     => $latest_trips,
        ]);

    } elseif ($role === 'driver' || $role === 'conductor') {
        $uid       = (int)$_SESSION['user_id'];
        $col       = $role === 'driver' ? 'driver_id' : 'conductor_id';
        $my_trips  = $pdo->query("
            SELECT t.id, t.status, t.start_time, b.bus_number, r.route_name
            FROM trips t
            JOIN buses b ON t.bus_id = b.id
            JOIN routes r ON t.route_id = r.id
            WHERE t.{$col} = {$uid} AND DATE(t.start_time) = '$today'
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($my_trips as &$tr) {
            $tr['start_time_fmt'] = $tr['start_time'] ? date('g:i A', strtotime($tr['start_time'])) : '—';
        }
        unset($tr);

        $done = count(array_filter($my_trips, fn($t) => $t['status'] === 'completed'));

        echo json_encode([
            'success'       => true,
            'role'          => $role,
            'my_trips_total'=> count($my_trips),
            'my_trips_done' => $done,
            'my_trips'      => $my_trips,
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No dashboard data for this role.']);
    }
    exit;
}

// ── Reset Database (Admin Only) ────────────────────────────────────────────
if ($action === 'ajax_reset_database') {
    requireRole(['admin']);

    // Require a confirmation token to prevent accidental resets
    $confirm = $_POST['confirm_token'] ?? '';
    if ($confirm !== 'RESET_CONFIRMED') {
        echo json_encode(['success' => false, 'message' => 'Missing confirmation token.']);
        exit;
    }

    try {
        $pdo->exec('PRAGMA foreign_keys = OFF');

        // Tables to wipe (in safe order)
        $tables = ['messages', 'trips', 'services', 'buses', 'routes'];
        foreach ($tables as $tbl) {
            $pdo->exec("DELETE FROM {$tbl}");
            // Reset auto-increment counters
            $pdo->exec("DELETE FROM sqlite_sequence WHERE name='{$tbl}'");
        }

        $pdo->exec('PRAGMA foreign_keys = ON');

        echo json_encode([
            'success' => true,
            'message' => 'Database has been reset. All operational data (trips, buses, routes, services, messages) has been cleared. User accounts were preserved.'
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Reset failed: ' . $e->getMessage()]);
    }
    exit;
}

// ── Auto-Start Trips: scheduled → ongoing when start_time reached ──────────
if ($action === 'ajax_auto_start_trips') {
    requireRole(['admin', 'fleet_manager', 'fleet manager']);

    try {
        // Fetch IDs of trips that should now be ongoing
        $stmt = $pdo->query("
            SELECT id FROM trips
            WHERE status = 'scheduled'
              AND start_time <= datetime('now','localtime')
        ");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE trips SET status = 'ongoing' WHERE id IN ($placeholders)")
                ->execute($ids);
        }

        echo json_encode(['success' => true, 'started_ids' => $ids]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
