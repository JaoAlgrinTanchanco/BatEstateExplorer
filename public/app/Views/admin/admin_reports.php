<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

if (!$is_logged_in || !$is_admin) {
    header('Location: login.php');
    exit;
}
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_reports.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<header class="content-header">
    <h1>Admin Reports</h1>
</header>

<div class="reports-grid">
    <!-- Direct Agents Card -->
    <div class="report-card" id="directAgentsCard">
        <h2>Direct Agents</h2>
        <div class="metrics">
            <p>Total: <span id="totalDirectAgents">0</span></p>
            <p>Active: <span id="activeDirectAgents">0</span></p>
            <p>Inactive: <span id="inactiveDirectAgents">0</span></p>
            <p>Avg Experience: <span id="avgExpDirectAgents">0</span> years</p>
        </div>
        <div class="chart-container">
            <canvas id="directAgentChart"></canvas>
        </div>
    </div>

    <!-- Associate Agents Card -->
    <div class="report-card" id="associateAgentsCard">
        <h2>Associate Agents</h2>
        <div class="metrics">
            <p>Total: <span id="totalAssociateAgents">0</span></p>
            <p>Active: <span id="activeAssociateAgents">0</span></p>
            <p>Inactive: <span id="inactiveAssociateAgents">0</span></p>
            <p>Avg Experience: <span id="avgExpAssociateAgents">0</span> years</p>
        </div>
        <div class="chart-container">
            <canvas id="associateAgentChart"></canvas>
        </div>
    </div>

    <!-- Clients Card -->
    <div class="report-card" id="clientsCard">
        <h2>Clients</h2>
        <div class="metrics">
            <p>Total: <span id="totalClients">0</span></p>
            <p>Active: <span id="activeClients">0</span></p>
            <p>Inactive: <span id="inactiveClients">0</span></p>
        </div>
        <div class="chart-container">
            <canvas id="clientChart"></canvas>
        </div>
    </div>
</div>

<script>
async function fetchReports() {
    try {
        const response = await fetch('/BatEstateExplorer/public/api/admin_reports.php');
        const data = await response.json();

        function getChartData(agentData) {
            if (!agentData || !agentData.monthly_signups) return { labels: [], values: [] };
            return {
                labels: agentData.monthly_signups.map(i => i.month),
                values: agentData.monthly_signups.map(i => i.count)
            };
        }

        function createChart(id, dataPoints, color) {
            const ctx = document.getElementById(id).getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dataPoints.labels,
                    datasets: [{
                        label: 'Signups Over Time',
                        data: dataPoints.values,
                        borderColor: color,
                        backgroundColor: color.replace('1)', '0.2)'),
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // allow smooth stretching
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Set metrics and charts
        document.getElementById('totalDirectAgents').textContent = data.direct_agents.total;
        document.getElementById('activeDirectAgents').textContent = data.direct_agents.active;
        document.getElementById('inactiveDirectAgents').textContent = data.direct_agents.inactive;
        document.getElementById('avgExpDirectAgents').textContent = data.direct_agents.avg_experience_years;
        createChart('directAgentChart', getChartData(data.direct_agents), 'rgba(54, 162, 235, 1)');

        document.getElementById('totalAssociateAgents').textContent = data.associate_agents.total;
        document.getElementById('activeAssociateAgents').textContent = data.associate_agents.active;
        document.getElementById('inactiveAssociateAgents').textContent = data.associate_agents.inactive;
        document.getElementById('avgExpAssociateAgents').textContent = data.associate_agents.avg_experience_years;
        createChart('associateAgentChart', getChartData(data.associate_agents), 'rgba(255, 206, 86, 1)');

        document.getElementById('totalClients').textContent = data.clients.total;
        document.getElementById('activeClients').textContent = data.clients.active;
        document.getElementById('inactiveClients').textContent = data.clients.inactive;
        createChart('clientChart', getChartData(data.clients), 'rgba(75, 192, 192, 1)');

    } catch (err) {
        console.error('Failed to fetch reports:', err);
    }
}

fetchReports();

</script>
