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
    $response['message'] = 'You must be logged in to edit a post.';
    echo json_encode($response);
    exit;
}

// Check if post_id and content parameters are provided
if (!isset($_POST['post_id']) || !isset($_POST['content'])) {
    $response['message'] = 'Missing required parameters.';
    echo json_encode($response);
    exit;
}

$post_id = $_POST['post_id'];
$content = trim($_POST['content']);
$user_id = $_SESSION['user_id'];

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
    
    if ($post['user_id'] != $user_id) {
        $response['message'] = 'You do not have permission to edit this post.';
        echo json_encode($response);
        exit;
    }
    
    // Update the post content
    $stmt = $conn->prepare("UPDATE posts SET content = ? WHERE id = ?");
    $stmt->execute([$content, $post_id]);
    
    $response['success'] = true;
    $response['message'] = 'Post updated successfully.';
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>