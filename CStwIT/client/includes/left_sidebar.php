<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include '../config/database.php'; // Assumes this sets up $conn

// Get current page to highlight active nav item
$current_page = basename($_SERVER['PHP_SELF']);

// Determine if this is an auth page (login or register)
$is_auth_page = ($current_page == 'login.php' || $current_page == 'register.php');

// Fetch user data from the database
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("SELECT name, username, profile_pic FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $name = $user['name'] ?? 'User'; // Fallback if name is NULL
            $username = $user['username'] ?? 'yourusername'; // Fallback if username is NULL
            $profile_pic = $user['profile_pic'] ?? 'default.jpg'; // Default to default.jpg if not set
        } else {
            // Fallback if user not found
            $name = 'User';
            $username = 'yourusername';
            $profile_pic = 'default.jpg';
        }

        // Fetch the initial unread notification count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL");
        $stmt->execute([$_SESSION['user_id']]);
        $initial_notification_count = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error fetching user or notifications: " . $e->getMessage());
        // Fallback on database error
        $name = 'User';
        $username = 'yourusername';
        $profile_pic = 'default.jpg';
        $initial_notification_count = 0;
    }
} else {
    // Fallback if session not set
    $name = 'User';
    $username = 'yourusername';
    $profile_pic = 'default.jpg';
    $initial_notification_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CStwIT</title>
    <link rel="stylesheet" href="../assets/style.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Load JavaScript file containing likePost() function -->
    <script src="../assets/script.js"></script>

    <style>
        /* Style for the notification badge */
        .notification-badge {
            position: relative;
            display: inline-block;
        }
        .badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: #dc3545; /* Bootstrap danger color (red) */
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            display: none; /* Hidden by default, shown when count > 0 */
        }
    </style>
</head>

<body<?php echo $is_auth_page ? ' class="auth-page"' : ''; ?>>
    <?php if (!$is_auth_page): ?>
    
    <!-- Sidebar / Bottom Nav -->
    <div class="left-sidebar">
        <div class="profile-summary">
            <div class="profile-pic">
                <img src="../assets/uploads/<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
            </div>
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($name); ?></h3>
                <p>@<?php echo htmlspecialchars($username); ?></p>
            </div>
        </div>

        <nav class="cs-nav">
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-home"></i></span>
                <span class="nav-text">Home</span>
            </a>
            <a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-user"></i></span>
                <span class="nav-text">Profile</span>
            </a>
            <a href="my_post.php" class="<?php echo ($current_page == 'my_post.php') ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
                <span class="nav-text">My Post</span>
            </a>
            <a href="notifications.php" class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                <span class="nav-icon notification-badge">
                    <i class="fas fa-bell"></i>
                    <span id="notification-count" class="badge"><?php echo $initial_notification_count; ?></span>
                </span>
                <span class="nav-text">Notifications</span>
            </a>
            <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-cog"></i></span>
                <span class="nav-text">Settings</span>
            </a>
        </nav>
        
        <div class="logout-container">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt mr-2"></i> Log out
            </a>
        </div>
    </div>

    <!-- Mobile profile menu toggle -->
    <div class="mobile-menu-toggle">
        <i class="fas fa-user"></i>
    </div>

    <!-- Mobile profile menu popup -->
    <div class="mobile-profile-menu">
        <div class="mobile-profile-header">
            <img src="../assets/uploads/<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
            <div class="mobile-profile-header-info">
                <h4><?php echo htmlspecialchars($name); ?></h4>
                <p>@<?php echo htmlspecialchars($username); ?></p>
            </div>
        </div>
        <div class="mobile-profile-actions">
            <a href="notifications.php" class="mobile-notification-link">
                Notifications 
                <span id="mobile-notification-count" class="badge"><?php echo $initial_notification_count; ?></span>
            </a>
            <a href="logout.php" class="mobile-logout-btn">Log out</a>
        </div>
    </div>

    <!-- Main content area -->
    <div class="main-content">
        <!-- Your main content goes here -->
    </div>
    <?php endif; ?>

    <script>
        // Toggle mobile profile menu
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const mobileProfileMenu = document.querySelector('.mobile-profile-menu');
            
            if (mobileMenuToggle && mobileProfileMenu) {
                mobileMenuToggle.addEventListener('click', function() {
                    mobileProfileMenu.classList.toggle('active');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    if (!mobileProfileMenu.contains(event.target) && 
                        !mobileMenuToggle.contains(event.target)) {
                        mobileProfileMenu.classList.remove('active');
                    }
                });
            }

            // Polling function to check for new notifications
            function checkNotifications() {
                fetch('../api/get_notification_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const desktopCountElement = document.getElementById('notification-count');
                            const mobileCountElement = document.getElementById('mobile-notification-count');
                            const currentCount = parseInt(desktopCountElement.textContent);

                            if (data.count > currentCount) {
                                // Notify the user of new notifications
                                if (window.location.pathname.indexOf('notifications.php') === -1) {
                                    alert('You have ' + (data.count - currentCount) + ' new notification(s)!');
                                }
                            }

                            // Update both desktop and mobile notification counts
                            desktopCountElement.textContent = data.count;
                            mobileCountElement.textContent = data.count;

                            // Show/hide badge based on count
                            if (data.count > 0) {
                                desktopCountElement.style.display = 'inline-block';
                                mobileCountElement.style.display = 'inline-block';
                            } else {
                                desktopCountElement.style.display = 'none';
                                mobileCountElement.style.display = 'none';
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching notification count:', error));
            }

            // Initial check and set interval for polling every 30 seconds
            checkNotifications();
            setInterval(checkNotifications, 30000); // Poll every 30 seconds
        });

        // Adjust layout based on screen size
        function adjustLayoutForScreenSize() {
            // This is now handled by CSS media queries
            // The JavaScript is kept minimal for better performance
        }

        // Initial adjustment and listen for resize
        window.addEventListener('load', adjustLayoutForScreenSize);
        window.addEventListener('resize', adjustLayoutForScreenSize);
    </script>
</body>
</html>