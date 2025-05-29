<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
if (!file_exists('../config/database.php')) {
    die('Configuration error: Database configuration file not found.');
}
include '../config/database.php';

if (isset($_SESSION['user_id'])) {
    try {
        // Check user status and ban_version
        $stmt = $conn->prepare("SELECT status, ban_version FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // User not found, log out
            session_unset();
            session_destroy();
            header("Location: /CStwIT/client/login.php");
            exit;
        }

        // Check if ban_version matches
        $sessionBanVersion = isset($_SESSION['ban_version']) ? $_SESSION['ban_version'] : -1;
        if ($sessionBanVersion !== $user['ban_version']) {
            // Ban version mismatch, user has been banned or unbanned
            session_unset();
            session_destroy();
            $message = $user['status'] === 'banned' ? "Your account has been banned by the admin." : "Your session has expired. Please log in again.";
            header("Location: /CStwIT/client/login.php?message=" . urlencode($message));
            exit;
        }

        // Check user status
        if ($user['status'] === 'banned') {
            session_unset();
            session_destroy();
            header("Location: /CStwIT/client/login.php?message=" . urlencode("Your account has been banned by the admin."));
            exit;
        }
    } catch (PDOException $e) {
        error_log("Session check error: " . $e->getMessage());
        session_unset();
        session_destroy();
        header("Location: /CStwIT/client/login.php?message=" . urlencode("An error occurred. Please log in again."));
        exit;
    }
}
?>