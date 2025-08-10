<?php
require_once __DIR__ . '/../../app/bootstrap.php';

$conn = $GLOBALS['conn']; // Get the database connection from bootstrap
require_admin($conn); // Ensure only admins can access

$current_user = current_user($conn);
$view = $_GET['view'] ?? 'dashboard';

switch ($view) {
    case 'dashboard':
        // Fetch dashboard stats and recent applications here
        $stats = [];
        $queries = [
            'total_properties' => "SELECT COUNT(*) as count FROM properties WHERE status = 'active'",
            'pending_applications' => "SELECT COUNT(*) as count FROM applications WHERE status = 'pending'",
            'total_agents' => "SELECT COUNT(*) as count FROM users WHERE user_type IN ('direct_agent', 'associate_agent') AND status = 'active'",
            'total_clients' => "SELECT COUNT(*) as count FROM users WHERE user_type = 'client' AND status = 'active'",
        ];

        foreach ($queries as $key => $query) {
            $result = mysqli_query($conn, $query);
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $stats[$key] = $row['count'];
            } else {
                $stats[$key] = 0;
            }
        }

        // Get recent applications
        $recent_applications = [];
        $query = "SELECT a.*, c.name as company_name, CONCAT(a.first_name, ' ', a.last_name) as applicant_name 
                  FROM applications a 
                  LEFT JOIN companies c ON a.company_id = c.id 
                  ORDER BY a.created_at DESC 
                  LIMIT 5";

        $result = mysqli_query($conn, $query);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $recent_applications[] = $row;
            }
        }

        $content_path = __DIR__ . '/../../app/Views/admin/admin_dashboard_new.php';
        break;

    case 'direct_agents':
        $content_path = __DIR__ . '/../../app/Views/admin/admin_direct_agents.php';
        break;

    case 'associate_agents':
        $content_path = __DIR__ . '/../../app/Views/admin/admin_associate_agents.php';
        break;

    case 'properties':
        $content_path = __DIR__ . '/../../app/Views/admin/admin_property_listings.php';
        break;

    case 'properties_agents':
        $content_path = __DIR__ . '/../../app/Views/admin/admin_property_listings_agents.php';
        break;

    case 'applications':
        $content_path = __DIR__ . '/../../app/Views/admin/admin_applications.php';
        break;

    case 'reports':
        $content_path = __DIR__ . '/../../app/Views/admin/admin_reports.php';
        break;

    case 'reports_agents':
        $content_path = __DIR__ . '/../../app/Views/admin/admin_reports_agents.php';
        break;

    case 'reports_clients':
        $content_path = __DIR__ . '/../../app/Views/admin/admin_reports_clients.php';
        break;

    case 'performance':
        $content_path = __DIR__ . '/../../app/Views/admin/admin_performance.php';
        break;

    default:
        $content_path = __DIR__ . '/../../app/Views/admin/admin_dashboard_new.php';
        break;
}

// Now include the main layout and pass variables
require __DIR__ . '/../../app/Views/layout/admin_layout.php';
