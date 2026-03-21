<?php
// includes/db.php
$db_file = __DIR__ . '/../fleet.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create users table if it doesn't exist
    $query = "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT,
        google_id TEXT UNIQUE,
        role TEXT DEFAULT 'user',
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Create messages table if it doesn't exist
    $query = "CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sender_id INTEGER NOT NULL,
        receiver_id INTEGER NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(sender_id) REFERENCES users(id),
        FOREIGN KEY(receiver_id) REFERENCES users(id)
    )";
    $pdo->exec($query);
    
    // Attempt to add google_id column if the table already exists but without the column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN google_id TEXT UNIQUE");
    } catch (PDOException $e) {}

    // Create buses table
    $query = "CREATE TABLE IF NOT EXISTS buses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bus_number TEXT NOT NULL UNIQUE,
        make TEXT,
        model TEXT,
        year INTEGER,
        capacity INTEGER,
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($query);

    // Create services table
    $query = "CREATE TABLE IF NOT EXISTS services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bus_id INTEGER NOT NULL,
        service_type TEXT NOT NULL,
        description TEXT,
        cost REAL,
        service_date DATE,
        status TEXT DEFAULT 'scheduled',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(bus_id) REFERENCES buses(id)
    )";
    $pdo->exec($query);

    // Create routes table
    $query = "CREATE TABLE IF NOT EXISTS routes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        route_name TEXT NOT NULL,
        start_location TEXT,
        end_location TEXT,
        distance REAL,
        estimated_duration INTEGER, -- stored in minutes
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($query);

    // Create trips table
    $query = "CREATE TABLE IF NOT EXISTS trips (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bus_id INTEGER,
        route_id INTEGER,
        driver_id INTEGER,
        start_time DATETIME,
        end_time DATETIME,
        status TEXT DEFAULT 'scheduled',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(bus_id) REFERENCES buses(id),
        FOREIGN KEY(route_id) REFERENCES routes(id),
        FOREIGN KEY(driver_id) REFERENCES users(id)
    )";
    $pdo->exec($query);

    // Attempt to add relational columns to buses table
    try { $pdo->exec("ALTER TABLE buses ADD COLUMN route_id INTEGER REFERENCES routes(id)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE buses ADD COLUMN driver_id INTEGER REFERENCES users(id)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE buses ADD COLUMN conductor_id INTEGER REFERENCES users(id)"); } catch (PDOException $e) {}

    // Attempt to add Live Tracking columns to buses
    try { $pdo->exec("ALTER TABLE buses ADD COLUMN gps_url TEXT"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE buses ADD COLUMN cctv_url_1 TEXT"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE buses ADD COLUMN cctv_url_2 TEXT"); } catch (PDOException $e) {}

    // Attempt to add role column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'user'");
    } catch (PDOException $e) {}

    // Attempt to add status column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN status TEXT DEFAULT 'pending'");
    } catch (PDOException $e) {}

    // Attempt to add staff profile columns
    try { $pdo->exec("ALTER TABLE users ADD COLUMN contact_no TEXT"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN license_number TEXT"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN emergency_contact TEXT"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN blood_type TEXT"); } catch (PDOException $e) {}

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
