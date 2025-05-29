<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to get user_id
session_start();
if (!isset($_SESSION['user_id'])) {
  die("Error: User not loggedâ€¯logged in.");
}

// Include database connection
include '../config/database.php';

// Debug: Show received POST data
var_dump($_POST);

if (isset($_POST['content'])) {
  try {
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $content]);
    
    // Redirect to home page
    header("Location: ../client/index.php");
    exit();
  } catch (PDOException $e) {
    die("Post creation failed: " . $e->getMessage());
  }
} else {
  die("Error: Missing required field (content).");
}
?>