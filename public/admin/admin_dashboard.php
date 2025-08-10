<?php
require_once __DIR__ . '/../../app/bootstrap.php';

$conn = $GLOBALS['conn']; // Get the database connection from bootstrap
require_admin($conn); // Ensure only admins can access

$current_user = current_user($conn);
$view = $_GET['view'] ?? 'dashboard';

// Map views to their modular content partials (inside main-content)
$content_views = [
    'dashboard'           => 'admin_dashboard_new.php',
    'direct_agents'       => 'admin_direct_agents.php',
    'associate_agents'    => 'admin_associate_agents.php',
    'properties'          => 'admin_property_listings.php',
    'properties_agents'   => 'admin_property_listings_agents.php',
    'applications'        => 'admin_applications.php',
    'reports'             => 'admin_reports.php',
    'reports_agents'      => 'admin_reports_agents.php',
    'reports_clients'     => 'admin_reports_clients.php',
    'performance'         => 'admin_performance.php',
];

// Default to dashboard if unknown view
if (!array_key_exists($view, $content_views)) {
    $view = 'dashboard';
}

// Define the path of the content partial for the current view
$content_path = __DIR__ . '/../../app/Views/admin/' . $content_views[$view];

// Load the main layout template which will include the partial content inside
require __DIR__ . '/../../app/Views/admin/admin_layout.php';
