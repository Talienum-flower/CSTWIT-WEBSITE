<?php
// Check if session is already started before attempting to start it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine if this is an auth page (login page)
$current_file = basename($_SERVER['PHP_SELF']);
$is_auth_page = ($current_file == 'login.php');

// Check if admin is logged in, but skip for the login page
if (!$is_auth_page && !isset($_SESSION['admin_id'])) {
    $_SESSION['error_message'] = "Please log in as an admin to access the dashboard.";
    header("Location: /CStwIT/admin/login.php");
    exit();
}

// Verify admin details and fetch dashboard data
if (!$is_auth_page) {
    try {
        // Include database connection with error handling
        if (!file_exists('../config/database.php')) {
            throw new Exception("Database configuration file not found.");
        }
        include '../config/database.php';

        if (!isset($conn) || !$conn instanceof PDO) {
            throw new Exception("Database connection not established.");
        }

        $adminId = (int)$_SESSION['admin_id'];

        // Fetch admin details
        $stmtAdmin = $conn->prepare("SELECT name, username FROM users WHERE id = ? AND role = 'admin'");
        $stmtAdmin->execute([$adminId]);
        $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            $_SESSION['error_message'] = "Admin not found or unauthorized.";
            header("Location: /CStwIT/admin/login.php");
            exit();
        }

        // Fetch dashboard data
        $user_count = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $post_count = $conn->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        $report_count = $conn->query("SELECT COUNT(*) FROM reports")->fetchColumn();

        // Recent Activity
        $new_users = $conn->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $deletion_requests = $conn->query("SELECT id, username, email, created_at, deletion_reason FROM users WHERE status = 'deletion_requested' ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $user_reports = $conn->query("
            SELECT 
                r.id, 
                r.user_id, 
                r.reported_user_id, 
                r.reason, 
                r.created_at, 
                u.username AS reporter_username,
                COALESCE(u2.username, p.content) AS reported_content,
                CASE 
                    WHEN r.post_id IS NOT NULL THEN 'Post'
                    ELSE 'User'
                END AS report_type
            FROM reports r 
            JOIN users u ON r.user_id = u.id
            LEFT JOIN users u2 ON r.reported_user_id = u2.id
            LEFT JOIN posts p ON r.post_id = p.id
            WHERE r.status = 'pending'
            ORDER BY r.created_at DESC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error fetching data: " . htmlspecialchars($e->getMessage());
        header("Location: /CStwIT/admin/login.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Configuration error: " . htmlspecialchars($e->getMessage());
        header("Location: /CStwIT/admin/login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CStwIT Admin Dashboard</title>
    <style>
        /* First CSS block (unchanged) */
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
            background-color: #8b0000; /* Deep red color */
            width: 220px;
            display: flex;
            flex-direction: column;
            color: white;
            padding-top: 20px;
            position: fixed;
            min-height: 100vh;
            background-image: url("../assets/owl-pattern.png"); /* Add owl pattern background */
            background-repeat: repeat;
            background-size: contain;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        /* Logo and title */
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

        /* Horizontal separator line */
        nav::before {
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            content: "";
            display: block;
            width: 90%;
            margin: 120px auto 20px;
        }

        /* Navigation links */
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
            width: 160px; /* Fixed width to ensure proper centering */
            text-align: center;
            margin: 0 auto; /* Centers the button horizontally */
            border-radius: 20px;
            padding: 10px 0;
            font-size: 14px;
            border: none;
            cursor: pointer;
            position: absolute;
            bottom: 20px; /* Positions it at the bottom */
            left: 50%; /* Moves the left edge to the center of the nav */
            transform: translateX(-50%); /* Shifts it back by half its width to center */
        }

        /* Main content area */
        .container {
            flex: 1;
            padding: 20px;
            background-color: #f4f4f4;
            margin-left: 220px; /* Match the width of the nav */
            transition: margin-left 0.3s ease;
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

        /* Responsive styles */
        @media screen and (max-width: 768px) {
            nav {
                transform: translateX(-100%);
                width: 80%; /* Take up most of the screen on mobile */
                max-width: 300px;
            }
            
            nav.active {
                transform: translateX(0);
            }
            
            .container {
                margin-left: 0;
                width: 100%;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .nav-overlay.active {
                display: block;
            }
        }

        /* Second CSS block (unchanged) */
        :root {
            --primary-color: #8b0000;
            --bg-color: #f5f7fa;
            --text-color: #333;
            --card-bg: #fff;
            --shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            --border-radius: 10px;
            --spacing-sm: 10px;
            --spacing-md: 20px;
            --spacing-lg: 30px;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: var(--bg-color);
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-color);
        }

        .container {
            margin-left: 500px;
            width: 100%;
            padding: var(--spacing-md);
            max-width: 1400px;
            margin: 0 auto 0 250px;
        }

        h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: var(--spacing-md);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .metric-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: var(--spacing-md);
            text-align: center;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .metric-card p:first-child {
            color: var(--primary-color);
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: var(--spacing-sm);
            text-transform: uppercase;
        }

        .metric-card p:last-child {
            color: var(--text-color);
            font-size: 1.75rem;
            font-weight: bold;
            margin-top: 5px;
        }

        h3 {
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-bottom: var(--spacing-sm);
            padding-bottom: 5px;
            border-bottom: 1px solid var(--primary-color);
        }

        .activity-section {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .activity-accordion {
            width: 100%;
        }

        .activity-accordion h4 {
            color: var(--primary-color);
            font-size: 1rem;
            margin: var(--spacing-sm) 0;
            cursor: pointer;
            padding: var(--spacing-sm);
            background-color: rgba(139, 0, 0, 0.05);
            border-radius: 5px;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .activity-accordion h4::after {
            content: '+';
            font-size: 1.2rem;
            font-weight: bold;
        }

        .activity-accordion h4.active {
            background-color: rgba(139, 0, 0, 0.1);
        }

        .activity-accordion h4.active::after {
            content: '-';
        }

        .activity-content {
            display: none;
            padding: var(--spacing-sm);
            border-left: 2px solid rgba(139, 0, 0, 0.2);
            margin-left: var(--spacing-sm);
        }

        .activity-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .activity-content p {
            margin: var(--spacing-sm) 0;
            font-size: 0.875rem;
            color: #555;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-content p:last-child {
            border-bottom: none;
        }

        .activity-content a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }

        .activity-content a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                margin-left: 200px;
            }
        }

        @media (max-width: 768px) {
            .container {
                margin-left: 0;
                padding: var(--spacing-sm);
            }
            
            .metrics {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: var(--spacing-sm);
            }
            
            h2 {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .metrics {
                grid-template-columns: 1fr;
            }
            
            .metric-card {
                margin-bottom: var(--spacing-sm);
            }
            
            h2 {
                font-size: 1.1rem;
            }
            
            h3 {
                font-size: 1rem;
            }
            
            .metric-card p:last-child {
                font-size: 1.5rem;
            }
            
            .activity-accordion h4 {
                font-size: 0.9rem;
            }
            
            .activity-content p {
                font-size: 0.8rem;
            }
            
            .activity-section {
                padding: var(--spacing-sm);
            }
        }

        /* Sidebar toggle support */
        .sidebar-toggle {
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

        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block;
            }
            
            body.sidebar-active .container {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <button class="menu-toggle sidebar-toggle">☰ Menu</button>
    <div class="nav-overlay"></div>

    <nav class="sidebar">
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_posts.php">Manage Posts</a>
        <a href="logout.php" class="logout-btn" style="display: block; text-align: center;">Logout</a>
    </nav>

    <div class="container">
        <h2>Admin Dashboard</h2>

        <div class="metrics">
            <div class="metric-card">
                <p>Total Users</p>
                <p><?php echo number_format($user_count); ?></p>
            </div>
            <div class="metric-card">
                <p>Total Posts</p>
                <p><?php echo number_format($post_count); ?></p>
            </div>
            <div class="metric-card">
                <p>Total Reports</p>
                <p><?php echo number_format($report_count); ?></p>
            </div>
        </div>

        <h3>Recent Activity</h3>
        <div class="activity-section">
            <div class="activity-accordion">
                <h4 class="active">New Registered Users</h4>
                <div class="activity-content active">
                    <?php if (empty($new_users)): ?>
                        <p>No new users.</p>
                    <?php else: ?>
                        <?php foreach ($new_users as $user): ?>
                            <p><?php echo htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['email']) . ') - ' . htmlspecialchars($user['created_at']); ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h4>Users Requesting Deletion</h4>
                <div class="activity-content">
                    <?php if (empty($deletion_requests)): ?>
                        <p>No deletion requests.</p>
                    <?php else: ?>
                        <?php foreach ($deletion_requests as $user): ?>
                            <p>
                                <a href="manage_users.php"><?php echo htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['email']) . ')'; ?></a>
                                - Reason: <?php echo htmlspecialchars($user['deletion_reason']); ?>
                                - Requested: <?php echo htmlspecialchars($user['created_at']); ?>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h4>Users Who Reported Others</h4>
                <div class="activity-content">
                    <?php if (empty($user_reports)): ?>
                        <p>No recent reports.</p>
                    <?php else: ?>
                        <?php foreach ($user_reports as $report): ?>
                            <p>
                                <a href="manage_posts.php"><?php echo htmlspecialchars($report['reporter_username']); ?></a> reported
                                <?php if ($report['report_type'] === 'Post'): ?>
                                    a post (Content: <?php echo htmlspecialchars(substr($report['reported_content'], 0, 50) . (strlen($report['reported_content']) > 50 ? '...' : '')); ?>)
                                <?php else: ?>
                                    <a href="manage_users.php"><?php echo htmlspecialchars($report['reported_content']); ?></a>
                                <?php endif; ?>
                                - Reason: <?php echo htmlspecialchars($report['reason']); ?>
                                - Reported: <?php echo htmlspecialchars($report['created_at']); ?>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle for mobile
            const toggleButton = document.querySelector('.menu-toggle');
            const nav = document.querySelector('nav');
            const overlay = document.querySelector('.nav-overlay');

            toggleButton.addEventListener('click', function() {
                nav.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.classList.toggle('sidebar-active');
            });

            overlay.addEventListener('click', function() {
                nav.classList.remove('active');
                overlay.classList.remove('active');
                document.body.classList.remove('sidebar-active');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                if (document.body.classList.contains('sidebar-active') &&
                    !nav.contains(event.target) &&
                    !toggleButton.contains(event.target)) {
                    nav.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.classList.remove('sidebar-active');
                }
            });

            // Accordion functionality
            const headers = document.querySelectorAll('.activity-accordion h4');
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const content = header.nextElementSibling;
                    const isActive = header.classList.contains('active');

                    // Close all sections
                    headers.forEach(h => h.classList.remove('active'));
                    document.querySelectorAll('.activity-content').forEach(c => c.classList.remove('active'));

                    // Open the clicked section if it wasn't active
                    if (!isActive) {
                        header.classList.add('active');
                        content.classList.add('active');
                    }
                });
            });

            // Handle window resize
            function handleResize() {
                if (window.innerWidth > 768) {
                    nav.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.classList.remove('sidebar-active');
                }
            }

            window.addEventListener('resize', handleResize);
            handleResize();
        });
    </script>
</body>
</html>