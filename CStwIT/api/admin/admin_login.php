<?php
// Include database and handle admin login
include '../../config/database.php';

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

// Debug: Log the input values
error_log("Admin login attempt - Username: $username");

// Prepare the query to fetch admin user with case-insensitive comparison
$stmt = $conn->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND LOWER(role) = 'admin'");
$stmt->execute([$username]);
$user = $stmt->fetch();

// Check if admin user is found
if (!$user) {
    error_log("Admin not found for username: $username");
    echo json_encode([
        'success' => false,
        'message' => 'Invalid login credentials: Admin not found.'
    ]);
    exit;
}

// Debug: Log the stored password hash
error_log("Stored hash for $username: " . $user['password']);

// Verify password
if (password_verify($password, $user['password'])) {
    session_start();
    $_SESSION['admin_id'] = $user['id'];
    echo json_encode([
        'success' => true,
        'redirect' => '../admin/dashboard.php'
    ]);
} else {
    error_log("Password verification failed for admin $username");
    echo json_encode([
        'success' => false,
        'message' => 'Invalid login credentials: Incorrect password.'
    ]);
}
?>