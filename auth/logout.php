<?php
session_start();
require_once '../config/database.php';

// Clear all session data
session_unset();
session_destroy();

// Clear any cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to homepage
header('Location: login.php');
exit;
?>  