<?php
// Check if session is already started before attempting to start it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine if this is an auth page (login page)
$current_file = basename($_SERVER['PHP_SELF']);
$is_auth_page = ($current_file == 'login.php');

// Check if admin is logged in, but skip this check for the login page
if (!$is_auth_page && !isset($_SESSION['admin_id'])) {
    $_SESSION['error_message'] = "Please log in as an admin to access the dashboard.";
    header("Location: /CStwIT/admin/login.php");
    exit();
}

// If not on the login page, verify the admin's details
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
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error fetching admin details: " . htmlspecialchars($e->getMessage());
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
  <title>CStwIT Admin</title>
  
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
  
  <div class="container">
    <!-- Content will be added here -->
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
    
    // Close menu when clicking outside
    overlay.addEventListener('click', function() {
      nav.classList.remove('active');
      overlay.classList.remove('active');
    });

    document.addEventListener('DOMContentLoaded', function() {
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
  
  // Sidebar toggle for mobile
  const sidebarToggle = document.querySelector('.sidebar-toggle');
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      document.body.classList.toggle('sidebar-active');
      
      // If you have a separate sidebar element
      const sidebar = document.querySelector('.sidebar'); // Adjust selector as needed
      if (sidebar) {
        sidebar.classList.toggle('active');
      }
    });
  }
  
  // Close sidebar when clicking outside of it (for mobile)
  document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar'); // Adjust selector as needed
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    
    if (sidebar && document.body.classList.contains('sidebar-active') && 
        !sidebar.contains(event.target) && 
        !sidebarToggle.contains(event.target)) {
      document.body.classList.remove('sidebar-active');
      sidebar.classList.remove('active');
    }
  });
  
  // Responsive resizing
  function handleResize() {
    if (window.innerWidth > 768) {
      document.body.classList.remove('sidebar-active');
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