<?php
// Include required files
include '../config/database.php';
include '../config/session.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to report a post.';
    echo json_encode($response);
    exit;
}

// Check if post_id and reason parameters are provided
if (!isset($_POST['post_id']) || !isset($_POST['reason'])) {
    $response['message'] = 'Missing required parameters.';
    echo json_encode($response);
    exit;
}

$post_id = $_POST['post_id'];
$reason = trim($_POST['reason']);
$user_id = $_SESSION['user_id'];

// Validate reason is not empty
if (empty($reason)) {
    $response['message'] = 'Report reason cannot be empty.';
    echo json_encode($response);
    exit;
}

try {
    // Check if the post exists
    $stmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        $response['message'] = 'Post not found.';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has already reported this post
    $stmt = $conn->prepare("SELECT id FROM reports WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    if ($stmt->fetch()) {
        $response['message'] = 'You have already reported this post.';
        echo json_encode($response);
        exit;
    }
    
    // Insert the report with status 'pending'
    $stmt = $conn->prepare("INSERT INTO reports (post_id, user_id, reason, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$post_id, $user_id, $reason]);
    
    $response['success'] = true;
    $response['message'] = 'Post reported successfully.';
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>