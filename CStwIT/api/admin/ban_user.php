<?php
// Include database connection
include '../../config/database.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the user is logged in as admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin authentication required.'
    ]);
    exit;
}

// Check if the user ID is provided
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID not provided.'
    ]);
    exit;
}

$user_id = (int)$_GET['id'];

// Determine the action (default to ban if not specified)
$action = isset($_GET['action']) && $_GET['action'] === 'unban' ? 'unban' : 'ban';
$new_status = $action === 'ban' ? 'banned' : 'active';

// Prepare and execute the update query with a prepared statement
try {
    // Start a transaction to ensure atomicity
    $conn->beginTransaction();

    // Fetch current ban_version
    $stmt = $conn->prepare("SELECT ban_version FROM users WHERE id = ? AND role = 'user'");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'User not found or not a regular user.'
        ]);
        exit;
    }

    $new_ban_version = $user['ban_version'] + 1;

    // Update user status and ban_version
    $stmt = $conn->prepare("UPDATE users SET status = ?, ban_version = ? WHERE id = ? AND role = 'user'");
    $stmt->execute([$new_status, $new_ban_version, $user_id]);

    if ($stmt->rowCount() > 0) {
        // If banning, update related reports to resolved
        if ($action === 'ban') {
            $stmt = $conn->prepare("UPDATE reports SET status = 'resolved' WHERE reported_user_id = ? AND status = 'pending'");
            $stmt->execute([$user_id]);
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => $action === 'ban' ? 'User banned successfully.' : 'User unbanned successfully.',
            'redirect' => 'http://localhost/CStwIT/admin/manage_users.php' // Redirect admin back to manage_users.php
        ]);
    } else {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'User not found or status unchanged.'
        ]);
    }
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error updating user status: ' . htmlspecialchars($e->getMessage())
    ]);
}
?>