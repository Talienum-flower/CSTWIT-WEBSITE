<?php
// File: client/notifications.php
include_once 'includes/session.php';
include_once 'includes/header.php';
include 'includes/left_sidebar.php';
include 'includes/right_sidebar.php';
include_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please log in to view your notifications.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all notifications as read when the page is viewed
try {
    $stmt = $conn->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL");
    $stmt->execute([$user_id]);
} catch (PDOException $e) {
    error_log("Error marking notifications as read: " . $e->getMessage());
    // Continue loading the page even if this fails, as it's not critical
}

include_once '../api/fetch_notifications.php';
?>

<div class="notifications-container" >
    <h2 style = color:black;>Notifications</h2>
    <?php if (isset($notifications) && is_array($notifications) && !empty($notifications)): ?>
        <?php foreach ($notifications as $notification): ?>
            <div class="notification">
                <p>
                    <?php echo htmlspecialchars($notification['message']); ?>
                    <small>(<?php echo htmlspecialchars($notification['type']); ?> by @<?php echo htmlspecialchars($notification['related_username'] ?? 'Unknown'); ?>)</small>
                </p>
                <small><?php echo htmlspecialchars($notification['created_at']); ?></small>
                
                <?php if ($notification['type'] === 'follow'): ?>
                    <a href="profile.php?user_id=<?php echo htmlspecialchars($notification['related_user_id']); ?>">View Profile</a>
                <?php elseif (in_array($notification['type'], ['like', 'comment']) && $notification['related_post_id']): ?>
                    <a href="post.php?id=<?php echo htmlspecialchars($notification['related_post_id']); ?>">View Post</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No notifications yet.</p>
    <?php endif; ?>
</div>
<?php ?>