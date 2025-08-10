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

require __DIR__ . '/../../app/Views/layout/user_layout.php';
