<?php
// File: client/post.php
include_once 'includes/session.php';
include_once 'includes/header.php';
include 'includes/left_sidebar.php';
include 'includes/right_sidebar.php';

// Include database configuration
include_once '../config/database.php';

// Check if post ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid post ID.");
}

$post_id = (int)$_GET['id'];
$post = null;
$comments = [];
$liked_by = [];

try {
    // Fetch post and owner details
    $stmt = $conn->prepare("
        SELECT p.content, p.created_at, p.likes, p.user_id, u.username, u.profile_pic
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Fetched post: " . print_r($post, true));

    if (!$post) {
        die("Post not found.");
    }

    // Fetch comments
    $stmt = $conn->prepare("
        SELECT c.comment, c.created_at, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Fetched comments: " . print_r($comments, true));

    // Fetch users who liked the post (include user_id)
    $stmt = $conn->prepare("
        SELECT u.id, u.username
        FROM likes l
        JOIN users u ON l.user_id = u.id
        WHERE l.post_id = ?
    ");
    $stmt->execute([$post_id]);
    $liked_by = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Fetched liked_by: " . print_r($liked_by, true));
} catch (PDOException $e) {
    error_log("Error fetching post details: " . $e->getMessage());
    die("An error occurred while loading the post.");
}

// Format the post date to match the image (e.g., Jan 07, 2024)
$date = new DateTime($post['created_at']);
$formatted_date = $date->format('M d, Y');

// Process post content to highlight hashtags
$content = htmlspecialchars($post['content']);
$hashtags = [];
preg_match_all('/#(\w+)/', $content, $matches);
if (!empty($matches[0])) {
    $hashtags = $matches[0];
    foreach ($matches[0] as $hashtag) {
        $content = str_replace($hashtag, "<span class=\"hashtag\">$hashtag</span>", $content);
    }
}

// Check if the current user has liked the post
$is_liked = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM likes 
        WHERE post_id = ? AND user_id = ?
    ");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    $is_liked = $stmt->fetchColumn() > 0;
}

// Determine if the post belongs to the logged-in user
$is_own_post = isset($_SESSION['user_id']) && isset($post['user_id']) && $_SESSION['user_id'] == $post['user_id'];
?>

<div class="single-post-container">
    <?php if ($post): ?>
        <div class="single-post">
            <div class="single-post-header">
                <div class="single-post-user-info">
                    <img src="../assets/uploads/<?php echo htmlspecialchars($post['profile_pic'] ?? 'default.jpg'); ?>" class="single-post-profile-pic" alt="Profile">
                    <div class="single-post-name-username">
                        <p class="single-post-user-name">@<?php echo htmlspecialchars($post['username']); ?></p>
                        <p class="single-post-user-username-date">
                            <span class="single-post-date"><?php echo htmlspecialchars($formatted_date); ?></span>
                        </p>
                    </div>
                </div>
                <!-- Post Options -->
                <div class="single-post-options">
                    <span class="single-post-three-dots"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="2"/><circle cx="6" cy="12" r="2"/><circle cx="18" cy="12" r="2"/></svg></span>
                    <div class="single-post-options-menu">
                        <?php if ($is_own_post): ?>
                            <a href="#" class="single-post-edit-option" data-post-id="<?php echo $post_id; ?>">Edit</a>
                            <a href="#" class="single-post-delete-option" data-post-id="<?php echo $post_id; ?>">Delete</a>
                        <?php else: ?>
                            <a href="#" class="single-post-report-option" data-post-id="<?php echo $post_id; ?>">Report</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Rest of the post content -->
            <div class="single-post-content">
                <?php echo $content; ?>
            </div>
            <?php if (!empty($hashtags)): ?>
                <div class="single-post-hashtags">
                    <?php echo implode(' ', $hashtags); ?>
                </div>
            <?php endif; ?>
            <div class="single-post-actions">
                <input type="text" class="single-post-comment-input" id="single-post-comment-input-<?php echo $post_id; ?>" placeholder="Comment" onkeypress="handleCommentKeyPress(event, <?php echo $post_id; ?>)">
                <div class="single-post-interaction-stats">
                    <button class="single-post-like-button <?php echo $is_liked ? 'liked' : ''; ?>" onclick="toggleLikesDropdown(<?php echo $post_id; ?>, this)">
                        <svg class="single-post-like-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        <span class="single-post-like-count" id="single-post-likes-<?php echo $post_id; ?>"><?php echo htmlspecialchars($post['likes']); ?></span>
                    </button>
                    <div class="single-post-likes-dropdown" id="single-post-likes-dropdown-<?php echo $post_id; ?>">
                        <?php if (!empty($liked_by)): ?>
                            <?php foreach ($liked_by as $liker): ?>
                                <a href="profile.php?user_id=<?php echo urlencode($liker['id']); ?>" class="single-post-liker"><?php echo htmlspecialchars($liker['username']); ?></a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="single-post-liker">No likes yet.</div>
                        <?php endif; ?>
                    </div>
                    <button class="single-post-comment-button" onclick="toggleComments(<?php echo $post_id; ?>)">
                        <svg class="single-post-comment-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                        <span class="single-post-comment-count"><?php echo count($comments); ?></span>
                    </button>
                </div>
            </div>
            <div class="single-post-comment-section" id="single-post-comment-section-<?php echo $post_id; ?>" style="display: <?php echo !empty($comments) ? 'block' : 'none'; ?>;">
                <?php if (!empty($comments)): ?>
                    <div class="single-post-comments-section">
                        <?php foreach ($comments as $comment): ?>
                            <?php
                            $comment_date = new DateTime($comment['created_at']);
                            $formatted_comment_date = $comment_date->format('M d, Y');
                            ?>
                            <div class="single-post-comment">
                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                <span class="single-post-date"><?php echo htmlspecialchars($formatted_comment_date); ?></span>
                                <div class="single-post-comment-text">
                                    <?php echo htmlspecialchars($comment['comment']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <p>Post not found.</p>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="single-post-edit-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal">×</span>
        <h3>Edit Post</h3>
        <form id="single-post-edit-form">
            <input type="hidden" id="single-post-edit-post-id" name="post_id">
            <textarea id="single-post-edit-content" name="content" rows="4" required></textarea>
            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

<!-- Report Modal (only include if the post is not the user's own) -->
<?php if (!$is_own_post): ?>
<div id="single-post-report-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal">×</span>
        <h3>Report Post</h3>
        <form id="single-post-report-form">
            <input type="hidden" id="single-post-report-post-id" name="post_id">
            <div class="single-post-report-reason">
                <input type="radio" name="reason" value="Inappropriate Content" id="single-post-reason-inappropriate" required>
                <label for="single-post-reason-inappropriate">Inappropriate Content</label>
            </div>
            <div class="single-post-report-reason">
                <input type="radio" name="reason" value="Spam" id="single-post-reason-spam">
                <label for="single-post-reason-spam">Spam</label>
            </div>
            <div class="single-post-report-reason">
                <input type="radio" name="reason" value="Harassment" id="single-post-reason-harassment">
                <label for="single-post-reason-harassment">Harassment</label>
            </div>
            <div class="single-post-report-reason">
                <input type="radio" name="reason" value="Other" id="single-post-reason-other">
                <label for="single-post-reason-other">Other</label>
            </div>
            <div id="single-post-other-reason-container" style="display: none;">
                <textarea id="single-post-other-reason" name="other-reason" rows="4" placeholder="Specify reason..."></textarea>
            </div>
            <button type="submit">Submit Report</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// Toggle Likes Dropdown and Handle Like/Unlike
function toggleLikesDropdown(postId, button) {
    const dropdown = document.getElementById('single-post-likes-dropdown-' + postId);
    
    // Close all other open dropdowns
    document.querySelectorAll('.single-post-likes-dropdown.show').forEach(openDropdown => {
        if (openDropdown !== dropdown) {
            openDropdown.classList.remove('show');
        }
    });

    // Toggle this dropdown
    dropdown.classList.toggle('show');

    // Handle like/unlike action
    const xhr = new XMLHttpRequest();
    const isLiked = button.classList.contains('liked');
    const action = isLiked ? "unlike" : "like";
    xhr.open("POST", "../api/like_post.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById("single-post-likes-" + postId).innerText = response.likes;
                        button.classList.toggle('liked', response.isLiked);

                        // Update the dropdown content dynamically
                        const dropdownContent = document.getElementById('single-post-likes-dropdown-' + postId);
                        if (response.liked_by && response.liked_by.length > 0) {
                            dropdownContent.innerHTML = response.liked_by.map(user => 
                                `<a href="profile.php?user_id=${encodeURIComponent(user.id)}" class="single-post-liker">${user.username}</a>`
                            ).join('');
                        } else {
                            dropdownContent.innerHTML = '<div class="single-post-liker">No likes yet.</div>';
                        }
                    } else {
                        console.error("Failed to " + action + " the post: " + response.message);
                    }
                } catch (e) {
                    console.error("Error parsing response: ", e);
                }
            } else {
                console.error("Failed to " + action + " the post: Server error " + xhr.status);
            }
        }
    };
    xhr.send("post_id=" + postId + "&action=" + action);
}

