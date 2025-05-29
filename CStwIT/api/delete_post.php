<?php
// File: api/delete_post.php
include '../config/database.php';
include '../config/session.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to delete a post.';
    echo json_encode($response);
    exit;
}

// Check if post_id parameter is provided
if (!isset($_POST['post_id'])) {
    $response['message'] = 'Missing required parameter: post_id';
    echo json_encode($response);
    exit;
}

$post_id = (int)$_POST['post_id']; // Cast to integer for safety
$user_id = (int)$_SESSION['user_id'];

try {
    // First, check if the post exists and belongs to the current user
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        $response['message'] = 'Post not found.';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has permission to delete this post
    if ($post['user_id'] != $user_id) {
        // Check if user is an admin
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['role'] !== 'admin') {
            $response['message'] = 'You do not have permission to delete this post.';
            echo json_encode($response);
            exit;
        }
    }
    
    // Begin transaction for data integrity
    $conn->beginTransaction();
    
    // Delete related records first (notifications, comments, likes, reports)
    $stmt = $conn->prepare("DELETE FROM notifications WHERE related_post_id = ?");
    $stmt->execute([$post_id]);
    
    $stmt = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt->execute([$post_id]);
    
    $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    
    $stmt = $conn->prepare("DELETE FROM reports WHERE post_id = ?");
    $stmt->execute([$post_id]);
    
    // Finally, delete the post
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    
    // Commit the transaction
    $conn->commit();
    
    // Fetch updated post count for confirmation (optional, for real-time UI updates)
    $stmt = $conn->query("SELECT COUNT(*) FROM posts");
    $updated_post_count = $stmt->fetchColumn();
    
    $response['success'] = true;
    $response['message'] = 'Post deleted successfully.';
    $response['post_count'] = $updated_post_count;
    error_log("Post $post_id deleted by user $user_id. New post count: $updated_post_count");
    
} catch (PDOException $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("Delete post error for post_id $post_id: " . $e->getMessage());
}

echo json_encode($response);
exit;
?>