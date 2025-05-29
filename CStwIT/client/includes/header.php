<?php
// No session_start() here; handled in config/session.php
// Determine if this is an auth page (login or register)
$current_file = basename($_SERVER['PHP_SELF']);
$is_auth_page = ($current_file == 'login.php' || $current_file == 'register.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>CStwIT</title>
  <link rel="stylesheet" href="../assets/style.css">
  <!-- Add Font Awesome for eye icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- Bootstrap JS and dependencies -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <!-- Load JavaScript file containing likePost() function -->
  <script src="../assets/script.js"></script>

</head>

<body<?php echo $is_auth_page ? ' class="auth-page"' : ''; ?>>
  <?php if (!$is_auth_page): ?>
  <header class="cs-header">
    <div class="cs-logo">
      <img src="../assets/images/logo.jpg" alt="CStwIT Logo">
      <h1>Welcome to CStwIT</h1>
    </div>
    <div class="cs-search">
      <form action="search.php" method="GET">
        <input type="text" name="query" placeholder="Search" aria-label="Search" class="search-input">
      </form>
    </div>
  </header>
  <?php endif; ?>
  <div class="container">

  <script>
    // Updated search script
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.querySelector('.cs-search form');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const query = this.querySelector('input[name="query"]').value.trim();
            
            if (query) {
                // Redirect to search_results.php with the query
                window.location.href = 'search_results.php?query=' + encodeURIComponent(query);
            } else {
                alert('Please enter a search query');
            }
        });
    }
});
    </script>
