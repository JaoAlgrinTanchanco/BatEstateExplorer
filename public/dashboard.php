<?php
require_once '../app/bootstrap.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect_to_login();
}

$conn = $GLOBALS['conn'];
$current_user = current_user($conn);

// Use the redirect helper for clean, simple redirects
redirect_by_user_type($current_user['user_type']);