<?php
require_once __DIR__ . '/../app/bootstrap.php';

$conn = $GLOBALS['conn'];
require_admin($conn);

$current_user = current_user($conn);
$view = $_GET['view'] ?? 'dashboard';

ob_start();

switch ($view) {
    case 'dashboard':
        require __DIR__ . '/../app/Views/admin/admin_dashboard_new.php';
        break;
    case 'applications':
        require __DIR__ . '/../app/Views/admin/admin_applications.php';
        break;
    case 'agents':
        require __DIR__ . '/../app/Views/admin/admin_agents.php';
        break;
    case 'properties':
        require __DIR__ . '/../app/Views/admin/admin_property_listings.php';
        break;
    case 'reports':
        require __DIR__ . '/../app/Views/admin/admin_reports.php';
        break;
    case 'reports_agents':
        require __DIR__ . '/../app/Views/admin/admin_reports_agents.php';
        break;
    case 'reports_clients':
        require __DIR__ . '/../app/Views/admin/admin_reports_clients.php';
        break;
    case 'performance':
        require __DIR__ . '/../app/Views/admin/admin_performance.php';
        break;
    case 'reported_accounts':
        require __DIR__ . '/../app/Views/admin/admin_reported_accounts.php';
        break;
    case 'wallet':
        require __DIR__ . '/../app/Views/admin/admin_wallet.php';
        break;
    default:
        require __DIR__ . '/../app/Views/admin/admin_dashboard_new.php';
}

$content_html = ob_get_clean();

require __DIR__ . '/../app/Views/layout/admin_layout.php';
