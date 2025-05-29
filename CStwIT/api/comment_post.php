<?php
// File: api/comment_post.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    error_log("Error: User not logged in. Session user_id not set.");
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit();
}

include '../config/database.php';
if (!file_exists('notifications_helper.php')) {
    error_log("notifications_helper.php not found in " . __DIR__);
    echo json_encode(["success" => false, "message" => "notifications_helper.php not found"]);
    exit();
}
include 'notifications_helper.php';
error_log("notifications_helper.php included successfully in comment_post.php. Function exists: " . (function_exists('createNotification') ? 'yes' : 'no'));

// Debug: Show received POST data
error_log("Received POST data: " . print_r($_POST, true));
var_dump($_POST);

if (isset($_POST['post_id'], $_POST['comment'])) {
    try {
        $post_id = (int)$_POST['post_id'];
        $user_id = $_SESSION['user_id'];
        $comment = trim($_POST['comment']);

        if (empty($comment)) {
            error_log("Error: Comment is empty for post_id=$post_id, user_id=$user_id");
            echo json_encode(["success" => false, "message" => "Comment cannot be empty"]);
            exit();
        }

        // Check if the post exists
        $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Post fetch result: " . print_r($post, true));
        if (!$post) {
            error_log("Post not found for post_id=$post_id");
            echo json_encode(["success" => false, "message" => "Post not found"]);
            exit();
        }

        // Insert the comment
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$post_id, $user_id, $comment]);
        error_log("Comment inserted for post_id=$post_id, user_id=$user_id");

        $post_owner_id = $post['user_id'];
        if ($post_owner_id != $user_id) {
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $commenter = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Commenter fetch result: " . print_r($commenter, true));

            if ($commenter) {
                $message = "@" . $commenter['username'] . " commented on your post";
                if (function_exists('createNotification')) {
                    $success = createNotification($conn, $post_owner_id, $user_id, $post_id, 'comment', $message);
                    error_log("createNotification result for comment: " . ($success ? "success" : "failed") . " | user_id=$post_owner_id, related_user_id=$user_id, post_id=$post_id");
                } else {
                    error_log("createNotification function not defined in comment_post.php");
                }
            } else {
                error_log("Failed to fetch commenter username for user_id=$user_id");
            }
        } else {
            error_log("No notification created: post_owner_id=$post_owner_id matches user_id=$user_id");
        }

        // Fetch updated comment count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $comment_count = $stmt->fetchColumn();

        // Fetch the new comment details to return
        $stmt = $conn->prepare("
            SELECT c.comment, c.created_at, u.username
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ? AND c.user_id = ?
            ORDER BY c.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$post_id, $user_id]);
        $new_comment = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "message" => "Comment added successfully",
            "comment_count" => $comment_count,
            "new_comment" => $new_comment
        ]);
    } catch (PDOException $e) {
        error_log("Comment submission failed: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Comment submission failed: " . $e->getMessage()]);
    }
} else {
    error_log("Error: Missing required fields (post_id or comment). Received: " . print_r($_POST, true));
    echo json_encode(["success" => false, "message" => "Missing required fields (post_id or comment)"]);
}
?>