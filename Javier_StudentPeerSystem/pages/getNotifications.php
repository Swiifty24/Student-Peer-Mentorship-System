<?php
// pages/getNotifications.php
session_start();
require_once '../classes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];
$conn = new Database();
$pdo = $conn->connect();

// Handle POST actions (mark as read)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_read' && isset($_POST['notificationID'])) {
        $notificationID = filter_input(INPUT_POST, 'notificationID', FILTER_VALIDATE_INT);
        
        $stmt = $pdo->prepare("UPDATE notifications SET isRead = 1 WHERE notificationID = ? AND userID = ?");
        $success = $stmt->execute([$notificationID, $userId]);
        
        echo json_encode(['success' => $success]);
        exit();
    }
    
    if ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET isRead = 1 WHERE userID = ?");
        $success = $stmt->execute([$userId]);
        
        echo json_encode(['success' => $success]);
        exit();
    }
}

// GET request - fetch notifications
try {
    // Get all notifications for user
    $stmt = $pdo->prepare("
        SELECT notificationID, message, type, isRead, createdAt
        FROM notifications 
        WHERE userID = ? 
        ORDER BY createdAt DESC 
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count unread
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE userID = ? AND isRead = 0");
    $stmt->execute([$userId]);
    $unreadCount = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unreadCount' => $unreadCount
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}