<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$user = current_user($conn);
if (!$user || !in_array($user['user_type'], ['direct_agent', 'associate_agent'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

$view = $_GET['view'] ?? 'dashboard';

switch ($view) {
    case 'direct_dashboard':
        // require __DIR__ . '/../app/Views/agent/direct_agent_dashboard_full.php';
        break;

    case 'direct_search':
        // require __DIR__ . '/../app/Views/agent/direct_agent_search.php';
        break;

    case 'associate_dashboard':
        require __DIR__ . '/../app/Views/agent/associate_home.php';
        break;

    case 'associate_profile':
        require __DIR__ . '/../app/Views/agent/associate_profile.php';
        break;

    case 'associate_search':
        require __DIR__ . '/../app/Views/agent/associate_search.php';
        break;

    default:
        if ($user['user_type'] === 'direct_agent') {
            // require __DIR__ . '/../app/Views/agent/direct_agent_dashboard_full.php';
        } else {
            require __DIR__ . '/../app/Views/agent/associate_home.php';
        }
}
