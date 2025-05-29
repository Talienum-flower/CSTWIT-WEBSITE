<?php
// File: api/update_profile.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/session.php';
include '../config/database.php';

// Function to return JSON response
function returnJson($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    returnJson(false, "User not logged in");
}

$user_id = $_SESSION['user_id'];

// Fetch the logged-in user's data
try {
    $stmt = $conn->prepare("SELECT username, email, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        returnJson(false, "User not found");
    }
} catch (PDOException $e) {
    returnJson(false, "Error fetching user: " . $e->getMessage());
}

// Debug: Log the incoming data
error_log("POST Data: " . print_r($_POST, true));
error_log("FILES Data: " . print_r($_FILES, true));

$username = $_POST['username'] ?? '';
$name = $_POST['name'] ?? '';
$bio = $_POST['bio'] ?? '';
$profile_pic = $user['profile_pic'] ?? 'default.jpg'; // Default to current or default.jpg

// Validate username: check for emptiness and uniqueness
if (empty($username)) {
    returnJson(false, "Username cannot be empty");
}

// Check if the new username is already taken by another user
try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        returnJson(false, "Username already taken");
    }
} catch (PDOException $e) {
    returnJson(false, "Error checking username: " . $e->getMessage());
}

// Handle profile picture upload
$file_data = $_FILES['profile_pic'] ?? null;
if ($file_data && $file_data['error'] === UPLOAD_ERR_OK && !empty($file_data['name'])) {
    $upload_dir = '../assets/uploads/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_file_size = 5 * 1024 * 1024; // 5MB
    $file_type = mime_content_type($file_data['tmp_name']);
    $file_size = $file_data['size'];

    // Validate file
    if (!in_array($file_type, $allowed_types)) {
        returnJson(false, "Invalid file type. Only JPEG, PNG, and GIF are allowed.");
    }
    if ($file_size > $max_file_size) {
        returnJson(false, "File size exceeds 5MB limit.");
    }

    // Generate unique filename
    $extension = pathinfo($file_data['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('profile_') . '.' . $extension;
    $target_path = $upload_dir . $new_filename;

    // Move uploaded file
    if (move_uploaded_file($file_data['tmp_name'], $target_path)) {
        // Delete old profile picture if it exists and is not default
        if ($profile_pic && $profile_pic !== 'default.jpg' && file_exists($upload_dir . $profile_pic)) {
            unlink($upload_dir . $profile_pic);
        }
        $profile_pic = $new_filename;
    } else {
        returnJson(false, "Failed to upload profile picture");
    }
} elseif ($file_data && $file_data['error'] !== UPLOAD_ERR_NO_FILE) {
    // Handle other upload errors (e.g., file too large, partial upload)
    returnJson(false, "File upload error: " . $file_data['error']);
}

try {
    $sql = "UPDATE users SET username = ?, email = ?, name = ?, bio = ?, profile_pic = ? WHERE id = ?";
    $params = [$username, $user['email'], $name, $bio, $profile_pic, $user_id];

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // Update session username to reflect the change
    $_SESSION['username'] = $username;

    returnJson(true, "Profile updated successfully", [
        'username' => $username,
        'name' => $name,
        'bio' => $bio,
        'profile_pic' => $profile_pic
    ]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    returnJson(false, "Profile update failed: " . $e->getMessage());
}
?>