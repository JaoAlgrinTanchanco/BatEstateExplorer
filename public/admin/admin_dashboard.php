<?php
require_once __DIR__ . '/../../app/bootstrap.php';

$conn = $GLOBALS['conn']; // Get the database connection from bootstrap
require_admin($conn); // Ensure only admins can access

$current_user = current_user($conn);
$view = $_GET['view'] ?? 'dashboard';

// Simple direct redirects instead of complex controller logic
switch ($view) {
    case 'dashboard':
        require __DIR__ . '/../../app/Views/admin/admin_dashboard_new.php';
        break;
    case 'direct_agents':
        require __DIR__ . '/../../app/Views/admin/admin_direct_agents.php';
        break;
    case 'associate_agents':
        require __DIR__ . '/../../app/Views/admin/admin_associate_agents.php';
        break;
    case 'properties':
        require __DIR__ . '/../../app/Views/admin/admin_property_listings.php';
        break;
    case 'properties_agents':
        require __DIR__ . '/../../app/Views/admin/admin_property_listings_agents.php';
        break;
    case 'applications':
        require __DIR__ . '/../../app/Views/admin/admin_applications.php';
        break;
    case 'reports':
        require __DIR__ . '/../../app/Views/admin/admin_reports.php';
        break;
    case 'reports_agents':
        require __DIR__ . '/../../app/Views/admin/admin_reports_agents.php';
        break;
    case 'reports_clients':
        require __DIR__ . '/../../app/Views/admin/admin_reports_clients.php';
        break;
    case 'performance':
        require __DIR__ . '/../../app/Views/admin/admin_performance.php';
        break;
    default:
        require __DIR__ . '/../../app/Views/admin/admin_dashboard_new.php';
}


