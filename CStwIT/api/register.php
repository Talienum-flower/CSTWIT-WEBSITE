<?php
// Include database connection
include '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

if (isset($_POST['name'], $_POST['username'], $_POST['email'], $_POST['password'])) {
    try {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Validate inputs
        if (empty($name) || empty($username) || empty($email) || empty($_POST['password'])) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit();
        }

        // Check if email is a Gmail address
        if (!preg_match('/@gmail\.com$/', $email)) {
            echo json_encode(['success' => false, 'message' => 'Only Gmail addresses (@gmail.com) are allowed.', 'email' => $email]);
            exit();
        }

        // Check if email already exists
        $emailCheckStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $emailCheckStmt->execute([$email]);
        $emailExists = $emailCheckStmt->fetchColumn();

        if ($emailExists > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists.', 'email' => $email]);
            exit();
        }

        // Check if username already exists
        $usernameCheckStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $usernameCheckStmt->execute([$username]);
        $usernameExists = $usernameCheckStmt->fetchColumn();

        if ($usernameExists > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists.', 'username' => $username]);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role, profile_pic) VALUES (?, ?, ?, ?, 'user', 'default.jpg')");
        $stmt->execute([$name, $username, $email, $password]);

        echo json_encode(['success' => true, 'message' => 'Registration successful!', 'redirect' => '../client/login.php']);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}
?>