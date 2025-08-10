<?php
// Use this inside main-content only
?>

<header class="content-header">
    <h1>Admin Dashboard</h1>
    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
    </div>
</header>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fa-solid fa-house"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['total_properties']; ?></h3>
            <p>Active Properties</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="fa-solid fa-file-lines"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['pending_applications']; ?></h3>
            <p>Pending Applications</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="fa-solid fa-user-tie"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['total_agents']; ?></h3>
            <p>Active Agents</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['total_clients']; ?></h3>
            <p>Active Clients</p>
        </div>
    </div>
</div>

<!-- Recent Applications -->
<div class="recent-section">
    <h2>Recent Applications</h2>
    <div class="applications-list">
        <?php if (empty($recent_applications)): ?>
            <p class="no-data">No applications found.</p>
        <?php else: ?>
            <?php foreach ($recent_applications as $app): ?>
                <div class="application-item">
                    <div class="app-info">
                        <h4><?php echo htmlspecialchars($app['applicant_name'] ?? 'Unknown Applicant'); ?></h4>
                        <p><?php echo htmlspecialchars($app['company_name'] ?? 'No Company'); ?></p>
                        <span class="status status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span>
                    </div>
                    <div class="app-date">
                        <?php echo date('M j, Y', strtotime($app['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
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
