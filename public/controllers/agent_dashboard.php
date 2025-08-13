<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$user = current_user($conn);
if (!$user || !in_array($user['user_type'], ['direct_agent', 'associate_agent'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

$view = $_GET['view'] ?? (
    $user['user_type'] === 'associate_agent'
        ? 'associate_home'
        : 'direct_dashboard'
);

switch ($view) {
    case 'associate_home':
        $view_file = __DIR__ . '/../app/Views/agent/associate_home.php';
        $page_title = "Associate Dashboard";
        break;
    case 'associate_profile':
        $view_file = __DIR__ . '/../app/Views/agent/associate_profile.php';
        $page_title = "My Profile";
        break;
    case 'associate_search':
        $view_file = __DIR__ . '/../app/Views/agent/associate_search.php';
        $page_title = "Search Properties";
        break;
    // Future: direct agent cases
    default:
        $view_file = __DIR__ . '/../app/Views/agent/associate_home.php';
        $page_title = "Associate Dashboard";
        break;
}

// finally load layout
require __DIR__ . '/../app/Views/layout/agent_layout.php';
