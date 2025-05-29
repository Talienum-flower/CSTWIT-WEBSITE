<?php
// File: api/notifications_helper.php

function createNotification($conn, $user_id, $related_user_id, $related_post_id, $type, $message) {
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, related_user_id, related_post_id, type, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $success = $stmt->execute([$user_id, $related_user_id, $related_post_id, $type, $message]);
        if ($success) {
            error_log("Notification created successfully: user_id=$user_id, related_user_id=$related_user_id, related_post_id=$related_post_id, type=$type, message=$message");
            return true;
        } else {
            error_log("Notification insert failed: user_id=$user_id, related_user_id=$related_user_id, related_post_id=$related_post_id, type=$type, message=$message");
            return false;
        }
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage() . " | Params: user_id=$user_id, related_user_id=$related_user_id, related_post_id=$related_post_id, type=$type, message=$message");
        return false;
    }
}
?>