<?php
include 'includes/session.php';
include 'includes/header.php';
include 'includes/left_sidebar.php';
include 'includes/right_sidebar.php';
include '../config/database.php';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$new_password, $user_id]);
}

// Handle account deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $user_id = $_SESSION['user_id'];
    $reason = $_POST['delete_reason'];
    if ($_POST['delete_reason'] === 'Other' && !empty($_POST['delete_reason_other'])) {
        $reason = $_POST['delete_reason_other'];
    }

    // Mark user's posts as deleted instead of removing them
    $conn->query("UPDATE posts SET status = 'deleted' WHERE user_id = $user_id");

    // Mark the user as requesting deletion
    $stmt = $conn->prepare("UPDATE users SET status = 'deletion_requested', deletion_reason = ? WHERE id = ?");
    $stmt->execute([$reason, $user_id]);

    // Destroy the session to log the user out
    session_destroy();

    // Redirect to login page with a success message
    header("Location: login.php?message=" . urlencode("Your account deletion request has been submitted and is pending admin approval."));
    exit();
}

// Handle report account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_account'])) {
    $reported_user_id = $_POST['report_user'];
    $reason = $_POST['report_reason'];
    if ($_POST['report_reason'] === 'Other' && !empty($_POST['report_reason_other'])) {
        $reason = $_POST['report_reason_other'];
    }
    $reporter_id = $_SESSION['user_id'];

    // Insert into reports table instead of notifications
    $stmt = $conn->prepare("INSERT INTO reports (user_id, reported_user_id, reason, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$reporter_id, $reported_user_id, $reason]);
}

// Fetch non-admin users for the report dropdown
$users_result = $conn->query("SELECT id, username FROM users WHERE role != 'admin'");
$users = [];
while ($row = $users_result->fetch(PDO::FETCH_ASSOC)) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    
</head>
<body>
    <div class="set-container">
        <h2 style = color:black;>Manage your account</h2>

        <div class="section">
            <h2>Password and Security</h2>
            <p>Manage your login details and keep your account safe.</p>
            <div class="option" onclick="openModal('changePasswordModal')">Change password</div>
        </div>
        <br>

        <div class="section">
            <h2>Report or delete account</h2>
            <p>Manage your account settings by deactivating it temporarily or deleting it permanently, with all your data removed and unable to be recovered.</p>
            <div class="option" onclick="openModal('reportAccountModal')">Report account</div>
            <div class="option" onclick="openModal('deleteAccountModal')">Delete account</div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('changePasswordModal')">×</span>
            <h3>Change Password</h3>
            <form method="POST">
                <input type="password" name="new_password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit" name="change_password">Submit</button>
            </form>
        </div>
    </div>

    <!-- Report Account Modal -->
    <div id="reportAccountModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('reportAccountModal')">×</span>
            <h3>Report Account</h3>
            <?php if (empty($users)): ?>
                <p>No users available to report.</p>
            <?php else: ?>
                <form method="POST">
                    <select name="report_user" required>
                        <option value="">Select user to report</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="report_reason" id="report_reason" onchange="document.getElementById('report_reason_other').style.display = this.value === 'Other' ? 'block' : 'none'" required>
                        <option value="">Select reason</option>
                        <option value="Inappropriate Content">Inappropriate Content</option>
                        <option value="Spam">Spam</option>
                        <option value="Harassment">Harassment</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" name="report_reason_other" id="report_reason_other" class="custom-reason" placeholder="Please specify your reason">
                    <button type="submit" name="report_account">Submit Report</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div id="deleteAccountModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('deleteAccountModal')">×</span>
            <h3>Delete Account</h3>
            <p>Request to permanently delete your account. This action will be reviewed by an admin and cannot be undone once approved.</p>
            <form method="POST">
                <select name="delete_reason" id="delete_reason" onchange="document.getElementById('delete_reason_other').style.display = this.value === 'Other' ? 'block' : 'none'" required>
                    <option value="">Select reason for deletion</option>
                    <option value="Privacy Concerns">Privacy Concerns</option>
                    <option value="Not Using Anymore">Not Using Anymore</option>
                    <option value="Technical Issues">Technical Issues</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" name="delete_reason_other" id="delete_reason_other" class="custom-reason" placeholder="Please specify your reason">
                <button type="submit" name="delete_account">Request Deletion</button>
            </form>
        </div>
    </div>

    <script>
        // Function to open a modal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        // Function to close a modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside the modal content
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };

        // Close modal when pressing the Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>