<?php
// Includes
include 'includes/session.php';
include 'includes/header.php';
include 'includes/left_sidebar.php';
include 'includes/right_sidebar.php';
include '../config/check_session.php';

// Fetch posts
if (file_exists('../api/fetch_posts.php')) {
    include '../api/fetch_posts.php';
} else {
    $posts = [];
}

// Fetch following posts (new)
if (file_exists('../api/fetch_following_posts.php')) {
    include '../api/fetch_following_posts.php';
} else {
    $following_posts = [];
}
?>
<!-- HTML -->
<div class="main-content">
    <!-- Tabs -->
    <div class="for-you-following-tabs">
        <div class="tab active" data-tab="for-you">For you</div>
        <div class="tab" data-tab="following">Following</div>
    </div>

    <!-- For You Tab Content -->
    <div class="tab-content active" id="for-you-content">
        <!-- Post Creation Form -->
        <form action="/CStwIT/api/create_post.php" method="POST" class="post-creation">
            <div class="post-input-container">
                <textarea class="post-input-area" name="content" placeholder="What's happening?" required></textarea>
            </div>
            <button class="post-btn" type="submit">Post</button>
        </form>

        <!-- Posts Display -->
        <?php if (isset($posts) && is_array($posts) && count($posts) > 0): ?>
            <?php foreach ($posts as $post): ?>
                <div class="post" data-post-id="<?php echo $post['id']; ?>">
                    <!-- Post Header -->
                    <div class="post-header">
                        <img src="../assets/uploads/<?php echo htmlspecialchars($post['profile_pic']); ?>" class="profile-pic" alt="Profile">
                        <div>
                            <strong>@<?php echo htmlspecialchars($post['username']); ?></strong>
                        </div>
                    </div>
                    <!-- Post Options -->
                    <div class="post-options">
                        <span class="three-dots">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="12" cy="5" r="2"/>
                                <circle cx="12" cy="12" r="2"/>
                                <circle cx="12" cy="19" r="2"/>
                            </svg>
                        </span>
                        <div class="post-options-menu">
                            <a href="#" class="view-option" data-post-id="<?php echo $post['id']; ?>">View</a>
                            <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                                <a href="#" class="edit-option" data-post-id="<?php echo $post['id']; ?>">Edit</a>
                                <a href="#" class="delete-option" data-post-id="<?php echo $post['id']; ?>">Delete</a>
                            <?php endif; ?>
                            <?php if ($_SESSION['user_id'] != $post['user_id']): ?>
                                <a href="#" class="report-option" data-post-id="<?php echo $post['id']; ?>">Report</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Post Content -->
                    <div class="post-content">
                        <?php echo htmlspecialchars($post['content']); ?>
                        <?php
                        // Extract and display hashtags
                        preg_match_all('/#([^\s]+)/', $post['content'], $hashtags);
                        if (!empty($hashtags[0])): ?>
                            <div class="post-hashtags">
                                <?php foreach ($hashtags[0] as $tag): ?>
                                    <?php echo htmlspecialchars($tag) . ' '; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Edit Post Modal -->
                    <div id="edit-modal" class="modal" style="display: none;">
                        <div class="modal-content">
                            <span class="close-modal">×</span>
                            <h3>Edit Post</h3>
                            <form id="edit-form">
                                <input type="hidden" id="edit-post-id" name="post_id">
                                <textarea id="edit-content" name="content" rows="4" required></textarea>
                                <button type="submit" class="edit-submit">Update Post</button>
                            </form>
                        </div>
                    </div>
                    <!-- Timestamp -->
                    <div class="timestamp">
                        <?php echo date('Y-m-d H:i:s', strtotime($post['created_at'])); ?>
                    </div>
                    <!-- Post Actions -->
                    <div class="post-actions">
                        <button class="like-button <?php echo (isset($post['is_liked']) && $post['is_liked']) ? 'liked' : ''; ?>" 
                                onclick="likePost(<?php echo $post['id']; ?>, this)">
                            <svg class="like-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                            <span class="count" id="likes-<?php echo $post['id']; ?>"><?php echo $post['likes']; ?></span>
                        </button>
                        <button class="comment-button" onclick="toggleComments(<?php echo $post['id']; ?>)">
                            <svg class="comment-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                            </svg>
                            <span class="count"><?php echo (int)count($post['comments']); ?></span>
                        </button>
                    </div>
                    <!-- Comment Section (Hidden by Default) -->
                    <div class="comment-section" id="comment-section-<?php echo $post['id']; ?>" style="display: none;">
                        <!-- Comment Form -->
                        <div class="add-comment-form">
                            <input type="text" id="comment-input-<?php echo $post['id']; ?>" 
                                   class="comment-input" placeholder="Comment" 
                                   onkeypress="handleCommentKeyPress(event, <?php echo $post['id']; ?>)">
                            <button class="comment-submit" onclick="submitComment(<?php echo $post['id']; ?>)">Post</button>
                        </div>
                        <!-- Comments Display -->
                        <?php if (!empty($post['comments'])): ?>
                            <div class="comments-section">
                                <?php foreach ($post['comments'] as $comment): ?>
                                    <div class="comment">
                                        <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                        <?php echo htmlspecialchars($comment['comment']); ?>
                                        <small><?php echo htmlspecialchars($comment['created_at']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Default Post -->
            <div class="post" data-post-id="0">
                <div class="post-header">
                    <div class="profile-pic"></div>
                    <strong>@username</strong>
                </div>
                <div class="post-options">
                    <span class="three-dots">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="5" r="2"/>
                            <circle cx="12" cy="12" r="2"/>
                            <circle cx="12" cy="19" r="2"/>
                        </svg>
                    </span>
                    <div class="post-options-menu">
                        <a href="#" class="view-option" data-post-id="0">View</a>
                        <?php if ($_SESSION['user_id'] != 0): ?>
                            <a href="#" class="report-option" data-post-id="0">Report</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="post-content">
                    Don't miss the moment — tap in, show some love, and be part of the excitement. 🔥
                    <div class="post-hashtags">
                        #NowHappening #LiveUpdate #StayTuned
                    </div>
                </div>
                <div class="post-actions">
                    <button class="like-button">
                        <svg class="like-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        <span class="count">230</span>
                    </button>
                    <button class="comment-button">
                        <svg class="comment-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                        <span class="count">0</span>
                    </button>
                </div>
            </div>
            <p>No other posts available.</p>
        <?php endif; ?>
    </div>

    <!-- Following Tab Content -->
    <div class="tab-content" id="following-content" style="display: none;">
        <!-- Post Creation Form -->
        <form action="/CStwIT/api/create_post.php" method="POST" class="post-creation">
            <div class="post-input-container">
                <textarea class="post-input-area" name="content" placeholder="What's happening?" required></textarea>
            </div>
            <button class="post-btn" type="submit">Post</button>
        </form>

        <!-- Following Posts Display -->
        <?php if (isset($following_posts) && is_array($following_posts) && count($following_posts) > 0): ?>
            <?php foreach ($following_posts as $post): ?>
                <div class="post" data-post-id="<?php echo $post['id']; ?>">
                    <!-- Post Header -->
                    <div class="post-header">
                        <img src="../assets/uploads/<?php echo htmlspecialchars($post['profile_pic']); ?>" class="profile-pic" alt="Profile">
                        <div>
                            <strong>@<?php echo htmlspecialchars($post['username']); ?></strong>
                        </div>
                    </div>
                    <!-- Post Options -->
                    <div class="post-options">
                        <span class="three-dots">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="12" cy="5" r="2"/>
                                <circle cx="12" cy="12" r="2"/>
                                <circle cx="12" cy="19" r="2"/>
                            </svg>
                        </span>
                        <div class="post-options-menu">
                            <a href="#" class="view-option" data-post-id="<?php echo $post['id']; ?>">View</a>
                            <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                                <a href="#" class="edit-option" data-post-id="<?php echo $post['id']; ?>">Edit</a>
                                <a href="#" class="delete-option" data-post-id="<?php echo $post['id']; ?>">Delete</a>
                            <?php endif; ?>
                            <?php if ($_SESSION['user_id'] != $post['user_id']): ?>
                                <a href="#" class="report-option" data-post-id="<?php echo $post['id']; ?>">Report</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Post Content -->
                    <div class="post-content">
                        <?php echo htmlspecialchars($post['content']); ?>
                        <?php
                        // Extract and display hashtags
                        preg_match_all('/#([^\s]+)/', $post['content'], $hashtags);
                        if (!empty($hashtags[0])): ?>
                            <div class="post-hashtags">
                                <?php foreach ($hashtags[0] as $tag): ?>
                                    <?php echo htmlspecialchars($tag) . ' '; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Edit Post Modal -->
                    <div id="edit-modal" class="modal" style="display: none;">
                        <div class="modal-content">
                            <span class="close-modal">×</span>
                            <h3>Edit Post</h3>
                            <form id="edit-form">
                                <input type="hidden" id="edit-post-id" name="post_id">
                                <textarea id="edit-content" name="content" rows="4" required></textarea>
                                <button type="submit hoje class="edit-submit">Update Post</button>
                            </form>
                        </div>
                    </div>
                    <!-- Timestamp -->
                    <div class="timestamp">
                        <?php echo date('Y-m-d H:i:s', strtotime($post['created_at'])); ?>
                    </div>
                    <!-- Post Actions -->
                    <div class="post-actions">
                        <button class="like-button <?php echo (isset($post['is_liked']) && $post['is_liked']) ? 'liked' : ''; ?>" 
                                onclick="likePost(<?php echo $post['id']; ?>, this)">
                            <svg class="like-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                            <span class="count" id="likes-<?php echo $post['id']; ?>"><?php echo $post['likes']; ?></span>
                        </button>
                        <button class="comment-button" onclick="toggleComments(<?php echo $post['id']; ?>)">
                            <svg class="comment-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                            </svg>
                            <span class="count"><?php echo (int)count($post['comments']); ?></span>
                        </button>
                    </div>
                    <!-- Comment Section (Hidden by Default) -->
                    <div class="comment-section" id="comment-section-<?php echo $post['id']; ?>" style="display: none;">
                        <!-- Comment Form -->
                        <div class="add-comment-form">
                            <input type="text" id="comment-input-<?php echo $post['id']; ?>" 
                                   class="comment-input" placeholder="Comment" 
                                   onkeypress="handleCommentKeyPress(event, <?php echo $post['id']; ?>)">
                            <button class="comment-submit" onclick="submitComment(<?php echo $post['id']; ?>)">Post</button>
                        </div>
                        <!-- Comments Display -->
                        <?php if (!empty($post['comments'])): ?>
                            <div class="comments-section">
                                <?php foreach ($post['comments'] as $comment): ?>
                                    <div class="comment">
                                        <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                        <?php echo htmlspecialchars($comment['comment']); ?>
                                        <small><?php echo htmlspecialchars($comment['created_at']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No posts from users you follow.</p>
        <?php endif; ?>
    </div>

    <!-- Report Modal -->
    <div id="report-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">×</span>
            <h3>Report Post</h3>
            <form id="report-form">
                <input type="hidden" id="report-post-id" name="post_id">
                <div class="report-reason">
                    <input type="radio" name="reason" value="Inappropriate Content" id="reason-inappropriate" required>
                    <label for="reason-inappropriate">Inappropriate Content</label>
                </div>
                <div class="report-reason">
                    <input type="radio" name="reason" value="Spam" id="reason-spam">
                    <label for="reason-spam">Spam</label>
                </div>
                <div class="report-reason">
                    <input type="radio" name="reason" value="Harassment" id="reason-harassment">
                    <label for="reason-harassment">Harassment</label>
                </div>
                <div class="report-reason">
                    <input type="radio" name="reason" value="Other" id="reason-other">
                    <label for="reason-other">Other</label>
                </div>
                <div id="other-reason-container" style="display: none;">
                    <textarea id="other-reason" name="other_reason" placeholder="Please specify the reason" rows="4"></textarea>
                </div>
                <button type="submit" class="report-submit">Submit Report</button>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript (Unchanged) -->
<script>
// Like Post
function likePost(postId, button) {
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
                        // Update all instances of this post's like count
                        const likeCountElements = document.querySelectorAll(`#likes-${postId}`);
                        likeCountElements.forEach(element => {
                            element.innerText = response.likes;
                        });
                        
                        // Update all instances of this post's like button
                        const likeButtons = document.querySelectorAll(`.post[data-post-id="${postId}"] .like-button`);
                        likeButtons.forEach(btn => {
                            btn.classList.toggle('liked', response.isLiked);
                        });
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
    const commentSection = document.getElementById('comment-section-' + postId);
    commentSection.style.display = commentSection.style.display === 'none' ? 'block' : 'none';
}

// Submit Comment
function submitComment(postId) {
    const input = document.getElementById('comment-input-' + postId);
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
                        // Add new comment dynamically without page reload
                        addCommentToDOM(postId, response.comment);
                        
                        // Update comment count
                        updateCommentCount(postId);
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

// Add new comment to DOM
function addCommentToDOM(postId, commentData) {
    const commentSections = document.querySelectorAll(`#comment-section-${postId} .comments-section`);
    
    // Create comment element
    const commentElement = document.createElement('div');
    commentElement.className = 'comment';
    commentElement.innerHTML = `
        <strong>${commentData.username || 'You'}</strong>
        ${commentData.comment}
        <small>${commentData.created_at || 'Just now'}</small>
    `;
    
    // If comments section doesn't exist, create it
    commentSections.forEach(section => {
        if (!section) {
            const commentSection = document.getElementById(`comment-section-${postId}`);
            const newCommentsSection = document.createElement('div');
            newCommentsSection.className = 'comments-section';
            commentSection.appendChild(newCommentsSection);
            newCommentsSection.appendChild(commentElement);
        } else {
            // Add the new comment to the top of existing comments section
            section.prepend(commentElement);
        }
    });
    
    // Make sure comment section is visible
    document.getElementById(`comment-section-${postId}`).style.display = 'block';
}

// Update comment count after adding a new comment
function updateCommentCount(postId) {
    const commentButtons = document.querySelectorAll(`.post[data-post-id="${postId}"] .comment-button .count`);
    commentButtons.forEach(button => {
        const currentCount = parseInt(button.innerText) || 0;
        button.innerText = currentCount + 1;
    });
}

// Handle Comment Key Press
function handleCommentKeyPress(event, postId) {
    if (event.key === 'Enter') {
        event.preventDefault();
        submitComment(postId);
    }
}

// Tab Switching and Post Options
document.addEventListener('DOMContentLoaded', function() {
    // Tab Switching
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            document.getElementById(this.getAttribute('data-tab') + '-content').style.display = 'block';
        });
    });

    // Post Options Menu
    const threeDots = document.querySelectorAll('.three-dots');
    threeDots.forEach(dots => {
        dots.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            document.querySelectorAll('.post-options-menu').forEach(m => {
                if (m !== menu) m.classList.remove('show');
            });
            menu.classList.toggle('show');
        });
    });

    // Close Menus on Click Outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.post-options-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    });

    // Edit Post
    document.querySelectorAll('.edit-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            
            // Find the post content
            const postElement = this.closest('.post');
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
                            // Update post content without page reload
                            updatePostContent(postId, content);
                            // Close the modal
                            document.getElementById('edit-modal').style.display = 'none';
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

    // Function to update post content without reload
    function updatePostContent(postId, newContent) {
        // Extract hashtags
        const hashtagRegex = /#([^\s]+)/g;
        const hashtags = newContent.match(hashtagRegex) || [];
        
        // Update all instances of this post
        const postContents = document.querySelectorAll(`.post[data-post-id="${postId}"] .post-content`);
        
        postContents.forEach(content => {
            // Update main text content
            content.innerHTML = newContent;
            
            // Add hashtags if they exist
            if (hashtags.length > 0) {
                let hashtagsHTML = '<div class="post-hashtags">';
                hashtags.forEach(tag => {
                    hashtagsHTML += tag + ' ';
                });
                hashtagsHTML += '</div>';
                content.innerHTML += hashtagsHTML;
            }
        });
    }

    // Delete Post
    document.querySelectorAll('.delete-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
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
                                    // Remove the post from DOM without reload
                                    deletePostFromDOM(postId);
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

    // Function to remove deleted post from DOM
    function deletePostFromDOM(postId) {
        const posts = document.querySelectorAll(`.post[data-post-id="${postId}"]`);
        posts.forEach(post => {
            post.style.opacity = '0';
            setTimeout(() => {
                post.style.display = 'none';
                post.remove();
            }, 300);
        });
    }

    // View Post
    document.querySelectorAll('.view-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            window.location.href = `post.php?id=${postId}`;
        });
    });

    // Report Post
    const reportModal = document.getElementById('report-modal');
    const closeModal = document.querySelector('#report-modal .close-modal');
    const reportForm = document.getElementById('report-form');
    const otherReasonContainer = document.getElementById('other-reason-container');
    const otherReasonInput = document.getElementById('other-reason');
    const reasonRadios = document.querySelectorAll('input[name="reason"]');

    // Show modal when report is clicked
    document.querySelectorAll('.report-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            document.getElementById('report-post-id').value = postId;
            reportModal.style.display = 'flex';
        });
    });

    // Close modal
    closeModal.addEventListener('click', function() {
        reportModal.style.display = 'none';
        reportForm.reset();
        otherReasonContainer.style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === reportModal) {
            reportModal.style.display = 'none';
            reportForm.reset();
            otherReasonContainer.style.display = 'none';
        }
    });

    // Show/hide other reason input
    reasonRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            otherReasonContainer.style.display = this.value === 'Other' ? 'block' : 'none';
            if (this.value !== 'Other') {
                otherReasonInput.value = '';
            }
        });
    });

    // Submit report
    reportForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const postId = document.getElementById('report-post-id').value;
        const selectedReason = document.querySelector('input[name="reason"]:checked').value;
        const reason = selectedReason === 'Other' ? otherReasonInput.value.trim() : selectedReason;

        if (selectedReason === 'Other' && !reason) {
            alert('Please specify a reason for the report.');
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "../api/report_post.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
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
    
    // Initialize comments sections if they don't exist
    document.querySelectorAll('.post').forEach(post => {
        const postId = post.getAttribute('data-post-id');
        const commentSection = document.getElementById(`comment-section-${postId}`);
        if (commentSection && !commentSection.querySelector('.comments-section')) {
            const commentsSection = document.createElement('div');
            commentsSection.className = 'comments-section';
            commentSection.appendChild(commentsSection);
        }
    });
});
</script>
<?php ?>