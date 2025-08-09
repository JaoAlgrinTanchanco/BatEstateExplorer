<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

$user = current_user($conn);
if (!$user || !in_array($user['user_type'], ['direct_agent','associate_agent'], true)) {
    header('Location: login.php');
    exit;
}

use App\Controllers\AgentController;

$controller = new AgentController();
$view = $_GET['view'] ?? 'dashboard';

switch ($view) {
    case 'direct_dashboard':
        $controller->directDashboard();
        break;
    case 'direct_search':
        $controller->directSearch();
        break;
    case 'associate_dashboard':
        $controller->associateDashboard();
        break;
    case 'associate_search':
        $controller->associateSearch();
        break;
    default:
        if ($user['user_type'] === 'direct_agent') {
            $controller->directDashboard();
        } else {
            $controller->associateDashboard();
        }
}


