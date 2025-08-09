<?php
require_once __DIR__ . '/../../app/bootstrap.php';

$conn = $GLOBALS['conn']; // Get the database connection from bootstrap
require_admin($conn); // Ensure only admins can access

$current_user = current_user($conn);

$controller = new App\Controllers\AdminController($conn, $current_user);
$view = $_GET['view'] ?? 'dashboard';

switch ($view) {
    case 'dashboard':
        $controller->dashboard();
        break;
    case 'direct_agents':
        $controller->directAgents();
        break;
    case 'associate_agents':
        $controller->associateAgents();
        break;
    case 'properties':
        $controller->properties();
        break;
    case 'properties_agents':
        $controller->propertiesAgents();
        break;
    case 'applications':
        $controller->applications();
        break;
    case 'reports':
        $controller->reports();
        break;
    case 'reports_agents':
        $controller->reportsAgents();
        break;
    case 'reports_clients':
        $controller->reportsClients();
        break;
    case 'performance':
        $controller->performance();
        break;
    default:
        $controller->dashboard();
}


