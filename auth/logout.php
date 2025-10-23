<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../public/app/redirects.php';


// Clear all session data
session_unset();
session_destroy();

// Clear any cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Use redirect helper for consistency
redirect_to_login();
?>  