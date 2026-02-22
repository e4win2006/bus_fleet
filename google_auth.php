<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// MOCK GOOGLE AUTH FLOW
// In a real application, you would use the Google API Client library:
// $client = new Google_Client();
// $client->setClientId(GOOGLE_CLIENT_ID);
// $client->setClientSecret(GOOGLE_CLIENT_SECRET);
// $client->setRedirectUri('http://localhost/BUS_FLEET/google_auth.php');
// $client->authenticate($_GET['code']);
// $payload = $client->verifyIdToken($client->getAccessToken()['id_token']);

// Since no credentials were provided, we'll simulate a successful Google login payload
$mock_payload = [
    'sub' => '101234567890123456789', // Mock Google ID
    'email' => 'demo.user@gmail.com',
    'name' => 'Demo User',
    'picture' => 'https://ui-avatars.com/api/?name=Demo+User'
];

$google_id = $mock_payload['sub'];
$email = $mock_payload['email'];
$name = $mock_payload['name'];

try {
    // Check if user already exists based on Google ID or Email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
    $stmt->execute([$google_id, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User exists. Update google_id if they previously signed up with email only
        if (empty($user['google_id'])) {
            $update_stmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $update_stmt->execute([$google_id, $user['id']]);
        }
        
        if ($user['status'] === 'pending') {
            header("Location: login.php?error=pending");
            exit;
        } elseif ($user['status'] === 'rejected') {
            header("Location: login.php?error=rejected");
            exit;
        }
        
        // Log them in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
    } else {
        // Create new user
        // We'll generate a unique username from their name
        $base_username = strtolower(str_replace(' ', '', $name));
        $username = $base_username . rand(100, 999);
        
        // Determine role and status based on email
        $role = ($email === 'edwintomjoseph41@gmail.com') ? 'admin' : 'user';
        $status = ($email === 'edwintomjoseph41@gmail.com') ? 'approved' : 'pending';

        $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, google_id, role, status) VALUES (?, ?, ?, ?, ?)");
        $insert_stmt->execute([$username, $email, $google_id, $role, $status]);
        
        if ($status === 'pending') {
            header("Location: login.php?error=pending");
            exit;
        }

        // Get the new user's ID
        $new_user_id = $pdo->lastInsertId();
        
        // Log them in
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
    }
    
    // Prevent session fixation
    session_regenerate_id(true);
    
    // Redirect to dashboard
    header("Location: dashboard.php");
    exit;
    
} catch (PDOException $e) {
    die("Authentication failed: " . $e->getMessage());
}
?>
