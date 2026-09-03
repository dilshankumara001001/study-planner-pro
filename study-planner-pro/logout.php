<?php
// Start session to destroy it
session_start();

// Destroy all session data
session_destroy();

// Redirect to login page
header("Location: pages/login.php");
exit();
?>