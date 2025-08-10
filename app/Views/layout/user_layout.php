<?php
// user_layout.php

// $title (string) - Page title
// $current_user (array|null) - Logged-in user data
// $content (string) - HTML content of the page

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title ?? 'BatEstate User Panel') ?></title>
    <link rel="stylesheet" href="../../assets/css/user_dashboard.css" />
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
                    <li><a href="user_dashboard.php?view=home" <?= ($title === 'User Dashboard | BatEstate' || $title === 'Home') ? 'class="active"' : '' ?>><i class="fa-solid fa-home"></i> Home</a></li>
                    <li><a href="user_dashboard.php?view=profile" <?= ($title === 'User Profile | BatEstate' || $title === 'Profile') ? 'class="active"' : '' ?>><i class="fa-solid fa-user"></i> Profile</a></li>
                    <li><a href="user_dashboard.php?view=search" <?= ($title === 'Search Properties | BatEstate' || $title === 'Search') ? 'class="active"' : '' ?>><i class="fa-solid fa-search"></i> Search Properties</a></li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="../../auth/logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="content-header">
                <h1><?= htmlspecialchars($title ?? 'BatEstate') ?></h1>
                <div class="user-info">
                    <span>Hello, <?= htmlspecialchars($current_user['email'] ?? 'Guest') ?></span>
                </div>
            </header>

            <div class="content-body">
                <?= $content ?>
            </div>
        </div>
    </div>
</body>
</html>
