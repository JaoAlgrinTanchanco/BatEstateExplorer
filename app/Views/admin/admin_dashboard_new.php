<?php
// This file is included by AdminController, so session and database are already available
// Check if user is logged in and is admin
$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

// Redirect if not logged in or not admin
if (!$is_logged_in || !$is_admin) {
    header('Location: ../../../../auth/login.php');
    exit;
}

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | BatEstate</title>
    <link rel="stylesheet" href="../../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🏠 BatEstate</h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin_dashboard.php?view=dashboard" class="active"><i class="fa-solid fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="admin_dashboard.php?view=direct_agents"><i class="fa-solid fa-user-tie"></i> Direct Agents</a></li>
                    <li><a href="admin_dashboard.php?view=associate_agents"><i class="fa-solid fa-users"></i> Associate Agents</a></li>
                    <li><a href="admin_dashboard.php?view=properties"><i class="fa-solid fa-house-chimney"></i> Property Listings</a></li>
                    <li><a href="admin_dashboard.php?view=applications"><i class="fa-solid fa-file-lines"></i> Applications</a></li>
                    <li><a href="admin_dashboard.php?view=reports"><i class="fa-solid fa-flag"></i> Reports</a></li>
                    <li><a href="admin_dashboard.php?view=performance"><i class="fa-solid fa-chart-bar"></i> Performance</a></li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../../auth/logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
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
        </div>
    </div>

    <script src="../../../assets/js/admin_dashboard.js"></script>
</body>
</html>
