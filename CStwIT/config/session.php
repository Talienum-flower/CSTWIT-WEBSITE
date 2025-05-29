<?php
// Enable output buffering to prevent header issues
ob_start();
// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: /CStwIT/client/login.php");
  exit();
}
// Flush output buffer
ob_flush();
?>