<?php
// File: api/follow_user.php - Fixed version
include '../config/database.php';
include '../config/session.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Validate POST parameters
if (!isset($_POST['followed_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$followed_id = (int)$_POST['followed_id'];
$follower_id = (int)$_SESSION['user_id'];
$action = $_POST['action'];

// Validate parameters
if ($followed_id <= 0 || $follower_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user IDs']);
    exit;
}

if ($followed_id === $follower_id) {
    echo json_encode(['success' => false, 'message' => 'Cannot follow yourself']);
    exit;
}

try {
    // Fetch usernames
    $follower_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $follower_stmt->execute([$follower_id]);
    $follower_user = $follower_stmt->fetch();
    
    $followed_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $followed_stmt->execute([$followed_id]);
    $followed_user = $followed_stmt->fetch();
    
    if (!$follower_user || !$followed_user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $follower_username = $follower_user['username'];
    $followed_username = $followed_user['username'];
    
    if ($action === 'follow') {
        // Check if already following
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followed_id = ?");
        $check_stmt->execute([$follower_id, $followed_id]);
        
        if ($check_stmt->fetchColumn() == 0) {
            // Insert new follow relationship
            $stmt = $conn->prepare("INSERT INTO follows (follower_id, follower_username, followed_id, followed_username) VALUES (?, ?, ?, ?)");
            $stmt->execute([$follower_id, $follower_username, $followed_id, $followed_username]);
        }
        
    } elseif ($action === 'unfollow') {
        // Remove follow relationship
        $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$follower_id, $followed_id]);
    }
    
    // Get updated follower count
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
    $count_stmt->execute([$followed_id]);
    $follower_count = $count_stmt->fetchColumn();
    
    // Check current follow status
    $status_stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followed_id = ?");
    $status_stmt->execute([$follower_id, $followed_id]);
    $is_following = $status_stmt->fetchColumn() > 0;
    
    echo json_encode([
        'success' => true,
        'is_following' => $is_following,
        'follower_count' => $follower_count,
        'action' => $action
    ]);
    
} catch (PDOException $e) {
    error_log("Follow error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>