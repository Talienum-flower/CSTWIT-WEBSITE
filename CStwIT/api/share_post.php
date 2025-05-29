<?php
// File: api/share_post.php
include '../config/database.php';
include '../config/session.php';
if (!file_exists('notifications_helper.php')) {
    error_log("notifications_helper.php not found in " . __DIR__);
    die("Error: notifications_helper.php not found.");
}
include 'notifications_helper.php';
error_log("notifications_helper.php included successfully in share_post.php. Function exists: " . (function_exists('createNotification') ? 'yes' : 'no'));

// Debug: Show received POST data
error_log("Received POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['post_id'])) {
    error_log("Invalid request: Method=" . $_SERVER['REQUEST_METHOD'] . ", POST data=" . print_r($_POST, true));
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = (int)$_POST['post_id'];

try {
    // Fetch original post securely
    $stmt = $conn->prepare("SELECT content, user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $original = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Original post fetch result: " . print_r($original, true));

    if (!$original) {
        error_log("Post not found for post_id=$post_id");
        echo json_encode(["success" => false, "message" => "Post not found"]);
        exit();
    }

    $content = "Shared: " . $original['content'];
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $content]);
    error_log("Share post inserted for user_id=$user_id, post_id=$post_id");

    // Insert notification for post owner (exclude if same user)
    $post_owner_id = $original['user_id'];
    if ($post_owner_id != $user_id) {
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $sharer = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Sharer fetch result: " . print_r($sharer, true));

        if ($sharer) {
            $message = $sharer['username'] . " shared your post";
            if (function_exists('createNotification')) {
                $success = createNotification($conn, $post_owner_id, $user_id, $post_id, 'repost', $message);
                error_log("createNotification result for share: " . ($success ? "success" : "failed") . " | user_id=$post_owner_id, related_user_id=$user_id, post_id=$post_id");
            } else {
                error_log("createNotification function not defined in share_post.php");
            }
        } else {
            error_log("Failed to fetch sharer username for user_id=$user_id");
        }
    } else {
        error_log("No notification created: post_owner_id=$post_owner_id matches user_id=$user_id");
    }

    echo json_encode(["success" => true, "message" => "Post shared"]);
} catch (PDOException $e) {
    error_log("Share error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Share failed"]);
}
exit();
?>