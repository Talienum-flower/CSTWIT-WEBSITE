<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
if (!file_exists('../config/database.php')) {
    die(json_encode([
        'success' => false,
        'message' => 'Configuration error: Database configuration file not found.'
    ]));
}
include '../config/database.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Check if form data is received
if (!isset($_POST['username']) || !isset($_POST['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Username or password not provided.'
    ]);
    exit;
}

$username = trim($_POST['username']); // Trim to remove any whitespace
$password = $_POST['password'];
$is_admin = isset($_POST['is_admin']) ? 1 : 0;

// Debug: Log the input values
error_log("Username: $username, Is Admin: $is_admin");

try {
    // Prepare the query with case-insensitive comparison
    $stmt = $conn->prepare("SELECT id, username, password, role, status, ban_version FROM users WHERE LOWER(username) = LOWER(?) AND LOWER(role) = LOWER(?)");
    $stmt->execute([$username, $is_admin ? 'admin' : 'user']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user is found
    if (!$user) {
        error_log("User not found for username: $username, role: " . ($is_admin ? 'admin' : 'user'));
        echo json_encode([
            'success' => false,
            'message' => 'Invalid login credentials: User not found.'
        ]);
        exit;
    }

    // Check user status for non-admin accounts
    if (!$is_admin) {
        if ($user['status'] === 'banned') {
            // Fetch ban reasons from reports
            $reportStmt = $conn->prepare("
                SELECT reason 
                FROM reports 
                WHERE reported_user_id = ? AND status = 'pending'
                LIMIT 1
            ");
            $reportStmt->execute([$user['id']]);
            $report = $reportStmt->fetch(PDO::FETCH_ASSOC);
            $reason = $report ? $report['reason'] : 'unspecified reasons';

            echo json_encode([
                'success' => false,
                'message' => "Your account has been banned by the admin due violate our community guidelines."
            ]);
            exit;
        }
        if ($user['status'] === 'deletion_requested') {
            echo json_encode([
                'success' => false,
                'message' => 'Your account has a pending deletion request and cannot be accessed.'
            ]);
            exit;
        }
        if ($user['status'] === 'deletion_approved') {
            echo json_encode([
                'success' => false,
                'message' => 'Your account has been approved for deletion and cannot be accessed.'
            ]);
            exit;
        }
    }

    // Debug: Log the stored password hash
    error_log("Stored hash for $username: " . $user['password']);

    // Verify password
    if (password_verify($password, $user['password'])) {
        if ($is_admin) {
            $_SESSION['admin_id'] = $user['id'];
            echo json_encode([
                'success' => true,
                'redirect' => '../admin/dashboard.php'
            ]);
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['ban_version'] = $user['ban_version']; // Store ban version in session
            echo json_encode([
                'success' => true,
                'redirect' => '../client/index.php'
            ]);
        }
    } else {
        error_log("Password verification failed for $username");
        echo json_encode([
            'success' => false,
            'message' => 'Invalid login credentials: Incorrect password.'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . htmlspecialchars($e->getMessage())
    ]);
    exit;
}
?>