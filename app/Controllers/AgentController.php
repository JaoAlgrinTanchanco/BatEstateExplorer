<?php
namespace App\Controllers;

class AgentController
{
    public function directDashboard(): void
    {
        require __DIR__ . '/../../app/Views/agent/direct_agent_dashboard_full.php';
    }

    public function directSearch(): void
    {
        require __DIR__ . '/../../app/Views/agent/direct_agent_search.php';
    }

    public function associateDashboard(): void
    {
        require __DIR__ . '/../../app/Views/agent/associate_agent_dashboard_full.php';
    }

    public function associateSearch(): void
    {
        require __DIR__ . '/../../app/Views/agent/associate_agent_search.php';
    }
}


