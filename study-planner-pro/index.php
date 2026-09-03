<?php
require_once 'config/config.php';
require_once 'config/functions.php';

if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
} else {
    header("Location: pages/login.php");
}
exit();
?>