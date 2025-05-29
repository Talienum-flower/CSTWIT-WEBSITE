<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error_message'] = "Please log in as an admin to access the dashboard.";
    header("Location: /CStwIT/admin/login.php");
    exit;
}

// Include database connection
include '../config/database.php';

// Check if post_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_posts.php");
    exit;
}

$post_id = $_GET['id'];

// Fetch post details
$post = $conn->prepare("
    SELECT p.id, p.content, p.created_at, u.name AS owner_name, u.id AS user_id
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$post->execute([$post_id]);
$post_data = $post->fetch();

if (!$post_data) {
    header("Location: manage_posts.php");
    exit;
}

// Determine which profile to show (using the user_id of the post owner)
$profileId = $post_data['user_id'];

if ($profileId === null) {
    $_SESSION['error_message'] = "Invalid user ID.";
    header("Location: /CStwIT/admin/login.php");
    exit();
}

// Fetch the user's data
try {
    $stmt = $conn->prepare("SELECT id, username, profile_pic, name, bio FROM users WHERE id = ?");
    $stmt->execute([$profileId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error_message'] = "User not found.";
        header("Location: /CStwIT/admin/login.php");
        exit();
    }

    // Fetch the number of posts for this user
    $stmtPosts = $conn->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmtPosts->execute([$profileId]);
    $postCount = $stmtPosts->fetchColumn();

    // Fetch the list of users this profile is following
    $stmtFollowing = $conn->prepare("SELECT followed_id, followed_username FROM follows WHERE follower_id = ?");
    $stmtFollowing->execute([$profileId]);
    $followingList = $stmtFollowing->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the list of this profile's followers
    $stmtFollowers = $conn->prepare("SELECT follower_id, follower_username FROM follows WHERE followed_id = ?");
    $stmtFollowers->execute([$profileId]);
    $followersList = $stmtFollowers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching profile: " . htmlspecialchars($e->getMessage());
    header("Location: /CStwIT/admin/login.php");
    exit();
}

// Fetch report details
$reports = $conn->prepare("
    SELECT r.id, r.reason, r.created_at AS report_date, u.name AS reporter_name, u.id AS user_id
    FROM reports r
    JOIN users u ON r.user_id = u.id
    WHERE r.post_id = ?
");
$reports->execute([$post_id]);
$report_data = $reports->fetchAll();

// Fetch counts
$like_count = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$like_count->execute([$post_id]);
$like_count = $like_count->fetchColumn();

$comment_count = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
$comment_count->execute([$post_id]);
$comment_count = $comment_count->fetchColumn();

$report_count = count($report_data);

// Handle actions (before any output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        foreach ($report_data as $report) {
            // Update report status to 'approved'
            $stmt = $conn->prepare("UPDATE reports SET status = 'approved' WHERE id = ?");
            $stmt->execute([$report['id']]);

            // Notify the user who reported the post
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, related_post_id, related_user_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $report['user_id'],
                "Your report for post has been reviewed and approved by an admin.",
                'report_approved',
                $post_id,
                $report['user_id'],
            ]);
        }
        header("Location: manage_posts.php");
        exit;
    } elseif (isset($_POST['delete'])) {
        $stmt = $conn->prepare("UPDATE posts SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$post_id]);
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, related_post_id, related_user_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $post_data['user_id'],
            "Your post  has been removed by an admin.",
            'post_deleted',
            $post_id,
            $post_data['user_id'],
        ]);
        header("Location: manage_posts.php");
        exit;
    } elseif (isset($_POST['warn'])) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, related_post_id, related_user_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $post_data['user_id'],
            "Your post has been reported for inappropriate content. Please review our community guidelines.",
            'post_warning',
            $post_id,
            $post_data['user_id'],
        ]);
        header("Location: manage_posts.php");
        exit;
    }
}

