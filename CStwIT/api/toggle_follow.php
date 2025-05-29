<?php
// Include database and session
include '../config/database.php';
include '../config/session.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('You must be logged in to follow users');
    }

    // Check if required parameters exist
    if (!isset($_POST['followed_id']) || empty($_POST['followed_id'])) {
        throw new Exception('Missing user ID to follow');
    }

    // Get user IDs
    $followed_id = $_POST['followed_id'];
    $follower_id = $_SESSION['user_id'];

    // Validate followed ID exists
    $check_user_stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ? AND status = 'active'");
    $check_user_stmt->execute([$followed_id]);
    $followed_user = $check_user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$followed_user) {
        throw new Exception('User to follow does not exist or is not active');
    }
    
    // Can't follow yourself
    if ($follower_id == $followed_id) {
        throw new Exception('You cannot follow yourself');
    }

    // Check if already following
    $check_stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?");
    $check_stmt->execute([$follower_id, $followed_id]);
    $follow_record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $already_following = $follow_record !== false;

    // Start transaction
    $conn->beginTransaction();

    if ($already_following) {
        // Unfollow: Delete the record
        $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$follower_id, $followed_id]);
        
        $action = 'unfollowed';
        $message = 'User unfollowed successfully';
    } else {
        // Get follower username
        $follower_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $follower_stmt->execute([$follower_id]);
        $follower_data = $follower_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$follower_data) {
            throw new Exception('Could not retrieve your user information');
        }
        
        $follower_username = $follower_data['username'];
        $followed_username = $followed_user['username'];
        
        // Follow: Insert new record
        $stmt = $conn->prepare("
            INSERT INTO follows (follower_id, follower_username, followed_id, followed_username) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$follower_id, $follower_username, $followed_id, $followed_username]);
        
        $action = 'followed';
        $message = 'User followed successfully';
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'action' => $action,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>