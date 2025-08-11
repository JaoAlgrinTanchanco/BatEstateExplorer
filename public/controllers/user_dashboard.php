<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

$view = $_GET['view'] ?? 'home';

switch ($view) {
    case 'home':
        $contentView = __DIR__ . '/../../app/Views/user/user_home.php';
        break;
    case 'profile':
        $contentView = __DIR__ . '/../../app/Views/user/user_profile.php';
        break;
    case 'search':
        $contentView = __DIR__ . '/../../app/Views/user/user_search.php';
        break;
    default:
        $contentView = __DIR__ . '/../../app/Views/user/user_home.php';
}

// Make sure $current_user is set, e.g. from session
$current_user = $_SESSION['user'] ?? null;

// Capture the page-specific content
ob_start();
require $contentView;
$content = ob_get_clean();

// Set the page title depending on view (optional but recommended)
switch ($view) {
    case 'home':
        $title = 'User Dashboard | BatEstate';
        break;
    case 'profile':
        $title = 'User Profile | BatEstate';
        break;
    case 'search':
        $title = 'Search Properties | BatEstate';
        break;
    default:
        $title = 'User Dashboard | BatEstate';
}

require __DIR__ . '/../../app/Views/layout/user_layout.php';
