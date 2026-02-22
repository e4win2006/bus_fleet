<?php
session_start();
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Handle GET requests (Fetch messages)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
    if (!isset($_GET['receiver_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Receiver ID required']);
        exit;
    }
    
    $receiver_id = $_GET['receiver_id'];
    
    // Fetch messages between $current_user_id and $receiver_id
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?) 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$current_user_id, $receiver_id, $receiver_id, $current_user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit;
}

// Handle POST requests (Send messages)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    if (!isset($_POST['receiver_id']) || !isset($_POST['message'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit;
    }
    
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
    
    if (empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Message is empty']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$current_user_id, $receiver_id, $message]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>
