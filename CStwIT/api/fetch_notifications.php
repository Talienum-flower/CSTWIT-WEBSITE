<?php
// File: api/fetch_notifications.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../config/database.php';
include_once '../config/session.php';

$notifications = [];

if (!isset($_SESSION['user_id'])) {
    error_log("No user_id in session for fetch_notifications");
    return;
}

$user_id = $_SESSION['user_id'];
error_log("Fetching notifications for user_id=$user_id");

// Fetch notifications with related user and post info
try {
    $stmt = $conn->prepare("
        SELECT n.id, n.message, n.created_at, n.related_post_id, n.type, u.username AS related_username
        FROM notifications n
        LEFT JOIN users u ON n.related_user_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Fetched notifications: " . print_r($notifications, true));
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    $notifications = [];
}
?>