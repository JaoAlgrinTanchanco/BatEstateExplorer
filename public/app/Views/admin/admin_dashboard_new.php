<?php
    // admin_dashboard_new.php
    // Expects $conn and $current_user available

    // Get dashboard statistics
    $stats = [];
    $queries = [
        'total_properties' => "SELECT COUNT(*) as count FROM properties WHERE status = 'available'",
        'pending_applications' => "SELECT COUNT(*) as count FROM applications WHERE status = 'pending'",
        'total_agents' => "SELECT COUNT(*) as count 
                        FROM users 
                        WHERE user_type IN ('direct_agent', 'associate_agent') 
                            AND status = 'active'",
        'total_clients' => "SELECT COUNT(*) as count 
                            FROM users 
                            WHERE user_type = 'user' 
                            AND status = 'active' 
                            AND JSON_LENGTH(privileges) > 0"
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
    $query = "SELECT a.*, c.name as company_name, 
            CONCAT(a.first_name, ' ', a.last_name) as applicant_name 
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

    // Applications by status (Donut Chart)
    $applications_by_status = [];
    $query = "SELECT status, COUNT(*) as total
            FROM applications
            GROUP BY status";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $applications_by_status[$row['status']] = $row['total'];
        }
    }

    // Applications over the last 12 months
    $applications_over_time = [];
    $query = "
        SELECT DATE_FORMAT(month_series.month, '%Y-%m') as month, 
            IFNULL(a.total, 0) as total
        FROM (
            SELECT DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL seq MONTH), '%Y-%m-01') as month
            FROM (
                SELECT 0 as seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11
            ) as months
        ) as month_series
        LEFT JOIN (
            SELECT DATE_FORMAT(created_at, '%Y-%m-01') as month, COUNT(*) as total
            FROM applications
            GROUP BY month
        ) a ON a.month = month_series.month
        ORDER BY month_series.month ASC
    ";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $applications_over_time[] = $row;
        }
    }

    $all_locations = [
        'Agoncillo','Alitagtag','Balayan','Balete','Batangas City','Bauan','Calaca','Calatagan',
        'Cuenca','Ibaan','Laurel','Lemery','Lian','Lipa City','Lobo','Mabini','Malvar','Mataasnakahoy',
        'Nasugbu','Padre Garcia','Rosario','San Jose','San Juan','San Luis','San Nicolas','San Pascual',
        'Santa Teresita','Santo Tomas','Taal','Talisay','Tanauan City','Taysan','Tingloy','Tuy'
    ];

    // Fetch property counts by location
    $properties_by_location = [];
    $query = "SELECT location, COUNT(*) as total FROM properties GROUP BY location";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $properties_by_location[$row['location']] = $row['total'];
        }
    }

    // Ensure all locations exist in the array, even if 0
    foreach ($all_locations as $loc) {
        if (!isset($properties_by_location[$loc])) {
            $properties_by_location[$loc] = 0;
        }
    }

    // Sort by $all_locations order
    $properties_by_location = array_merge(array_flip($all_locations), $properties_by_location);

?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_dashboard_new.css" />

<!-- Dashboard content -->
<header class="content-header">
    <h1>Admin Dashboard</h1>
