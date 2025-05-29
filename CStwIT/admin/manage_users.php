<?php
// Prevent browser caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if session is already started before attempting to start it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    error_log("Session started in manage_users.php. Session data: " . print_r($_SESSION, true));
}

// Determine if this is an auth page (login page)
$current_file = basename($_SERVER['PHP_SELF']);
$is_auth_page = ($current_file == 'login.php');

// Check if admin is logged in, but skip for the login page
if (!$is_auth_page && !isset($_SESSION['admin_id'])) {
    error_log("Redirecting to login.php: Admin ID not set");
    $_SESSION['error_message'] = "Please log in as an admin to manage users.";
    header("Location: /CStwIT/admin/login.php");
    exit();
}

// Verify admin details and fetch users data
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
        error_log("Admin ID: $adminId");

        // Fetch admin details
        $stmtAdmin = $conn->prepare("SELECT name, username FROM users WHERE id = ? AND role = 'admin'");
        $stmtAdmin->execute([$adminId]);
        $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            error_log("Redirecting to login.php: Admin not found or unauthorized");
            $_SESSION['error_message'] = "Admin not found or unauthorized.";
            header("Location: /CStwIT/admin/login.php");
            exit();
        }

        // Fetch users and their report counts (user and post reports), sorted by created_at in descending order
        $users_result = $conn->query("
            SELECT 
                u.*,
                (SELECT COUNT(*) FROM reports r 
                 WHERE (r.reported_user_id = u.id OR 
                        r.post_id IN (SELECT id FROM posts p WHERE p.user_id = u.id)) 
                 AND r.status = 'pending') AS report_count
            FROM users u 
            WHERE u.role = 'user'
            ORDER BY u.created_at DESC
        ");
        $users = $users_result ? $users_result->fetchAll(PDO::FETCH_ASSOC) : [];
        error_log("Fetched " . count($users) . " users in manage_users.php");

        // Fetch detailed reports for each user (user and post reports)
        $reports = [];
        foreach ($users as $user) {
            $user_id = $user['id'];
            $stmt = $conn->prepare("
                SELECT 
                    r.id, 
                    r.user_id AS reporter_id, 
                    r.reported_user_id, 
                    r.post_id,
                    r.reason, 
                    r.created_at, 
                    u.username AS reporter_username,
                    CASE 
                        WHEN r.post_id IS NOT NULL THEN 'Post'
                        ELSE 'User'
                    END AS report_type,
                    COALESCE(p.content, '') AS post_content
                FROM reports r 
                JOIN users u ON r.user_id = u.id 
                LEFT JOIN posts p ON r.post_id = p.id
                WHERE (r.reported_user_id = ? OR 
                       r.post_id IN (SELECT id FROM posts p2 WHERE p2.user_id = ?)) 
                AND r.status = 'pending'
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$user_id, $user_id]);
            $reports[$user_id] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $users = [];
        error_log("Redirecting to login.php: PDOException - " . $e->getMessage());
        $_SESSION['error_message'] = "Error fetching data: " . htmlspecialchars($e->getMessage());
        header("Location: /CStwIT/admin/login.php");
        exit;
    } catch (Exception $e) {
        error_log("Redirecting to login.php: Exception - " . $e->getMessage());
        $_SESSION['error_message'] = "Configuration error: " . htmlspecialchars($e->getMessage());
        header("Location: /CStwIT/admin/login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CStwIT Admin - Manage Users</title>
    <style>
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

        .container {
            flex: 1;
            padding: 20px;
            background-color: #f4f4f4;
            margin-left: 220px;
            transition: margin-left 0.3s ease;
        }

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

        @media screen and (max-width: 768px) {
            nav {
                transform: translateX(-100%);
                width: 80%;
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

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-banned {
            color: #dc3545;
            font-weight: bold;
        }

        .status-pending-deletion {
            color: #ffcc00;
            font-weight: bold;
        }

        .status-approved-deletion {
            color: #ff6600;
            font-weight: bold;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }

        .close-modal:hover {
            color: #8b0000;
        }

        #reportsContent p {
            margin: 10px 0;
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #333;
        }

        #reportsContent p:last-child {
            border-bottom: none;
        }

        .report-link {
            background-color: #ffcc00;
            color: #333;
        }

        .report-link:hover {
            background-color: #e6b800;
        }

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
        <?php
        // Display success or error messages
        if (isset($_SESSION['success_message'])) {
            echo '<p style="color: green; font-weight: bold;">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<p style="color: red; font-weight: bold;">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
            unset($_SESSION['error_message']);
        }
        ?>
        <h2>Manage Users</h2>
        <?php if (empty($users)): ?>
            <div class="no-users">
                <p>No users found.</p>
            </div>
        <?php else: ?>
            <table>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Reports</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($users as $user): ?>
                    <tr data-user-id="<?php echo htmlspecialchars($user['id']); ?>">
                        <td><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                        <td class="<?php
                            if ($user['status'] === 'banned') {
                                echo 'status-banned';
                            } elseif ($user['status'] === 'deletion_requested') {
                                echo 'status-pending-deletion';
                            } elseif ($user['status'] === 'deletion_approved') {
                                echo 'status-approved-deletion';
                            } else {
                                echo 'status-active';
                            }
                        ?>">
                            <?php
                            if ($user['status'] === 'deletion_requested') {
                                echo "Pending Deletion";
                            } elseif ($user['status'] === 'deletion_approved') {
                                echo "Approved for Deletion";
                            } elseif ($user['status'] === 'banned') {
                                echo "Banned";
                            } else {
                                echo "Active";
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['created_at'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($user['report_count'] > 0): ?>
                                <a href="#" class="report-link button" data-user-id="<?php echo htmlspecialchars($user['id']); ?>">
                                    <?php echo $user['report_count']; ?> Report<?php echo $user['report_count'] > 1 ? 's' : ''; ?>
                                </a>
                            <?php else: ?>
                                No Reports
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['status'] !== 'banned' && $user['report_count'] > 0): ?>
                                <a href="#" class="button ban-button" data-action="ban">Ban</a>
                            <?php elseif ($user['status'] === 'banned'): ?>
                                <a href="#" class="button ban-button" data-action="unban">Unban</a>
                            <?php endif; ?>
                            
                            <?php if ($user['status'] === 'deletion_requested'): ?>
                                <a href="../api/admin/approve_deletion.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="button">Approve Deletion</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <!-- Reports Modal -->
        <div id="reportsModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">×</span>
                <h3>Reports for User</h3>
                <div id="reportsContent">
                    <!-- Report details will be populated here via JavaScript -->
                </div>
            </div>
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
            
            // Ban/Unban functionality
            const banButtons = document.querySelectorAll('.ban-button');
            banButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const userId = this.closest('tr').getAttribute('data-user-id');
                    const action = this.getAttribute('data-action');
                    const newAction = action === 'ban' ? 'unban' : 'ban';
                    const newText = action === 'ban' ? 'Unban' : 'Ban';

                    fetch(`../api/admin/ban_user.php?id=${userId}&action=${action}`, {
                        method: 'GET'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the button text and action
                            this.textContent = newText;
                            this.setAttribute('data-action', newAction);
                            // Update the status cell
                            const statusCell = this.closest('tr').querySelector('td:nth-child(4)');
                            statusCell.textContent = action === 'ban' ? 'Banned' : 'Active';
                            statusCell.className = action === 'ban' ? 'status-banned' : 'status-active';
                            // Update the reports cell
                            const reportsCell = this.closest('tr').querySelector('td:nth-child(6)');
                            if (action === 'ban') {
                                reportsCell.innerHTML = 'No Reports';
                                delete reportsData[userId]; // Remove reports from JavaScript data
                            }
                            // Handle redirect
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            }
                        } else {
                            console.error('Error:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error.message);
                    });
                });
            });
            
            // Pass reports data to JavaScript
            const reportsData = <?php echo json_encode($reports); ?>;

            // Reports Modal functionality
            const modal = document.getElementById('reportsModal');
            const closeModal = document.querySelector('.close-modal');
            const reportsContent = document.getElementById('reportsContent');

            document.querySelectorAll('.report-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const userId = this.getAttribute('data-user-id');
                    const userReports = reportsData[userId] || [];

                    if (userReports.length === 0) {
                        reportsContent.innerHTML = '<p>No reports found.</p>';
                    } else {
                        reportsContent.innerHTML = userReports.map(report => `
                            <p>
                                <strong>Type:</strong> ${report.report_type}<br>
                                <strong>Reported by:</strong> ${report.reporter_username}<br>
                                <strong>Reason:</strong> ${report.reason}<br>
                                ${report.report_type === 'Post' ? `<strong>Post Content:</strong> ${report.post_content.substring(0, 50)}${report.post_content.length > 50 ? '...' : ''}<br>` : ''}
                                <strong>Reported on:</strong> ${report.created_at}
                            </p>
                        `).join('');
                    }

                    modal.style.display = 'flex';
                });
            });

            // Close modal when clicking the close button
            closeModal.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            // Close modal when clicking outside the modal content
            window.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.style.display === 'flex') {
                    modal.style.display = 'none';
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