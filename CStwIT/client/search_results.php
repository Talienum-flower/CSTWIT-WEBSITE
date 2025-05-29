<?php
// File: search_results.php
include 'includes/session.php';
include 'includes/header.php';
include 'includes/left_sidebar.php';
include 'includes/right_sidebar.php';
include '../config/database.php';

// Check if query parameter exists
if (!isset($_GET['query']) || empty(trim($_GET['query']))) {
    $_SESSION['error_message'] = "Please enter a search query.";
    header("Location: index.php");
    exit();
}

$raw_query = trim($_GET['query']);
$query = '%' . $raw_query . '%';
$users = [];
$posts = [];
$search_type = 'user'; // Default to user search

try {
    // Step 1: Search for users by username or name (excluding email)
    $stmtUsers = $conn->prepare("
        SELECT id, username, profile_pic, name, bio 
        FROM users 
        WHERE username LIKE ? OR name LIKE ?
    ");
    $stmtUsers->execute([$query, $query]);
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($users)) {
        // Step 2a: If users are found, fetch their posts
        $search_type = 'user';
        $stmtPosts = $conn->prepare("
            SELECT p.id, p.user_id, p.content, p.likes, p.created_at, u.username, u.profile_pic 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE u.username LIKE ? OR u.name LIKE ?
            ORDER BY p.created_at DESC
        ");
        $stmtPosts->execute([$query, $query]);
        $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Step 2b: If no users are found, search for posts by content
        $search_type = 'post';
        $stmtPosts = $conn->prepare("
            SELECT p.id, p.user_id, p.content, p.likes, p.created_at, u.username, u.profile_pic 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.content LIKE ?
            ORDER BY p.created_at DESC
        ");
        $stmtPosts->execute([$query]);
        $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch like status, liked_by users, and comment count for each post
    if (isset($_SESSION['user_id'])) {
        foreach ($posts as &$post) {
            // Check if the current user liked this post
            $stmtLike = $conn->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ? AND post_id = ?");
            $stmtLike->execute([$_SESSION['user_id'], $post['id']]);
            $post['is_liked'] = $stmtLike->fetchColumn() > 0;

            // Fetch users who liked this post
            $stmtLikedBy = $conn->prepare("SELECT u.id, u.username FROM likes l JOIN users u ON l.user_id = u.id WHERE l.post_id = ?");
            $stmtLikedBy->execute([$post['id']]);
            $post['liked_by'] = $stmtLikedBy->fetchAll(PDO::FETCH_ASSOC);

            // Fetch comment count for this post
            $stmtCommentCount = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
            $stmtCommentCount->execute([$post['id']]);
            $post['comment_count'] = $stmtCommentCount->fetchColumn();
        }
        unset($post); // Unset reference to avoid issues
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching search results: " . htmlspecialchars($e->getMessage());
    header("Location: index.php");
    exit();
}
?>

<div class="sr-main-content">
    <h5>Search Results for "<?php echo htmlspecialchars($raw_query); ?>"</h5>

    <?php if ($search_type === 'user'): ?>
        <!-- Users Section -->
        <h6><a href="profile.php">Users</a></h6>
        <?php if (empty($users)): ?>
            <p>No users found matching "<?php echo htmlspecialchars($raw_query); ?>".</p>
        <?php else: ?>
            <div class="sr-search-results-users">
                <?php foreach ($users as $user): ?>
                    <a href="profile.php?user_id=<?php echo urlencode($user['id']); ?>">
                        <div class="sr-profile-header">
                            <img src="../assets/uploads/<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>" class="sr-profile-pic" alt="Profile Picture">
                            <div class="sr-user-info">
                                <p><strong>@<?php echo htmlspecialchars($user['username']); ?></strong></p>
                                <p>Name: <?php echo htmlspecialchars($user['name'] ?? 'Not set'); ?></p>
                                <p>Bio: <?php echo htmlspecialchars($user['bio'] ?? 'No bio'); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Posts Section -->
    <h6><?php echo $search_type === 'user' ? 'Posts from Matching Users' : 'Posts Matching Your Search'; ?></h6>
    <?php if (empty($posts)): ?>
        <p><a href="post.php">No posts found <?php echo $search_type === 'user' ? 'from users matching' : 'matching'; ?> "<?php echo htmlspecialchars($raw_query); ?>"</a>.</p>
    <?php else: ?>
        <div class="sr-search-results-posts">
            <?php foreach ($posts as $post): ?>
                <a href="post.php?id=<?php echo urlencode($post['id']); ?>">
                    <div class="sr-post">
                        <div class="sr-post-header">
                            <div class="sr-post-header-user-info">
                                <img src="../assets/uploads/<?php echo htmlspecialchars($post['profile_pic'] ?? 'default.jpg'); ?>" alt="Profile">
                                <strong>@<?php echo htmlspecialchars($post['username']); ?></strong>
                            </div>
                            <div class="sr-post-options">
                                <span class="sr-three-dots">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="12" cy="6" r="2" />
                                        <circle cx="12" cy="12" r="2" />
                                        <circle cx="12" cy="18" r="2" />
                                    </svg>
                                </span>
                                <div class="sr-post-options-menu">
                                    <a href="#">Edit</a>
                                    <a href="#">Delete</a>
                                    <a href="#">Report</a>
                                </div>
                            </div>
                        </div>
                        <div class="sr-post-content">
                            <?php
                            $content = htmlspecialchars($post['content']);
                            $hashtags = [];
                            preg_match_all('/#(\w+)/', $content, $matches);
                            if (!empty($matches[0])) {
                                $hashtags = $matches[0];
                                foreach ($matches[0] as $hashtag) {
                                    $content = str_replace($hashtag, "<span class=\"sr-hashtag\">$hashtag</span>", $content);
                                }
                            }
                            echo $content;
                            ?>
                        </div>
                        <?php if (!empty($hashtags)): ?>
                            <div class="sr-post-hashtags">
                                <?php echo implode(' ', $hashtags); ?>
                            </div>
                        <?php endif; ?>
                        <div class="sr-timestamp">
                            <?php
                            $date = new DateTime($post['created_at']);
                            $formatted_date = $date->format('M d, Y');
                            echo htmlspecialchars($formatted_date);
                            ?>
                        </div>
                        <div class="sr-post-actions">
                            <button class="sr-like-button <?php echo isset($post['is_liked']) && $post['is_liked'] ? 'liked' : ''; ?>" 
                                    data-post-id="<?php echo $post['id']; ?>" 
                                    onclick="toggleLike(this)">
                                <svg class="sr-like-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                                <span class="sr-like-count" id="sr-like-count-<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['likes']); ?></span>
                            </button>
                            <button class="sr-comment-button" data-post-id="<?php echo $post['id']; ?>" onclick="addComment(this)">
                                <svg class="sr-comment-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                                </svg>
                                <span class="sr-comment-count" id="sr-comment-count-<?php echo $post['id']; ?>"><?php echo isset($post['comment_count']) ? $post['comment_count'] : 0; ?></span>
                            </button>
                            <div class="sr-likes-dropdown" id="sr-likes-dropdown-<?php echo $post['id']; ?>">
                                <?php if (!empty($post['liked_by'])): ?>
                                    <?php foreach ($post['liked_by'] as $liker): ?>
                                        <a href="profile.php?user_id=<?php echo urlencode($liker['id']); ?>" class="sr-liker"><?php echo htmlspecialchars($liker['username']); ?></a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="sr-liker">No likes yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Comment Section -->
                        <div class="sr-comment-section">
                            <div class="sr-add-comment-form">
                                <input type="text" class="sr-comment-input" placeholder="Write a comment..." data-post-id="<?php echo $post['id']; ?>">
                                <button class="sr-comment-submit" data-post-id="<?php echo $post['id']; ?>">Post</button>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('click', function(event) {
    if (!event.target.closest('.sr-like-button') && !event.target.closest('.sr-likes-dropdown')) {
        document.querySelectorAll('.sr-likes-dropdown.show').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

function toggleLike(button) {
    const postId = button.getAttribute('data-post-id');
    const isLiked = button.classList.contains('liked');
    const action = isLiked ? 'unlike' : 'like';
    const likeCountSpan = document.getElementById(`sr-like-count-${postId}`);
    const dropdown = document.getElementById(`sr-likes-dropdown-${postId}`);

    button.disabled = true;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../api/like_post.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        button.disabled = false;
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    likeCountSpan.textContent = response.likes;
                    button.classList.toggle('liked', response.isLiked);
                    if (response.liked_by && response.liked_by.length > 0) {
                        dropdown.innerHTML = response.liked_by.map(user => 
                            `<a href="profile.php?user_id=${encodeURIComponent(user.id)}" class="sr-liker">${user.username}</a>`
                        ).join('');
                    } else {
                        dropdown.innerHTML = '<div class="sr-liker">No likes yet.</div>';
                    }
                } else {
                    console.error('Failed to ' + action + ' the post: ' + response.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        } else {
            console.error('Request failed with status: ' + xhr.status);
        }
    };
    xhr.onerror = function() {
        button.disabled = false;
        console.error('Request failed');
    };
    xhr.send(`post_id=${postId}&action=${action}&user_id=<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>`);
}

function addComment(button) {
    const postId = button.getAttribute('data-post-id');
    const form = button.closest('.sr-add-comment-form');
    const input = form.querySelector('.sr-comment-input');
    const comment = input.value.trim();
    const commentCountSpan = document.getElementById(`sr-comment-count-${postId}`);

    if (comment) {
        button.disabled = true;
        console.log(`Sending: post_id=${postId}&user_id=<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>&comment=${encodeURIComponent(comment)}`);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../api/comment_post.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            button.disabled = false;
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        commentCountSpan.textContent = response.comment_count; // Update the comment count
                        input.value = ''; // Clear the input field
                    } else {
                        console.error('Failed to add comment: ' + response.message);
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            } else {
                console.error('Request failed with status: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            button.disabled = false;
            console.error('Request failed');
        };
        xhr.send(`post_id=${postId}&user_id=<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>&comment=${encodeURIComponent(comment)}`);
    }
}

document.querySelectorAll('.sr-comment-submit').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Button clicked, post_id:', this.getAttribute('data-post-id'));
        addComment(this);
    });
});
</script>