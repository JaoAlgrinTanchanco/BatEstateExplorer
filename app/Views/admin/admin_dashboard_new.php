<?php
// admin_dashboard_new.php
// Expects $conn and $current_user available

// Get dashboard statistics
$stats = [];
$queries = [
    'total_properties' => "SELECT COUNT(*) as count FROM properties WHERE status = 'active'",
    'pending_applications' => "SELECT COUNT(*) as count FROM applications WHERE status = 'pending'",
    'total_agents' => "SELECT COUNT(*) as count FROM users WHERE user_type IN ('direct_agent', 'associate_agent') AND status = 'active'",
    'total_clients' => "SELECT COUNT(*) as count FROM users WHERE user_type = 'client' AND status = 'active'"
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
?>

<!-- Dashboard content -->
<header class="content-header">
    <h1>Admin Dashboard</h1>
    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
    </div>
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

<div class="recent-section">
    <h2>Recent Applications</h2>
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

<div class="quick-actions">
    <h2>Quick Actions</h2>
    <div class="action-buttons">
        <a href="admin_dashboard.php?view=applications" class="btn btn-primary">
            <i class="fa-solid fa-file-lines"></i> Review Applications
        </a>
        <a href="admin_dashboard.php?view=properties" class="btn btn-secondary">
            <i class="fa-solid fa-house"></i> Manage Properties
        </a>
        <a href="admin_dashboard.php?view=direct_agents" class="btn btn-success">
            <i class="fa-solid fa-user-plus"></i> Add Agent
        </a>
    </div>
</div>