// Verify admin details
try {
    $adminId = (int)$_SESSION['admin_id'];
    $stmtAdmin = $conn->prepare("SELECT name, username FROM users WHERE id = ? AND role = 'admin'");
    $stmtAdmin->execute([$adminId]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $_SESSION['error_message'] = "Admin not found or unauthorized.";
        header("Location: /CStwIT/admin/login.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching admin details: " . htmlspecialchars($e->getMessage());
    header("Location: /CStwIT/admin/login.php");
    exit();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Configuration error: " . htmlspecialchars($e->getMessage());
    header("Location: /CStwIT/admin/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CStwIT Admin - View Reported Post</title>
    <style>
        /* CSS from the second snippet (dashboard layout) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
        }

        /* Navigation sidebar */
        nav {
            background-color: #8b0000;
            width: 220px;
            display: flex;
            flex-direction: column;
            color: white;
            padding-top: 20px;
            position: fixed;
            min-height: 100vh;
            background-image: url("../assets/owl-pattern.png");
            background-repeat: repeat;
            background-size: contain;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        nav:after {
            content: "CStwIT Admin";
            font-size: 18px;
            font-weight: bold;
            color: white;
            position: absolute;
            top: 75px;
            left: 45px;
            text-align: center;
        }

        nav::before {
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            content: "";
            display: block;
            width: 90%;
            margin: 120px auto 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            border-left: 5px solid transparent;
            transition: all 0.3s ease;
            margin: 10px 0;
            font-size: 14px;
        }

        nav a i {
            margin-right: 10px;
            font-size: 16px;
        }

        nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .logout-btn {
            background-color: white;
            color: #8b0000;
            width: 160px;
            text-align: center;
            margin: 0 auto;
            border-radius: 20px;
            padding: 10px 0;
            font-size: 14px;
            border: none;
            cursor: pointer;
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
        }

        /* Menu toggle button for mobile */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1001;
            background-color: #8b0000;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 16px;
        }

        /* Overlay for mobile nav */
        .nav-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* CSS from the first snippet (post view content) */
        .content-container {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 220px);
            background-color: #f5f6fa;
        }

        h2, h3 {
            color: #8b0000;
            margin-bottom: 15px;
        }

        h2 {
            font-size: 24px;
        }

        h3 {
            font-size: 18px;
        }

        .back-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #8b0000;
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
            transition: background-color 0.3s;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #700000;
        }

        .post-details, .report-details {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 20px;
        }

        .flagged-content {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #8b0000;
        }

        .flagged-content h3 {
            margin-top: 0;
            color: #8b0000;
        }

        p {
            margin: 5px 0;
            font-size: 14px;
            color: #333;
        }

        strong {
            color: #555;
        }

        .report-item {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 15px;
            background-color: #8b0000;
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .action-btn:hover {
            background-color: #700000;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            nav {
                transform: translateX(-100%);
                width: 80%;
                max-width: 300px;
            }

            nav.active {
                transform: translateX(0);
            }

            .content-container {
                margin-left: 0;
                width: 100%;
                padding: 10px;
            }

            .menu-toggle {
                display: block;
            }

            .nav-overlay.active {
                display: block;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 20px;
            }

            h3 {
                font-size: 16px;
            }

            p, .report-item {
                font-size: 12px;
            }

            .back-btn, .action-btn {
                font-size: 12px;
                padding: 6px 12px;
            }

            .post-details, .flagged-content, .report-details {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <button class="menu-toggle">☰ Menu</button>
    <div class="nav-overlay"></div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_posts.php">Manage Posts</a>
        <a href="logout.php" class="logout-btn" style="display: block; text-align: center;">Logout</a>
    </nav>

    <div class="content-container">
        <h2>View Reported Post</h2>
        <a href="manage_posts.php" class="back-btn">Back to List</a>

        <div class="post-details">
            <h3>Post Details</h3>
            <p><strong>Content:</strong> <?php echo htmlspecialchars($post_data['content']); ?></p>
            <p><strong>Posted by:</strong> <?php echo htmlspecialchars($post_data['owner_name']); ?> (Username: <?php echo htmlspecialchars($user['username']); ?>)</p>
            <p><strong>Bio:</strong> <?php echo htmlspecialchars($user['bio'] ?: 'No bio available'); ?></p>
            <p><strong>Total Posts:</strong> <?php echo $postCount; ?></p>
            <p><strong>Posted on:</strong> <?php echo $post_data['created_at']; ?></p>
        </div>

        <div class="flagged-content">
            <h3>Flagged Content</h3>
            <p>This post has been flagged for review due to potential violation of community guidelines.</p>
        </div>

        <div class="post-details">
            <h3>Post Statistics</h3>
            <p><strong>Likes:</strong> <?php echo $like_count; ?></p>
            <p><strong>Comments:</strong> <?php echo $comment_count; ?></p>
            <p><strong>Reports:</strong> <?php echo $report_count; ?></p>
        </div>

        <div class="action-buttons">
            <form method="POST" style="margin: 0;">
                <button type="submit" name="approve" class="action-btn">Approve Post</button>
            </form>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="warn" class="action-btn">Warn User</button>
            </form>
        </div>
        <br>

        <div class="report-details">
            <h3>Report Details</h3>
            <?php foreach ($report_data as $report): ?>
                <div class="report-item">
                    <p><strong>Reported by:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></p>
                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($report['reason']); ?></p>
                    <p><strong>Reported on:</strong> <?php echo $report['report_date']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Toggle menu functionality
        const menuToggle = document.querySelector('.menu-toggle');
        const nav = document.querySelector('nav');
        const overlay = document.querySelector('.nav-overlay');

        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function() {
            nav.classList.remove('active');
            overlay.classList.remove('active');
        });
    </script>
</body>
</html>