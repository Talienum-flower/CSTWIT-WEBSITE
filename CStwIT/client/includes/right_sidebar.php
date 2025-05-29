<?php
// File: includes/right_sidebar.php
// Adjust include paths relative to right_sidebar.php's location (C:\xampp\htdocs\CStwIT\client\includes\)
// Go up two levels to reach the root config directory
$db_path = dirname(dirname(__DIR__)) . '/config/database.php'; // Points to C:\xampp\htdocs\CStwIT\config\database.php
$session_path = __DIR__ . '/session.php'; // Points to C:\xampp\htdocs\CStwIT\client\includes\session.php

if (!file_exists($db_path)) {
    error_log("Database file not found at: $db_path");
    $users_to_follow = [];
} elseif (!file_exists($session_path)) {
    error_log("Session file not found at: $session_path");
    $users_to_follow = [];
} else {
    include $db_path;
    include $session_path;

    try {
        // Verify session user_id with debug
        if (!isset($_SESSION['user_id'])) {
            error_log("User not logged in for right sidebar. Session: " . print_r($_SESSION, true));
            $users_to_follow = [];
        } else {
            // Debug session
            error_log("Logged-in user ID: " . $_SESSION['user_id']);

            // Define the profile images directory with absolute path
            $profile_images_dir = dirname(dirname(__DIR__)) . '/assets/uploads/';
            if (!is_dir($profile_images_dir)) {
                if (!mkdir($profile_images_dir, 0755, true)) {
                    error_log("Failed to create profile images directory: $profile_images_dir");
                }
            }

            // Fetch active users excluding the logged-in user, admins, banned, and deleted users
            $stmt = $conn->prepare("SELECT id, username, profile_pic, name, created_at 
                                    FROM users 
                                    WHERE status = 'active' 
                                    AND id != ? 
                                    AND role != 'admin' 
                                    ORDER BY created_at DESC 
                                    LIMIT 5");
            $stmt->execute([$_SESSION['user_id']]);
            $users_to_follow = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debug query results
            error_log("Fetched users to follow: " . print_r($users_to_follow, true));

            // Check follow status for each user
            foreach ($users_to_follow as &$user) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followed_id = ?");
                $stmt->execute([$_SESSION['user_id'], $user['id']]);
                $user['is_followed'] = $stmt->fetchColumn() > 0;
            }
            unset($user);
        }
    } catch (PDOException $e) {
        error_log("Database error in right sidebar: " . $e->getMessage());
        $users_to_follow = [];
    }
}
?>

<style>
    
    </style>
    <div class="follow-suggestions">
        <div class="suggestions-header">
            <h2>Who to Follow</h2>
        </div>
        <div class="suggestions-list">
            <?php if (empty($users_to_follow)): ?>
                <div class="suggestion-item">
                    <div class="suggestion-info">
                        <span class="suggestion-fullname">No suggestions available.</span>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($users_to_follow as $user): ?>
                <div class="suggestion-item">
                    <div class="suggestion-user">
                        <div class="suggestion-avatar">
                            <img src="../assets/uploads/<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>'s profile">
                        </div>
                        <div class="suggestion-info">
                            <?php if (!empty($user['name'])): ?>
                                <span class="suggestion-fullname"><?php echo htmlspecialchars($user['name']); ?></span>
                            <?php endif; ?>
                            <span class="suggestion-username">@<?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                    </div>
                    <div class="suggestion-action">
                        <button class="follow-toggle-btn <?php echo $user['is_followed'] ? 'following' : ''; ?>" 
                                data-user-id="<?php echo $user['id']; ?>" 
                                data-followed="<?php echo $user['is_followed'] ? 'true' : 'false'; ?>">
                            <?php echo $user['is_followed'] ? 'Following' : 'Follow'; ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const followButtons = document.querySelectorAll('.follow-toggle-btn');
    
    followButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const isFollowed = this.getAttribute('data-followed') === 'true';
            const buttonElement = this;
            
            buttonElement.disabled = true;
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../api/toggle_follow.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                buttonElement.disabled = false;
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            if (response.action === 'followed') {
                                buttonElement.textContent = 'Following';
                                buttonElement.classList.add('following');
                                buttonElement.setAttribute('data-followed', 'true');
                            } else {
                                buttonElement.textContent = 'Follow';
                                buttonElement.classList.remove('following');
                                buttonElement.setAttribute('data-followed', 'false');
                            }
                        } else {
                            alert('Error: ' + response.message);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('An error occurred while processing the request.');
                    }
                } else {
                    alert('Request failed with status: ' + xhr.status);
                }
            };
            
            xhr.onerror = function() {
                buttonElement.disabled = false;
                alert('Request failed. Please try again.');
            };
            
            xhr.send('followed_id=' + encodeURIComponent(userId));
        });
    });
});
</script>