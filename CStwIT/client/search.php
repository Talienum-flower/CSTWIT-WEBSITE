<?php
session_start();

// Include database connection
include '../config/database.php';

// Check if query parameter exists
if (isset($_GET['query']) && !empty($_GET['query'])) {
    $raw_query = trim($_GET['query']);
    $query = '%' . $raw_query . '%';
    error_log("Search query: " . $raw_query);

    try {
        // Search for users by username, name, or email
        $stmt = $conn->prepare("
            SELECT id, username, profile_pic 
            FROM users 
            WHERE username LIKE ? OR name LIKE ? OR email LIKE ?
            LIMIT 1
        ");
        $stmt->execute([$query, $query, $query]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Return JSON with user data for AJAX
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'user' => $user]);
            exit;
        } else {
            // No user found, return error
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No users found matching "' . htmlspecialchars($raw_query) . '"']);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'An error occurred during search']);
        exit;
    }
} else {
    // No query provided
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please enter a search query']);
    exit;
}
?>