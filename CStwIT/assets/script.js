function likePost(postId, button) {
    console.log("Sending request for postId:", postId, "Action:", button.textContent.includes("Unlike") ? "unlike" : "like");
    const xhr = new XMLHttpRequest();
    const isLiked = button.textContent.includes("Unlike");
    const action = isLiked ? "unlike" : "like";
    xhr.open("POST", "/CStwIT/api/like_post.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        console.log("Response status:", xhr.status, "Response text:", xhr.responseText);
        if (xhr.status === 200) {
          const response = JSON.parse(xhr.responseText);
          if (response.success) {
            document.getElementById("likes-" + postId).innerText = response.likes;
            button.textContent = response.isLiked ? "Unlike" : "Like";
            button.textContent += " (" + response.likes + ")";
          } else {
            alert("Failed to " + action + " the post: " + response.message);
          }
        } else {
          alert("Failed to " + action + " the post: Server error " + xhr.status);
        }
      }
    };
    xhr.send("post_id=" + postId + "&action=" + action);
  }


  
// Add these event listeners to the DOMContentLoaded function

// Edit post functionality
document.querySelectorAll('.edit-option').forEach(option => {
  option.addEventListener('click', function(e) {
    e.preventDefault();
    const postId = this.getAttribute('data-post-id');
    // Implement edit functionality
    console.log(`Edit post ${postId}`);
    // You could open a modal or redirect to an edit page
  });
});

// Delete post functionality
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
                // Remove the post from the DOM or reload the page
                window.location.reload();
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

// View post functionality
document.querySelectorAll('.view-option').forEach(option => {
  option.addEventListener('click', function(e) {
    e.preventDefault();
    const postId = this.getAttribute('data-post-id');
    // Redirect to single post view
    window.location.href = `post.php?id=${postId}`;
  });
});

// Report post functionality
document.querySelectorAll('.report-option').forEach(option => {
  option.addEventListener('click', function(e) {
    e.preventDefault();
    const postId = this.getAttribute('data-post-id');
    // Implement report functionality
    if (confirm('Are you sure you want to report this post?')) {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", "../api/report_post.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
          if (xhr.status === 200) {
            alert("Post has been reported. Thank you for helping us maintain community standards.");
          } else {
            alert("Failed to report post. Please try again later.");
          }
        }
      };
      xhr.send("post_id=" + postId);
    }
  });
});