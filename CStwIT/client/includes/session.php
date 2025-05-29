<?php
// Include session configuration
if (file_exists('../config/session.php')) {
  include '../config/session.php';
} else {
  die("Error: Session configuration file not found. Please ensure 'config/session.php' exists in the 'config' folder.");
}
?>