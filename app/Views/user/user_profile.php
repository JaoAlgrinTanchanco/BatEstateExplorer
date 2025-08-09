<?php
// User profile view
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | BatEstate</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
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
                    <li><a href="user_dashboard.php?view=home"><i class="fa-solid fa-home"></i> Home</a></li>
                    <li><a href="user_dashboard.php?view=profile" class="active"><i class="fa-solid fa-user"></i> Profile</a></li>
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
                <h1>User Profile</h1>
                <div class="user-info">
                    <span>Profile for: <?php echo htmlspecialchars($current_user['email']); ?></span>
                </div>
            </header>

            <div class="content-body">
                <div class="profile-card">
                    <h2>Account Information</h2>
                    <div class="profile-info">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($current_user['email']); ?></p>
                        <p><strong>User Type:</strong> <?php echo htmlspecialchars($current_user['user_type']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($current_user['status']); ?></p>
                        <p><strong>Created:</strong> <?php echo htmlspecialchars($current_user['created_at'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
