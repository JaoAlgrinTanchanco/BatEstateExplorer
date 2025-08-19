<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$user = current_user($conn);
if (!$user || !in_array($user['user_type'], ['direct_agent', 'associate_agent'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

// Get requested view
$requested_view = $_GET['view'] ?? null;

// Restrict views based on user type
if ($user['user_type'] === 'associate_agent') {
    $allowed_views = ['associate_home', 'associate_profile', 'associate_search'];
    $default_view  = 'associate_home';
} else { // direct_agent
    $allowed_views = ['direct_home', 'direct_profile', 'direct_search'];
    $default_view  = 'direct_home';
}

// If requested view is not allowed for this user, fallback
$view = in_array($requested_view, $allowed_views, true) ? $requested_view : $default_view;

switch ($view) {
    // Associate agent views
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

    // Direct agent views
    case 'direct_home':
        $view_file = __DIR__ . '/../app/Views/agent/direct_home.php';
        $page_title = "Direct Dashboard";
        break;
    case 'direct_profile':
        $view_file = __DIR__ . '/../app/Views/agent/direct_profile.php';
        $page_title = "My Profile";
        break;
    case 'direct_search':
        $view_file = __DIR__ . '/../app/Views/agent/direct_search.php';
        $page_title = "Search Properties";
        break;

    default:
        $view_file = __DIR__ . '/../app/Views/agent/' . $default_view . '.php';
        $page_title = ($user['user_type'] === 'associate_agent') ? "Associate Dashboard" : "Direct Dashboard";
        break;
}

// finally load layout
require __DIR__ . '/../app/Views/layout/agent_layout.php';
