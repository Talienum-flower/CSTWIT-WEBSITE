<?php
// Include session check to ensure user is logged in
include 'includes/session.php';
// Include header
include 'includes/header.php';
// Debug: Confirm file is loading
var_dump(__FILE__);
// Debug: Confirm form action
echo "<!-- Form action: /CStwIT/api/create_post.php -->";
?>
<div class="container">
  <h2>Create a New Post</h2>
  <form action="/CStwIT/api/create_post.php" method="POST">
    <div class="form-group">
      <label>Post Content</label>
      <textarea name="content" placeholder="What's on your mind?" required></textarea>
    </div>
    <button type="submit" class="button">Post</button>
  </form>
</div>
<?php  ?>