<?php
// File: api/get_notification_count.php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL");
    $stmt->execute([$user_id]);
    $count = $stmt->fetchColumn();

    echo json_encode([
        "success" => true,
        "count" => $count
    ]);
} catch (PDOException $e) {
    error_log("Error fetching notification count: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>