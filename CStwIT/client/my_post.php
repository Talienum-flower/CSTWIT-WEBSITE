<?php
// File: client/mypost.php
include 'includes/session.php';
include 'includes/header.php';
include 'includes/left_sidebar.php';
include 'includes/right_sidebar.php';
include '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please log in to view your posts.";
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION['user_id'];

try {
    // Fetch user details including profile picture
    $stmtUser = $conn->prepare("SELECT name, username, profile_pic FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error_message'] = "User not found.";
        header("Location: login.php");
        exit();
    }

    // Fetch the number of posts for this user
    $stmtPosts = $conn->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmtPosts->execute([$userId]);
    $postCount = $stmtPosts->fetchColumn();

    // Fetch all posts by this user
    $stmt = $conn->prepare("SELECT id, content, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch likes and comments count for each post
    $postsWithInteractions = [];
    foreach ($posts as $post) {
        // Count likes
        $stmtLikes = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
        $stmtLikes->execute([$post['id']]);
        $post['likes_count'] = $stmtLikes->fetchColumn();

        // Count comments
        $stmtComments = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
        $stmtComments->execute([$post['id']]);
        $post['comments_count'] = $stmtComments->fetchColumn();

        // Format the date to match the image (Jan 07, 2024)
        $date = new DateTime($post['created_at']);
        $post['formatted_date'] = $date->format('M d, Y');

        $postsWithInteractions[] = $post;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching posts: " . htmlspecialchars($e->getMessage());
    header("Location: login.php");
    exit();
}
?>

<div>
    <h2 class="my-post-header">My Post</h2>
    <div class="post-count"><?php echo $postCount; ?> post<?php echo $postCount != 1 ? 's' : ''; ?></div>

    <!-- Post List -->
    <div class="post-list">
        <?php if (empty($postsWithInteractions)): ?>
            <p>You have no posts yet.</p>
        <?php else: ?>
            <?php foreach ($postsWithInteractions as $post): ?>
                <div class="post-item">
                    <div class="post-header">
                        <div class="user-info">
                            <div class="user-avatar">
                                <img src="../assets/uploads/<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>" alt="Profile Picture" class="profile-pic">
                            </div>
                            <div class="name-username">
                                <p class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'Your Name'); ?></p>
                                <p class="user-username-date">
                                    @<?php echo htmlspecialchars($user['username'] ?? 'userme'); ?>
                                    <span class="post-date"><?php echo htmlspecialchars($post['formatted_date']); ?></span>
                                </p>
                            </div>
                        </div>
                        <div class="post-options">
                            <button class="options-btn" onclick="toggleOptionsMenu(<?php echo $post['id']; ?>)">⋯</button>
                            <div id="options-menu-<?php echo $post['id']; ?>" class="options-menu">
                                <a href="#" class="view-option" data-post-id="<?php echo htmlspecialchars($post['id']); ?>">View</a>
                                <a href="#" class="edit-option" data-post-id="<?php echo htmlspecialchars($post['id']); ?>">Edit</a>
                                <button onclick="deletePost(<?php echo $post['id']; ?>)" class="delete-btn">Delete</button>
                            </div>
                        </div>
                    </div>
                    <div class="post-content">
                        <?php 
                        $displayContent = htmlspecialchars($post['content']); 
                        echo $displayContent;
                        ?>
                    </div>
                  
                    <div class="post-actions">
                        <div class="interaction-stats">
                            <button class="like-button <?php echo (isset($_SESSION['liked_posts']) && in_array($post['id'], $_SESSION['liked_posts'])) ? 'liked' : ''; ?>" 
                                    onclick="likePost(<?php echo $post['id']; ?>, this)">
                                <svg class="like-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                                <span class="like-count"><?php echo $post['likes_count']; ?></span>
                            </button>
                            <button class="comment-button" onclick="viewPost(<?php echo $post['id']; ?>)">
                                <svg class="comment-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                                </svg>
                                <span class="comment-count"><?php echo $post['comments_count']; ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Post Modal -->
<div id="edit-modal" class="modal">
    <div class="modal-content">
        <button class="close-modal">×</button>
        <h3 class="modal-title">Edit Post</h3>
        <form id="edit-form" class="modal-form">
            <input type="hidden" id="edit-post-id" name="post_id">
            <textarea id="edit-content" name="content" placeholder="What's on your mind?"></textarea>
            <div class="button-container">
                <button type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to toggle the options menu
    window.toggleOptionsMenu = function(postId) {
        const menuId = 'options-menu-' + postId;
        const menu = document.getElementById(menuId);
        
        // Close all other open menus first
        document.querySelectorAll('.options-menu.show').forEach(openMenu => {
            if (openMenu.id !== menuId) {
                openMenu.classList.remove('show');
            }
        });
        
        // Toggle this menu
        menu.classList.toggle('show');
    };

    // Close menu when clicking elsewhere on the page
    document.addEventListener('click', function(event) {
        if (!event.target.matches('.options-btn')) {
            document.querySelectorAll('.options-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });

    // Edit Post
    document.querySelectorAll('.edit-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            
            // Find the post content
            const postElement = this.closest('.post-item');
            const postContent = postElement.querySelector('.post-content').innerText;
            
            // Populate the edit form
            document.getElementById('edit-post-id').value = postId;
            document.getElementById('edit-content').value = postContent;
            
            // Show the modal
            document.getElementById('edit-modal').style.display = 'flex';
        });
    });

    // Setup edit modal close functionality
    document.querySelector('#edit-modal .close-modal').addEventListener('click', function() {
        document.getElementById('edit-modal').style.display = 'none';
    });

    // Close edit modal when clicking outside
    window.addEventListener('click', function(e) {
        const editModal = document.getElementById('edit-modal');
        if (e.target === editModal) {
            editModal.style.display = 'none';
        }
    });

    // Handle edit form submission
    document.getElementById('edit-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const postId = document.getElementById('edit-post-id').value;
        const content = document.getElementById('edit-content').value.trim();
        
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
    window.deletePost = function(postId) {
        if (confirm('Are you sure you want to delete this post?')) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "../api/delete_post.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                window.location.reload();
                            } else {
                                alert("Failed to delete post: " + response.message);
                            }
                        } catch (e) {
                            console.error("Error parsing response: ", e);
                            alert("An error occurred while processing your request.");
                        }
                    } else {
                        alert("Failed to delete post: Server error " + xhr.status);
                    }
                }
            };
            xhr.send("post_id=" + postId);
        }
    };

    // View Post
    document.querySelectorAll('.view-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            window.location.href = `post.php?id=${postId}`;
        });
    });

    // Like Post (Placeholder - Needs API integration)
    window.likePost = function(postId, button) {
        const xhr = new XMLHttpRequest();
        const isLiked = button.classList.contains('liked');
        const action = isLiked ? "unlike" : "like";
        xhr.open("POST", "../api/like_post.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Update like count (correct selector for the current post)
                            const likeCountElement = button.querySelector('.like-count');
                            likeCountElement.innerText = response.likes;
                            button.classList.toggle('liked', response.isLiked);
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
    };

    // View Post (Redirect to post.php for comments)
    window.viewPost = function(postId) {
        window.location.href = `post.php?id=${postId}`;
    };
});
</script>

<?php ?>