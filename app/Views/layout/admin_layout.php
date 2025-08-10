<?php
// Assumes $current_user, $view, and $content_path are set before including this file

// Helper function to mark active menu item
function isActive($menu_view, $current_view) {
    return $menu_view === $current_view ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Panel | BatEstate</title>
    <link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_dashboard.css">
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      crossorigin="anonymous"
    />
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
                    <li><a href="admin_dashboard.php?view=dashboard" class="<?php echo isActive('dashboard', $view); ?>"><i class="fa-solid fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="admin_dashboard.php?view=direct_agents" class="<?php echo isActive('direct_agents', $view); ?>"><i class="fa-solid fa-user-tie"></i> Direct Agents</a></li>
                    <li><a href="admin_dashboard.php?view=associate_agents" class="<?php echo isActive('associate_agents', $view); ?>"><i class="fa-solid fa-users"></i> Associate Agents</a></li>
                    <li><a href="admin_dashboard.php?view=properties" class="<?php echo isActive('properties', $view); ?>"><i class="fa-solid fa-house-chimney"></i> Property Listings</a></li>
                    <li><a href="admin_dashboard.php?view=applications" class="<?php echo isActive('applications', $view); ?>"><i class="fa-solid fa-file-lines"></i> Applications</a></li>
                    <li><a href="admin_dashboard.php?view=reports" class="<?php echo isActive('reports', $view); ?>"><i class="fa-solid fa-flag"></i> Reports</a></li>
                    <li><a href="admin_dashboard.php?view=performance" class="<?php echo isActive('performance', $view); ?>"><i class="fa-solid fa-chart-bar"></i> Performance</a></li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="../../auth/logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="content-header">
                <h1>
                    <?php
                    // Optional: custom titles per view
                    $titles = [
                        'dashboard' => 'Admin Dashboard',
                        'direct_agents' => 'Direct Agents Management',
                        'associate_agents' => 'Associate Agents Management',
                        'properties' => 'Property Listings',
                        'applications' => 'Applications Management',
                        'reports' => 'Admin Reports',
                        'performance' => 'Admin Performance',
                    ];
                    echo $titles[$view] ?? 'Admin Panel';
                    ?>
                </h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
                </div>
            </header>

            <div class="content-body">
                <?php include $content_path; ?>
            </div>
        </div>
    </div>

    <script src="/BatEstateExplorer/assets/js/admin_dashboard.js"></script>
</body>
</html>
