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
        <nav class="topbar">
            <div class="topbar-left">
                <h2>🏠 BatEstate</h2>
                <p>User Panel</p>
            </div>

            <ul class="topbar-nav">
                <li>
                    <a href="user_dashboard.php?view=home" <?= ($title === 'User Dashboard | BatEstate') ? 'class="active"' : '' ?>>
                        <i class="fa-solid fa-home"></i> Home
                    </a>
                </li>
                <li>
                    <a href="user_dashboard.php?view=profile" <?= ($title === 'User Profile | BatEstate') ? 'class="active"' : '' ?>>
                        <i class="fa-solid fa-user"></i> Profile
                    </a>
                </li>
                <li>
                    <a href="user_dashboard.php?view=search" <?= ($title === 'Search Properties | BatEstate') ? 'class="active"' : '' ?>>
                        <i class="fa-solid fa-search"></i> Search Properties
                    </a>
                </li>
            </ul>

            <div class="topbar-buttons">
                <a href="user_dashboard.php?view=become_direct_agent">Become Direct Agent</a>
                <a href="user_dashboard.php?view=become_associate_agent">Become Associate Agent</a>
            </div>

            <div class="user-info-logout">
                <span>Hello, <?= htmlspecialchars($current_user['email'] ?? 'Guest') ?></span>
                <a href="../../auth/logout.php" title="Logout"><i class="fa-solid fa-sign-out-alt"></i></a>
            </div>
        </nav>

        <div class="main-content">
            <?= $content ?>
        </div>
    </div>
</body>
</html>
