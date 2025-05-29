<?php
// Start session
session_start();

// Include database connection
if (!file_exists('../../config/database.php')) {
    $_SESSION['error_message'] = 'Configuration error: Database configuration file not found.';
    header('Location: /CStwIT/admin/manage_users.php');
    exit();
}
include '../../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error_message'] = 'Unauthorized access. Please log in as an admin.';
    header('Location: /CStwIT/admin/login.php');
    exit();
}

// Verify admin role
try {
    $adminId = (int)$_SESSION['admin_id'];
    $stmtAdmin = $conn->prepare("SELECT name, username FROM users WHERE id = ? AND role = 'admin'");
    $stmtAdmin->execute([$adminId]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $_SESSION['error_message'] = 'Admin not found or unauthorized.';
        header('Location: /CStwIT/admin/login.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Error verifying admin: ' . htmlspecialchars($e->getMessage());
    header('Location: /CStwIT/admin/login.php');
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid user ID.';
    header('Location: /CStwIT/admin/manage_users.php');
    exit();
}

$userId = (int)$_GET['id'];

try {
    // Start a transaction
    $conn->beginTransaction();

    // Check if the user exists
    $stmtUser = $conn->prepare("SELECT id, status FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $conn->rollBack();
        $_SESSION['error_message'] = 'User not found.';
        header('Location: /CStwIT/admin/manage_users.php');
        exit();
    }

    // Check if the user has a deletion request or already approved
    if ($user['status'] !== 'deletion_requested' && $user['status'] !== 'pending_success') {
        $conn->rollBack();
        $_SESSION['error_message'] = 'User does not have a deletion request pending or approved.';
        header('Location: /CStwIT/admin/manage_users.php');
        exit();
    }

    // Update the user's status to 'pending_success' if not already
    if ($user['status'] !== 'pending_success') {
        $stmtUpdate = $conn->prepare("UPDATE users SET status = 'pending_success' WHERE id = ?");
        $stmtUpdate->execute([$userId]);
    }

    // Delete dependent records in the correct order
    // 1. Delete reports where the user is the reporter or reported user
    $stmtDeleteReports = $conn->prepare("DELETE FROM reports WHERE user_id = ? OR reported_user_id = ?");
    $stmtDeleteReports->execute([$userId, $userId]);

    // 2. Delete comments made by the user (not tied to their posts, as post-related comments are handled by CASCADE)
    $stmtDeleteComments = $conn->prepare("DELETE FROM comments WHERE user_id = ?");
    $stmtDeleteComments->execute([$userId]);

    // 3. Delete notifications involving the user (post-related notifications are handled by CASCADE)
    $stmtDeleteNotifications = $conn->prepare("DELETE FROM notifications WHERE user_id = ? OR related_user_id = ?");
    $stmtDeleteNotifications->execute([$userId, $userId]);

    // 4. Delete likes by the user (post-related likes are handled by CASCADE)
    $stmtDeleteLikes = $conn->prepare("DELETE FROM likes WHERE user_id = ?");
    $stmtDeleteLikes->execute([$userId]);

    // 5. Delete follows involving the user
    $stmtDeleteFollows = $conn->prepare("DELETE FROM follows WHERE follower_id = ? OR followed_id = ?");
    $stmtDeleteFollows->execute([$userId, $userId]);

    // 6. Delete the user's posts (cascades to comments, notifications, likes, and reports on those posts)
    $stmtDeletePosts = $conn->prepare("DELETE FROM posts WHERE user_id = ?");
    $stmtDeletePosts->execute([$userId]);

    // 7. Delete the user
    $stmtDeleteUser = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmtDeleteUser->execute([$userId]);

    // Commit the transaction
    $conn->commit();

    // Set success message
    $_SESSION['success_message'] = 'User successfully deleted.';
} catch (PDOException $e) {
    // Roll back the transaction on error
    $conn->rollBack();
    // Log the error for debugging
    error_log("Error approving deletion for user ID $userId: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error approving deletion: ' . htmlspecialchars($e->getMessage());
} catch (Exception $e) {
    // Roll back the transaction on error
    $conn->rollBack();
    // Log the error for debugging
    error_log("Unexpected error for user ID $userId: " . $e->getMessage());
    $_SESSION['error_message'] = 'Unexpected error: ' . htmlspecialchars($e->getMessage());
}

// Redirect to manage_users.php
header('Location: /CStwIT/admin/manage_users.php');
exit();
?>