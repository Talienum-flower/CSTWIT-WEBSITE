<?php
// Include database connection
if (!file_exists('../../config/database.php')) {
    die(json_encode([
        'success' => false,
        'message' => 'Configuration error: Database configuration file not found.'
    ]));
}
include '../../config/database.php';

// Include admin session check with corrected path
if (!file_exists('session.php')) {
    die(json_encode([
        'success' => false,
        'message' => 'Configuration error: Session file not found.'
    ]));
}
include 'session.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the user is logged in and check their status
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    
    try {
        // First check if the account exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([strtolower($username)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If user exists, check if they're banned
        if ($user && $user['status'] === 'banned') {
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
                'message' => "Your account has been banned by the admin due to reports from other users (Reason: $reason)."
            ]);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . htmlspecialchars($e->getMessage())
        ]);
        exit;
    }
}

// Continue with the original login.php code
// This file will be included in the existing login.php
?>