</header>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-house"></i></div>
        <div class="stat-content">
            <h3><?= $stats['total_properties'] ?></h3>
            <p>Active Properties</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-file-lines"></i></div>
        <div class="stat-content">
            <h3><?= $stats['pending_applications'] ?></h3>
            <p>Pending Applications</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-user-tie"></i></div>
        <div class="stat-content">
            <h3><?= $stats['total_agents'] ?></h3>
            <p>Active Agents</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="stat-content">
            <h3><?= $stats['total_clients'] ?></h3>
            <p>Active Clients</p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- LEFT COLUMN -->
    <div class="recent-card">
        <div class="recent-card-header">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Recent Applications</h2>
            <div class="dropdown">
                <button class="dropdown-toggle">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu">
                    <li data-status="approved">Remove Approved Applications</li>
                    <li data-status="rejected">Remove Rejected Applications</li>
                    <li data-status="pending">Remove Pending Applications</li>
                    <li data-status="all" class="danger-option">Clear All Applications</li>
                </ul>
            </div>
        </div>

        <div class="recent-card-body">
            <div class="applications-list">
                <?php if (empty($recent_applications)): ?>
                    <p class="no-data">No applications found.</p>
                <?php else: ?>
                    <?php foreach ($recent_applications as $app): ?>
                        <div class="application-item">
                            <div class="app-info">
                                <h4><?= htmlspecialchars($app['applicant_name'] ?? 'Unknown Applicant') ?></h4>
                                <p><?= htmlspecialchars($app['company_name'] ?? 'No Company') ?></p>
                                <span class="status status-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span>
                            </div>
                            <div class="app-date">
                                <?= date('M j, Y', strtotime($app['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="right-column">
        <!-- Top: Charts side by side -->
        <div class="right-top">
            <div class="chart-container">
                <h2>Applications by Status</h2>
                <canvas id="donutChart"></canvas>
            </div>
            <div class="chart-container">
                <h2>Applications Over Time</h2>
                <canvas id="lineChart"></canvas>
            </div>
        </div>

        <!-- Bottom: Bar Graph -->
        <div class="right-bottom">
            <div class="chart-container">
                <h2>Properties by Location</h2>
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div id="confirmModal" class="modal" style="display:none;">
  <div class="modal-content">
    <h3 id="modalTitle">Confirm Action</h3>
    <p id="modalMessage">Are you sure you want to remove these applications?</p>
    <div class="modal-actions">
      <button id="cancelBtn" class="btn btn-secondary">Cancel</button>
      <button id="confirmBtn" class="btn btn-danger">Confirm</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const dropdowns = document.querySelectorAll('.dropdown');
        const modal = document.getElementById('confirmModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        let selectedStatus = null;

        // ===== Handle dropdown toggle =====
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');
            menu.style.display = 'none';

            toggle.addEventListener('click', e => {
                e.stopPropagation();

                // Close all other dropdowns
                dropdowns.forEach(d => {
                    const otherMenu = d.querySelector('.dropdown-menu');
                    if (otherMenu !== menu) otherMenu.style.display = 'none';
                });

                // Toggle current dropdown
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            });
        });

        // ===== Close dropdown when clicking outside =====
        document.addEventListener('click', () => {
            dropdowns.forEach(d => d.querySelector('.dropdown-menu').style.display = 'none');
        });

        // ===== Handle dropdown item click =====
        document.querySelectorAll('.dropdown-menu li').forEach(item => {
            item.addEventListener('click', e => {
                e.stopPropagation();
                selectedStatus = item.dataset.status;

                modalTitle.textContent = "Confirm Deletion";
                modalMessage.textContent = (selectedStatus === 'all')
                    ? "Are you sure you want to delete ALL applications? This cannot be undone."
                    : `Are you sure you want to remove all ${selectedStatus} applications?`;

                modal.style.display = 'flex';
                item.closest('.dropdown-menu').style.display = 'none';
            });
        });

        // ===== Modal actions =====
        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            selectedStatus = null;
        });

        confirmBtn.addEventListener('click', () => {
            if (!selectedStatus) return;

            fetch("/BatEstateExplorer/public/api/delete_applications.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "status=" + encodeURIComponent(selectedStatus)
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(() => alert("Something went wrong."));

            modal.style.display = 'none';
        });
    });

    // ===== Chart.js resize fix =====
    window.addEventListener('resize', () => {
        Object.values(Chart.instances).forEach(chart => chart.resize());
        document.querySelectorAll('.dashboard-grid, .right-column').forEach(el => {
            el.style.maxWidth = "100%";
            el.style.overflowX = "hidden";
        });
    });

    const grayShades = ["#111827", "#374151", "#6b7280", "#9ca3af", "#d1d5db"];

    // ===== Donut data =====
    const donutData = {
        labels: <?= json_encode(array_keys($applications_by_status)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($applications_by_status)) ?>,
            backgroundColor: [grayShades[0], grayShades[2], grayShades[4]],
            borderWidth: 0
        }]
    };

    // ===== Line Chart =====
    const lineData = {
        labels: <?= json_encode(array_column($applications_over_time, 'month')) ?>,
        datasets: [{
            label: "Applications",
            data: <?= json_encode(array_column($applications_over_time, 'total')) ?>,
            borderColor: grayShades[0],
            backgroundColor: "rgba(17,24,39,0.08)",
            fill: true,
            tension: 0.35,
            pointBackgroundColor: grayShades[1],
            pointBorderColor: "#fff",
            pointBorderWidth: 2,
            pointRadius: 4
        }]
    };

    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: lineData,
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    ticks: {
                        color: "#6b7280",
                        callback: function(value, index) {
                            // Format 'YYYY-MM' to 'Jan 2025'
                            const monthStr = this.getLabelForValue(index); // '2025-01'
                            const date = new Date(monthStr + '-01');
                            return date.toLocaleString('default', { month: 'short', year: 'numeric' });
                        }
                    },
                    grid: { display: false }
                },
                y: {
                    ticks: { color: "#6b7280" },
                    grid: { color: "rgba(0,0,0,0.05)" },
                    beginAtZero: true
                }
            }
        }
    });

    // ===== Bar data (black gradient) =====
    const barData = {
        labels: <?= json_encode(array_keys($properties_by_location)) ?>,
        datasets: [{
            label: "Properties",
            data: <?= json_encode(array_values($properties_by_location)) ?>,
            backgroundColor: [],
            borderRadius: 6
        }]
    };

    // ===== Donut Chart =====
    new Chart(document.getElementById('donutChart'), {
        type: 'doughnut',
        data: donutData,
        options: {
            responsive: true,
            cutout: "70%",
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: "#374151", font: { family: "Satoshi-Regular" } }
                }
            }
        }
    });

    // ===== Bar Chart with Black Gradient =====
    const barCtx = document.getElementById('barChart').getContext('2d');

    // Apply black gradient to each bar
    barData.datasets[0].data.forEach(() => {
        const grad = barCtx.createLinearGradient(0, 0, 0, barCtx.canvas.height);
        grad.addColorStop(0, 'rgba(0,0,0,0.9)'); // top black
        grad.addColorStop(1, 'rgba(0,0,0,0.1)'); // bottom transparent
        barData.datasets[0].backgroundColor.push(grad);
    });

    new Chart(barCtx, {
        type: 'bar',
        data: barData,
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: "#6b7280" }, grid: { display: false } },
                y: { ticks: { color: "#6b7280" }, grid: { color: "rgba(0,0,0,0.05)" }, beginAtZero: true }
            }
        }
    });
</script>
