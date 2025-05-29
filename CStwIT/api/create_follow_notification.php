<?php
// File: api/create_follow_notification.php
// This file will create follow notifications when a user follows another user

include_once '../config/database.php';
include_once '../config/session.php';

function createFollowNotification($follower_id, $followed_id) {
    global $conn;
    
    // Get follower username
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$follower_id]);
    $follower = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$follower) {
        error_log("Error: Follower user not found for ID: $follower_id");
        return false;
    }
    
    // Check if the followed user is also following the follower
    $stmt = $conn->prepare("
        SELECT COUNT(*) as mutual 
        FROM follows 
        WHERE follower_id = ? AND followed_id = ?
    ");
    $stmt->execute([$followed_id, $follower_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $is_mutual = ($result && $result['mutual'] > 0);
    
    // Create message based on mutual status
    if ($is_mutual) {
        $message = $follower['username'] . " followed you back!";
    } else {
        $message = $follower['username'] . " started following you";
    }
    
    // Insert notification
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, related_user_id, message, type) 
            VALUES (?, ?, ?, 'follow')
        ");
        $stmt->execute([$followed_id, $follower_id, $message]);
        return true;
    } catch (PDOException $e) {
        error_log("Error creating follow notification: " . $e->getMessage());
        return false;
    }
}
?>