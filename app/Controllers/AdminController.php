<?php
namespace App\Controllers;

class AdminController
{
    private $conn;
    private $current_user;

    public function __construct($conn, $current_user)
    {
        $this->conn = $conn;
        $this->current_user = $current_user;
    }
    public function dashboard(): void
    {
        require __DIR__ . '/../../app/Views/admin/admin_dashboard_new.php';
    }

    public function directAgents(): void
    {
        require __DIR__ . '/../../app/Views/admin/admin_direct_agents.php';
    }

    public function associateAgents(): void
    {
        require __DIR__ . '/../../app/Views/admin/admin_associate_agents.php';
    }

    public function properties(): void
    {
        require __DIR__ . '/../../app/Views/admin/admin_property_listings.php';
    }

    public function propertiesAgents(): void
    {
        require __DIR__ . '/../../app/Views/admin/admin_property_listings_agents.php';
    }

    public function applications(): void
    {
        require __DIR__ . '/../../app/Views/admin/admin_applications.php';
    }

    public function reports(): void
    {
        require __DIR__ . '/../../app/Views/admin/admin_reports.php';
    }

    public function reportsAgents(): void
    {
        require __DIR__ . '/../../app/Views/admin/admin_reports_agents.php';
    }

    public function reportsClients(): void
    {
        require __DIR__ . '/../../app/Views/admin/admin_reports_clients.php';
    }

    public function performance(): void
    {
        require __DIR__ . '/../../app/Views/admin/admin_performance.php';
    }
}


