<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

$view = $_GET['view'] ?? 'home';

// Simple direct redirects instead of complex controller logic
switch ($view) {
    case 'home':
        require __DIR__ . '/../../public/user/user_dashboard.php';
        break;
    case 'profile':
        require __DIR__ . '/../../app/Views/user/user_profile.php';
        break;
    case 'search':
        require __DIR__ . '/../../app/Views/user/user_search.php';
        break;
    default:
        require __DIR__ . '/../../public/user/user_dashboard.php';
}


