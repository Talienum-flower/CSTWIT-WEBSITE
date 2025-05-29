<?php
// Include database connection
include '../config/database.php';
try {
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
    ORDER BY p.created_at DESC
  ");
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $posts = [];
  foreach ($results as $row) {
    $post_id = $row['id'];
    if (!isset($posts[$post_id])) {
      $posts[$post_id] = [
        'id' => $row['id'],
        'user_id' => $row['user_id'],
        'content' => $row['content'],
        'created_at' => $row['created_at'],
        'likes' => $row['likes'],
        'username' => $row['post_username'],
        'profile_pic' => $row['profile_pic'],
        'comments' => [],
        'liked_by' => []
      ];
    }
    if ($row['comment_id']) {
      $posts[$post_id]['comments'][] = [
        'id' => $row['comment_id'],
        'username' => $row['comment_username'],
        'comment' => $row['comment'],
        'created_at' => $row['comment_created_at']
      ];
    }
    if ($row['like_id'] && !in_array($row['liker_username'], array_column($posts[$post_id]['liked_by'], 'username'))) {
      $posts[$post_id]['liked_by'][] = [
        'username' => $row['liker_username']
      ];
    }
  }
  $posts = array_values($posts); // Reindex array
} catch (PDOException $e) {
  // Set empty posts array if query fails
  $posts = [];
}
?>