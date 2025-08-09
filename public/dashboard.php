<?php
require_once '../app/bootstrap.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit;
}

$conn = $GLOBALS['conn'];
$current_user = current_user($conn);

// Redirect based on user type
if ($current_user['user_type'] === 'admin') {
    header('Location: admin/');
} elseif ($current_user['user_type'] === 'direct_agent' || $current_user['user_type'] === 'associate_agent') {
    header('Location: agent/');
} else {
    header('Location: user/');
}
exit;