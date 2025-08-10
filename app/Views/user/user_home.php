<?php
// Make sure $current_user is defined, e.g. from session or passed data
$current_user = $current_user ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | BatEstate</title>
    <link rel="stylesheet" href="../../assets/css/user_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🏠 BatEstate</h2>
                <p>User Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="user_dashboard.php?view=home" class="active"><i class="fa-solid fa-home"></i> Home</a></li>
                    <li><a href="user_dashboard.php?view=profile"><i class="fa-solid fa-user"></i> Profile</a></li>
                    <li><a href="user_dashboard.php?view=search"><i class="fa-solid fa-search"></i> Search Properties</a></li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../../auth/logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="content-header">
                <h1>Welcome to BatEstate</h1>
                <div class="user-info">
                    <span>Hello, <?= htmlspecialchars($current_user['email'] ?? 'Guest'); ?></span>
                </div>
            </header>

            <div class="content-body">
                <div class="welcome-card">
                    <h2>Find Your Dream Property</h2>
                    <p>Welcome to BatEstate! We're here to help you find the perfect property in Batangas.</p>
                    
                    <div class="action-buttons">
                        <a href="user_dashboard.php?view=search" class="btn btn-primary">
                            <i class="fa-solid fa-search"></i> Search Properties
                        </a>
                        <a href="user_dashboard.php?view=profile" class="btn btn-secondary">
                            <i class="fa-solid fa-user"></i> View Profile
                        </a>
                    </div>
                </div>

                <div class="quick-stats">
                    <h3>Quick Overview</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <i class="fa-solid fa-house"></i>
                            <span>Browse Properties</span>
                        </div>
                        <div class="stat-item">
                            <i class="fa-solid fa-user-tie"></i>
                            <span>Connect with Agents</span>
                        </div>
                        <div class="stat-item">
                            <i class="fa-solid fa-heart"></i>
                            <span>Save Favorites</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
