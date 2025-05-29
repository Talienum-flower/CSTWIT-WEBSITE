<?php
// File: api/fetch_following_posts.php
// Include database connection
include '../config/database.php';

// No need for session_start() here

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $following_posts = [];
} else {
    try {
        $user_id = $_SESSION['user_id'];

        // Query to get posts from users the logged-in user follows
        $stmt = $conn->prepare("
            SELECT p.*, u.username AS post_username, u.profile_pic,
                   c.id AS comment_id, c.comment, c.created_at AS comment_created_at,
                   uc.username AS comment_username,
                   l.id AS like_id, lu.username AS liker_username
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN comments c ON c.post_id = p.id
            LEFT JOIN users uc ON c.user_id = uc.id
            LEFT JOIN likes l ON l.post_id = p.id
            LEFT JOIN users lu ON l.user_id = lu.id
            JOIN follows f ON p.user_id = f.followed_id
            WHERE f.follower_id = ? AND f.followed_id IS NOT NULL
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Query results: " . print_r($results, true)); // Debug

        $following_posts = [];
        foreach ($results as $row) {
            $post_id = $row['id'];
            if (!isset($following_posts[$post_id])) {
                $following_posts[$post_id] = [
                    'id' => $row['id'],
                    'user_id' => $row['user_id'],
                    'content' => $row['content'],
                    'created_at' => $row['created_at'],
                    'likes' => $row['likes'],
                    'username' => $row['post_username'],
                    'profile_pic' => $row['profile_pic'],
                    'comments' => [],
                    'liked_by' => [],
                    'is_liked' => false // Add is_liked flag
                ];
            }
            if ($row['comment_id']) {
                $following_posts[$post_id]['comments'][] = [
                    'id' => $row['comment_id'],
                    'username' => $row['comment_username'],
                    'comment' => $row['comment'],
                    'created_at' => $row['comment_created_at']
                ];
            }
            if ($row['like_id']) {
                $following_posts[$post_id]['liked_by'][] = [
                    'username' => $row['liker_username']
                ];
                // Check if the current user has liked this post
                if (isset($_SESSION['username']) && $row['liker_username'] === $_SESSION['username']) {
                    $following_posts[$post_id]['is_liked'] = true;
                }
            }
        }
        $following_posts = array_values($following_posts); // Reindex array
    } catch (PDOException $e) {
        // Set empty posts array if query fails
        $following_posts = [];
        error_log("Error fetching following posts: " . $e->getMessage());
    }
}
?>