// Toggle Comments
function toggleComments(postId) {
    const commentSection = document.getElementById('single-post-comment-section-' + postId);
    commentSection.style.display = commentSection.style.display === 'none' ? 'block' : 'none';
}

// Submit Comment
function submitComment(postId) {
    const input = document.getElementById('single-post-comment-input-' + postId);
    const comment = input.value.trim();
    if (comment.length === 0) return;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "../api/comment_post.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        window.location.reload();
                    } else {
                        console.error("Failed to add comment: " + response.message);
                    }
                } catch (e) {
                    console.error("Error parsing response: ", e);
                }
            } else {
                console.error("Failed to add comment: Server error " + xhr.status);
            }
        }
    };
    xhr.send("post_id=" + postId + "&comment=" + encodeURIComponent(comment));
    input.value = '';
}

// Handle Comment Key Press
function handleCommentKeyPress(event, postId) {
    if (event.key === 'Enter') {
        event.preventDefault();
        submitComment(postId);
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.single-post-like-button') && !event.target.closest('.single-post-likes-dropdown')) {
        document.querySelectorAll('.single-post-likes-dropdown.show').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

// Post Options and Modal Handling
document.addEventListener('DOMContentLoaded', function() {
    // Post Options Menu
    const threeDots = document.querySelectorAll('.single-post-three-dots');
    threeDots.forEach(dots => {
        dots.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            document.querySelectorAll('.single-post-options-menu').forEach(m => {
                if (m !== menu) m.classList.remove('show');
            });
            menu.classList.toggle('show');
        });
    });

    // Close Menus on Click Outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.single-post-options')) {
            document.querySelectorAll('.single-post-options-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });

    // Edit Post
    document.querySelectorAll('.single-post-edit-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            const postElement = this.closest('.single-post');
            const postContent = postElement.querySelector('.single-post-content').innerText;
            document.getElementById('single-post-edit-post-id').value = postId;
            document.getElementById('single-post-edit-content').value = postContent;
            document.getElementById('single-post-edit-modal').style.display = 'flex';
        });
    });

    // Close Edit Modal
    document.querySelector('#single-post-edit-modal .close-modal').addEventListener('click', function() {
        document.getElementById('single-post-edit-modal').style.display = 'none';
    });

    // Close Edit Modal on Outside Click
    window.addEventListener('click', function(e) {
        const editModal = document.getElementById('single-post-edit-modal');
        if (e.target === editModal) {
            editModal.style.display = 'none';
        }
    });

    // Handle Edit Form Submission
    document.getElementById('single-post-edit-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const postId = document.getElementById('single-post-edit-post-id').value;
        const content = document.getElementById('single-post-edit-content').value.trim();
        if (!content) {
            alert('Post content cannot be empty.');
            return;
        }
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "../api/edit_post.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            window.location.reload();
                        } else {
                            alert("Failed to update post: " + response.message);
                        }
                    } catch (e) {
                        console.error("Error parsing response: ", e);
                        alert("An error occurred while processing your request.");
                    }
                } else {
                    alert("Failed to update post: Server error " + xhr.status);
                }
            }
        };
        xhr.send("post_id=" + postId + "&content=" + encodeURIComponent(content));
    });

    // Delete Post
    document.querySelectorAll('.single-post-delete-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            if (confirm('Are you sure you want to delete this post?')) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "../api/delete_post.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    alert('Post deleted successfully.');
                                    window.location.href = 'index.php'; // Redirect to home page
                                } else {
                                    alert("Failed to delete post: " + response.message);
                                }
                            } catch (e) {
                                console.error("Error parsing response: ", e);
                            }
                        } else {
                            alert("Failed to delete post: Server error " + xhr.status);
                        }
                    }
                };
                xhr.send("post_id=" + postId);
            }
        });
    });

    <?php if (!$is_own_post): ?>
    // Report Post
    const reportModal = document.getElementById('single-post-report-modal');
    const closeModal = reportModal.querySelector('.close-modal');
    const reportForm = document.getElementById('single-post-report-form');
    const otherReasonContainer = document.getElementById('single-post-other-reason-container');
    const otherReasonInput = document.getElementById('single-post-other-reason');
    const reasonRadios = document.querySelectorAll('input[name="reason"]');

    document.querySelectorAll('.single-post-report-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            document.getElementById('single-post-report-post-id').value = postId;
            reportModal.style.display = 'flex';
        });
    });

    closeModal.addEventListener('click', function() {
        reportModal.style.display = 'none';
        reportForm.reset();
        otherReasonContainer.style.display = 'none';
    });

    window.addEventListener('click', function(e) {
        if (e.target === reportModal) {
            reportModal.style.display = 'none';
            reportForm.reset();
            otherReasonContainer.style.display = 'none';
        }
    });

    reasonRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            otherReasonContainer.style.display = this.value === 'Other' ? 'block' : 'none';
            if (this.value !== 'Other') {
                otherReasonInput.value = '';
            }
        });
    });

    reportForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const postId = document.getElementById('single-post-report-post-id').value;
        const selectedReason = document.querySelector('input[name="reason"]:checked').value;
        const reason = selectedReason === 'Other' ? otherReasonInput.value.trim() : selectedReason;

        if (selectedReason === 'Other' && !reason) {
            alert('Please specify a reason for the report.');
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "../api/report_post.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    alert("Post has been reported. Thank you for helping us maintain community standards.");
                    reportModal.style.display = 'none';
                    reportForm.reset();
                    otherReasonContainer.style.display = 'none';
                } else {
                    alert("Failed to report post. Please try again later.");
                }
            }
        };
        xhr.send(`post_id=${postId}&reason=${encodeURIComponent(reason)}`);
    });
    <?php endif; ?>
});
</script>

<?php ?>