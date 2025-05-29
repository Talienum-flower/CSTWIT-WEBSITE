<?php
// File: client/profile.php
include 'includes/session.php';
include 'includes/header.php';
include 'includes/left_sidebar.php';
include 'includes/right_sidebar.php';
include '../config/database.php';

// Determine which profile to show
$profileId = isset($_GET['user_id']) && is_numeric($_GET['user_id'])
    ? (int)$_GET['user_id']
    : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null);

if ($profileId === null) {
    $_SESSION['error_message'] = "Invalid user ID.";
    header("Location: login.php");
    exit();
}

// Fetch the user's data
try {
    $stmt = $conn->prepare("SELECT id, username, profile_pic, name, bio FROM users WHERE id = ?");
    $stmt->execute([$profileId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error_message'] = "User not found.";
        header("Location: login.php");
        exit();
    }

    // Fetch the number of posts for this user
    $stmtPosts = $conn->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmtPosts->execute([$profileId]);
    $postCount = $stmtPosts->fetchColumn();

    // Fetch the number of followers
    $stmtFollowersCount = $conn->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
    $stmtFollowersCount->execute([$profileId]);
    $followersCount = $stmtFollowersCount->fetchColumn();

    // Fetch the number of following
    $stmtFollowingCount = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $stmtFollowingCount->execute([$profileId]);
    $followingCount = $stmtFollowingCount->fetchColumn();

    // Fetch the list of users this profile is following
    $stmtFollowing = $conn->prepare("SELECT followed_id, u.username AS followed_username 
                                     FROM follows f 
                                     JOIN users u ON f.followed_id = u.id 
                                     WHERE f.follower_id = ?");
    $stmtFollowing->execute([$profileId]);
    $followingList = $stmtFollowing->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the list of this profile's followers
    $stmtFollowers = $conn->prepare("SELECT follower_id, u.username AS follower_username 
                                     FROM follows f 
                                     JOIN users u ON f.follower_id = u.id 
                                     WHERE f.followed_id = ?");
    $stmtFollowers->execute([$profileId]);
    $followersList = $stmtFollowers->fetchAll(PDO::FETCH_ASSOC);

    // Check if the current user is following this profile (only if viewing another user's profile)
    $isFollowing = false;
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $profileId) {
        $stmtFollow = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmtFollow->execute([$_SESSION['user_id'], $profileId]);
        $isFollowing = $stmtFollow->fetchColumn() > 0;
    }

    // Check follow status for each user in followingList
    if (isset($_SESSION['user_id'])) {
        foreach ($followingList as &$followed) {
            $stmtCheckFollow = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followed_id = ?");
            $stmtCheckFollow->execute([$_SESSION['user_id'], $followed['followed_id']]);
            $followed['is_following'] = $stmtCheckFollow->fetchColumn() > 0;
        }
        unset($followed); // Unset reference
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching profile: " . htmlspecialchars($e->getMessage());
    header("Location: login.php");
    exit();
}
?>

<div class="main-content">
    <div class="profile-header">
        <h2>Profile</h2>
        <img src="../assets/uploads/<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>" class="profile-pic" alt="Profile Picture">
        <p><strong></strong> <?php echo htmlspecialchars($user['name'] ?? 'Not set'); ?></p>
        <p><strong>@</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong></strong> <?php echo htmlspecialchars($user['bio'] ?? 'No bio'); ?></p>

        <?php if ($profileId === (int)$_SESSION['user_id']): ?>
            <!-- Edit button for own profile -->
            <a href="#" class="button" data-toggle="modal" data-target="#editProfileModal">Edit Profile</a>
        <?php elseif (isset($_SESSION['user_id'])): ?>
            <!-- Follow/Unfollow button for other profiles -->
            <form action="../api/follow_user.php" method="POST" style="display:inline;">
                <input type="hidden" name="followed_id" value="<?php echo $profileId; ?>">
                <input type="hidden" name="action" value="<?php echo $isFollowing ? 'unfollow' : 'follow'; ?>">
                <button type="submit" class="btn btn-<?php echo $isFollowing ? 'danger' : 'primary'; ?>">
                    <?php echo $isFollowing ? 'Unfollow' : 'Follow'; ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Following/Followers Tabs -->
<div class="tab-buttons">
    <button class="tab active" data-target="following-list">Following (<?php echo $followingCount; ?>)</button>
    <button class="tab" data-target="followers-list">Followers (<?php echo $followersCount; ?>)</button>
</div>

<!-- Following List -->
<div class="follow-list active" id="following-list">
    <?php if (empty($followingList)): ?>
        <p>No users followed.</p>
    <?php else: ?>
        <?php foreach ($followingList as $followed): ?>
            <div class="follow-item">
                <a href="profile.php?user_id=<?php echo htmlspecialchars($followed['followed_id']); ?>">
                    <?php echo htmlspecialchars($followed['followed_username']); ?>
                </a>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $followed['followed_id']): ?>
                    <form action="../api/follow_user.php" method="POST" class="follow-form">
                        <input type="hidden" name="followed_id" value="<?php echo htmlspecialchars($followed['followed_id']); ?>">
                        <input type="hidden" name="action" value="<?php echo $followed['is_following'] ? 'unfollow' : 'follow'; ?>">
                        <button type="button" class="follow-button follow-toggle-btn <?php echo $followed['is_following'] ? 'following' : ''; ?>" 
                                data-user-id="<?php echo htmlspecialchars($followed['followed_id']); ?>" 
                                data-followed="<?php echo $followed['is_following'] ? 'true' : 'false'; ?>">
                            <?php echo $followed['is_following'] ? 'Following' : 'Follow'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Followers List -->
<div class="follow-list" id="followers-list">
    <?php if (empty($followersList)): ?>
        <p>No followers.</p>
    <?php else: ?>
        <?php foreach ($followersList as $follower): ?>
            <div class="follow-item">
                <a href="profile.php?user_id=<?php echo htmlspecialchars($follower['follower_id']); ?>">
                    <?php echo htmlspecialchars($follower['follower_username']); ?>
                </a>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $follower['follower_id']): ?>
                    <?php
                        $stmtCheckFollow = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followed_id = ?");
                        $stmtCheckFollow->execute([$_SESSION['user_id'], $follower['follower_id']]);
                        $isFollowingUser = $stmtCheckFollow->fetchColumn() > 0;
                    ?>
                    <form action="../api/follow_user.php" method="POST" class="follow-form">
                        <input type="hidden" name="followed_id" value="<?php echo htmlspecialchars($follower['follower_id']); ?>">
                        <input type="hidden" name="action" value="<?php echo $isFollowingUser ? 'unfollow' : 'follow'; ?>">
                        <button type="button" class="follow-button follow-toggle-btn <?php echo $isFollowingUser ? 'following' : ''; ?>" 
                                data-user-id="<?php echo htmlspecialchars($follower['follower_id']); ?>" 
                                data-followed="<?php echo $isFollowingUser ? 'true' : 'false'; ?>">
                            <?php echo $isFollowingUser ? 'Following' : 'Follow'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Edit Profile Modal -->
<div class="modal" id="editProfileModal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
            <button type="button" class="modal-close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="editProfileForm" action="../api/update_profile.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea class="form-control" id="bio" name="bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>
                <div class="form-group edit-profile-pic-container">
                    <label for="profile_pic_input">Profile Picture</label>
                    <div class="profile-pic-overlay">
                        <img src="../assets/uploads/<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>" class="edit-profile-pic" alt="Profile Picture">
                    </div>
                    <input type="file" id="profile_pic_input" name="profile_pic" accept="image/*" style="display: none;">
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
        <div class="modal-footer">
            <!-- Optional: Add additional buttons here if needed -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profile picture upload preview functionality
    const picOverlay = document.querySelector('.profile-pic-overlay');
    const fileInput = document.getElementById('profile_pic_input');
    const editProfilePic = document.querySelector('.edit-profile-pic');
    
    if (picOverlay && fileInput) {
        picOverlay.addEventListener('click', function() {
            fileInput.click();
        });
    }
    
    if (fileInput && editProfilePic) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    editProfilePic.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // Custom modal toggle
    const modal = document.getElementById('editProfileModal');
    const modalClose = document.querySelector('.modal-close');
    const openModalButton = document.querySelector('.button[data-target="#editProfileModal"]');

    if (openModalButton) {
        openModalButton.addEventListener('click', function(e) {
            e.preventDefault();
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
        });
    }

    if (modalClose) {
        modalClose.addEventListener('click', function() {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        });
    }

    // Close modal when clicking outside or pressing Escape
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }
    });

    // Tab switching logic for Following/Followers
    const tabButtons = document.querySelectorAll('.tab');
    const followLists = document.querySelectorAll('.follow-list');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            followLists.forEach(list => list.classList.remove('active'));

            this.classList.add('active');
            const targetId = this.getAttribute('data-target');
            const targetList = document.getElementById(targetId);
            if (targetList) {
                targetList.classList.add('active');
            }
        });
    });
    
    // Handle main profile follow/unfollow button
    const mainFollowForm = document.querySelector('form[action="../api/follow_user.php"]');
    if (mainFollowForm) {
        const mainFollowButton = mainFollowForm.querySelector('button[type="submit"]');
        if (mainFollowButton) {
            mainFollowButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                const followed_id = mainFollowForm.querySelector('input[name="followed_id"]').value;
                const action = mainFollowForm.querySelector('input[name="action"]').value;
                
                mainFollowButton.disabled = true;
                const originalText = mainFollowButton.textContent;
                mainFollowButton.textContent = 'Loading...';
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '../api/follow_user.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    mainFollowButton.disabled = false;
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                if (response.is_following) {
                                    mainFollowButton.textContent = 'Unfollow';
                                    mainFollowButton.className = 'btn btn-danger';
                                    mainFollowForm.querySelector('input[name="action"]').value = 'unfollow';
                                } else {
                                    mainFollowButton.textContent = 'Follow';
                                    mainFollowButton.className = 'btn btn-primary';
                                    mainFollowForm.querySelector('input[name="action"]').value = 'follow';
                                }
                                const followersTab = document.querySelector('.tab[data-target="followers-list"]');
                                if (followersTab) {
                                    followersTab.textContent = `Followers (${response.follower_count})`;
                                }
                            } else {
                                alert('Error: ' + response.message);
                                mainFollowButton.textContent = originalText;
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            alert('An error occurred while processing the request.');
                            mainFollowButton.textContent = originalText;
                        }
                    } else {
                        alert('Request failed with status: ' + xhr.status);
                        mainFollowButton.textContent = originalText;
                    }
                };
                
                xhr.onerror = function() {
                    mainFollowButton.disabled = false;
                    mainFollowButton.textContent = originalText;
                    alert('Request failed. Please try again.');
                };
                
                xhr.send(`followed_id=${encodeURIComponent(followed_id)}&action=${encodeURIComponent(action)}`);
            });
        }
    }
    
    // Handle follow/unfollow buttons in following and followers lists
    const followButtons = document.querySelectorAll('.follow-toggle-btn');
    
    followButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const userId = this.getAttribute('data-user-id');
            const isFollowed = this.getAttribute('data-followed') === 'true';
            const buttonElement = this;
            const form = buttonElement.closest('.follow-form');
            
            buttonElement.disabled = true;
            const originalText = buttonElement.textContent;
            buttonElement.textContent = 'Loading...';
            
            const action = isFollowed ? 'unfollow' : 'follow';
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../api/follow_user.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                buttonElement.disabled = false;
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            if (response.is_following) {
                                buttonElement.textContent = 'Following';
                                buttonElement.classList.add('following');
                                buttonElement.setAttribute('data-followed', 'true');
                                if (form) {
                                    const actionInput = form.querySelector('input[name="action"]');
                                    if (actionInput) actionInput.value = 'unfollow';
                                }
                            } else {
                                buttonElement.textContent = 'Follow';
                                buttonElement.classList.remove('following');
                                buttonElement.setAttribute('data-followed', 'false');
                                if (form) {
                                    const actionInput = form.querySelector('input[name="action"]');
                                    if (actionInput) actionInput.value = 'follow';
                                }
                            }
                        } else {
                            alert('Error: ' + response.message);
                            buttonElement.textContent = originalText;
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('An error occurred while processing the request.');
                        buttonElement.textContent = originalText;
                    }
                } else {
                    alert('Request failed with status: ' + xhr.status);
                    buttonElement.textContent = originalText;
                }
            };
            
            xhr.onerror = function() {
                buttonElement.disabled = false;
                buttonElement.textContent = originalText;
                alert('Request failed. Please try again.');
            };
            
            xhr.send(`followed_id=${encodeURIComponent(userId)}&action=${encodeURIComponent(action)}`);
        });
    });

    // Handle edit profile form submission via AJAX
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../api/update_profile.php', true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            document.getElementById('editProfileModal').classList.remove('show');
                            document.getElementById('editProfileModal').setAttribute('aria-hidden', 'true');
                            window.location.href = 'profile.php';
                        } else {
                            alert('Error: ' + response.message);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('An error occurred while processing the request.');
                    }
                } else {
                    alert('Request failed with status: ' + xhr.status);
                }
            };

            xhr.onerror = function() {
                alert('Request failed. Please try again.');
            };

            xhr.send(formData);
        });
    }
});
</script>