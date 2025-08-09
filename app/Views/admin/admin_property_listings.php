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
    header('Location: ../../../auth/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Property Listings | BatEstate</title>
    <link rel='stylesheet' href='../../../assets/css/admin_dashboard.css'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' crossorigin='anonymous' />
</head>
<body>
    <div class='admin-container'>
        <!-- Sidebar -->
        <div class='sidebar'>
            <div class='sidebar-header'>
                <h2> BatEstate</h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class='sidebar-nav'>
                <ul>
                    <li><a href='admin_dashboard.php?view=dashboard'><i class='fa-solid fa-tachometer-alt'></i> Dashboard</a></li>
                    <li><a href='admin_dashboard.php?view=direct_agents'><i class='fa-solid fa-user-tie'></i> Direct Agents</a></li>
                    <li><a href='admin_dashboard.php?view=associate_agents'><i class='fa-solid fa-users'></i> Associate Agents</a></li>
                    <li><a href='admin_dashboard.php?view=properties'><i class='fa-solid fa-house-chimney'></i> Property Listings</a></li>
                    <li><a href='admin_dashboard.php?view=applications'><i class='fa-solid fa-file-lines'></i> Applications</a></li>
                    <li><a href='admin_dashboard.php?view=reports'><i class='fa-solid fa-flag'></i> Reports</a></li>
                    <li><a href='admin_dashboard.php?view=performance'><i class='fa-solid fa-chart-bar'></i> Performance</a></li>
                </ul>
            </nav>
            
            <div class='sidebar-footer'>
                <a href='../../auth/logout.php'><i class='fa-solid fa-sign-out-alt'></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class='main-content'>
            <header class='content-header'>
                <h1>Admin Property Listings</h1>
                <div class='user-info'>
                    <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
                </div>
            </header>

            <div class='content-body'>
                <h2>Admin Property Listings</h2>
                <p>This page will contain the admin property listings functionality.</p>
                <p>Coming soon...</p>
            </div>
        </div>
    </div>

    <script src='../../../assets/js/admin_dashboard.js'></script>
</body>
</html>
