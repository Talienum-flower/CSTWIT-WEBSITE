
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
    $_SESSION['error_message'] = "Please log in as an admin to manage reported posts.";
    header("Location: /CStwIT/admin/login.php");
    exit();
}

// Verify admin details and fetch reported posts data
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

        // Fetch reported posts with reporter and owner details, excluding approved reports
        $reported_posts_result = $conn->query("
            SELECT r.id AS report_id, r.post_id, r.user_id AS reporter_id, r.reason, r.created_at AS report_date,
                   u1.name AS reporter_name,
                   u2.name AS owner_name
            FROM reports r
            JOIN users u1 ON r.user_id = u1.id
            JOIN posts p ON r.post_id = p.id
            JOIN users u2 ON p.user_id = u2.id
            WHERE r.status = 'pending'
        ");
        $reported_posts = $reported_posts_result ? $reported_posts_result->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $e) {
        $reported_posts = [];
        $_SESSION['error_message'] = "Error fetching data: " . htmlspecialchars($e->getMessage());
        error_log("Error fetching reported posts: " . $e->getMessage());
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
    <title>CStwIT Admin - Manage Reported Posts</title>
    <style>
        /* First CSS block (from second file, sidebar) */
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

        /* Second CSS block (from first file, manage reported posts) */
        /* Manage Reported Posts Styles */
        body {
            background-color: #f5f6fa;
        }

        .container {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 220px);
            background-color: #f5f6fa;
        }

        h2 {
            color: #8b0000;
            font-size: 24px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #8b0000;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e9ecef;
        }

        td {
            color: #333;
            border-bottom: 1px solid #dee2e6;
        }

        .button {
            display: inline-block;
            padding: 8px 12px;
            margin: 2px;
            background-color: #8b0000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #700000;
        }

        .no-reports {
            text-align: center;
            color: #555;
            font-size: 16px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                margin-left: 0;
                width: 100%;
                padding: 10px;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            th, td {
                min-width: 120px;
            }
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 20px;
            }

            th, td {
                font-size: 12px;
                padding: 8px 10px;
            }

            .button {
                padding: 6px 10px;
                font-size: 10px;
            }

            .no-reports {
                font-size: 14px;
                padding: 15px;
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
        <h2>Manage Reported Posts</h2>
        <?php if (empty($reported_posts)): ?>
            <div class="no-reports">
                There are no reports
            </div>
        <?php else: ?>
            <table>
                <tr>
                    <th>Reporter Name</th>
                    <th>Post Owner Name</th>
                    <th>Reason</th>
                    <th>Report Date</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($reported_posts as $report): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($report['reporter_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($report['owner_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($report['reason'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($report['report_date'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="view_report.php?id=<?php echo htmlspecialchars($report['post_id'] ?? ''); ?>" class="button">View Post</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <script>
        // Toggle menu functionality
        const menuToggle = document.querySelector('.menu-toggle');
        const nav = document.querySelector('nav');
        const overlay = document.querySelector('.nav-overlay');

        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.classList.toggle('sidebar-active');
        });

        // Close menu when clicking outside
        overlay.addEventListener('click', function() {
            nav.classList.remove('active');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-active');
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle for mobile
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', () => {
                    document.body.classList.toggle('sidebar-active');
                    
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) {
                        sidebar.classList.toggle('active');
                    }
                });
            }
            
            // Close sidebar when clicking outside of it (for mobile)
            document.addEventListener('click', function(event) {
                const sidebar = document.querySelector('.sidebar');
                const sidebarToggle = document.querySelector('.sidebar-toggle');
                
                if (sidebar && document.body.classList.contains('sidebar-active') && 
                    !sidebar.contains(event.target) && 
                    !sidebarToggle.contains(event.target) &&
                    !menuToggle.contains(event.target)) {
                    nav.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.classList.remove('sidebar-active');
                    if (sidebar) {
                        sidebar.classList.remove('active');
                    }
                }
            });
            
            // Responsive resizing
            function handleResize() {
                if (window.innerWidth > 768) {
                    document.body.classList.remove('sidebar-active');
                    nav.classList.remove('active');
                    overlay.classList.remove('active');
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) {
                        sidebar.classList.remove('active');
                    }
                }
            }
            
            window.addEventListener('resize', handleResize);
            handleResize();
        });
    </script>
</body>
</html>