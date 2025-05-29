<?php
// File: api/like_post.php
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
    die("Error: notifications_helper.php not found.");
}
include 'notifications_helper.php';
error_log("notifications_helper.php included successfully in like_post.php. Function exists: " . (function_exists('createNotification') ? 'yes' : 'no'));

// Debug: Show received POST data
error_log("Received POST data: " . print_r($_POST, true));
var_dump($_POST);

if (isset($_POST['post_id'], $_POST['action'])) {
    try {
        $post_id = (int)$_POST['post_id'];
        $user_id = $_SESSION['user_id'];
        $action = trim($_POST['action']);

        if (empty($action)) {
            error_log("Error: Action is empty for post_id=$post_id, user_id=$user_id");
            echo json_encode(["success" => false, "message" => "Action cannot be empty"]);
            exit();
        }

        // Check if the post exists
        $stmt = $conn->prepare("SELECT id, user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Post fetch result: " . print_r($post, true));
        if (!$post) {
            error_log("Post not found for post_id=$post_id");
            echo json_encode(["success" => false, "message" => "Post not found"]);
            exit();
        }
        $post_owner_id = $post['user_id'];

        // Check if the user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_exists = $stmt->fetch();
        error_log("User fetch result for user_id=$user_id: " . print_r($user_exists, true));
        if (!$user_exists) {
            echo json_encode(["success" => false, "message" => "User not found"]);
            exit();
        }

        // Check if the user already liked the post
        $stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        $already_liked = $stmt->fetchColumn();

        if ($action === "unlike" && $already_liked) {
            $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$user_id, $post_id]);
            $stmt = $conn->prepare("UPDATE posts SET likes = GREATEST(likes - 1, 0) WHERE id = ?");
            $stmt->execute([$post_id]);
            error_log("Unlike processed for user_id=$user_id, post_id=$post_id");
        } elseif ($action === "like" && !$already_liked) {
            $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $post_id]);
            $stmt = $conn->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?");
            $stmt->execute([$post_id]);
            error_log("Like inserted for user_id=$user_id, post_id=$post_id");

            if ($post_owner_id != $user_id) {
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $liker = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Liker fetch result: " . print_r($liker, true));

                if ($liker) {
                    $message = "@" . $liker['username'] . " liked your post";
                    if (function_exists('createNotification')) {
                        $success = createNotification($conn, $post_owner_id, $user_id, $post_id, 'like', $message);
                        error_log("createNotification result for like: " . ($success ? "success" : "failed") . " | user_id=$post_owner_id, related_user_id=$user_id, post_id=$post_id");
                    } else {
                        error_log("createNotification function not defined in like_post.php");
                    }
                } else {
                    error_log("Failed to fetch liker username for user_id=$user_id");
                }
            } else {
                error_log("No notification created: post_owner_id=$post_owner_id matches user_id=$user_id");
            }
        } else {
            $message = $already_liked ? "Already liked" : "Not liked yet";
            error_log("Invalid action or state: $message for post_id=$post_id, user_id=$user_id");
            echo json_encode(["success" => false, "message" => $message]);
            exit();
        }

        $stmt = $conn->prepare("SELECT likes FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $likes = $stmt->fetchColumn();

        $stmt = $conn->prepare("
            SELECT u.username
            FROM likes l
            JOIN users u ON l.user_id = u.id
            WHERE l.post_id = ?
        ");
        $stmt->execute([$post_id]);
        $liked_by = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $isLiked = $action === "unlike" ? false : ($already_liked ? true : ($action === "like"));

        echo json_encode([
            "success" => true,
            "likes" => $likes,
            "isLiked" => $isLiked,
            "liked_by" => $liked_by
        ]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    error_log("Error: Missing required fields (post_id or action). Received: " . print_r($_POST, true));
    echo json_encode(["success" => false, "message" => "Missing post_id or action"]);
}
?>