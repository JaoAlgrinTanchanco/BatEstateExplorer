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
    <link rel="stylesheet" href="../../assets/css/property_card.css">
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

                <!-- Message Icon -->
                <a href="http://localhost/BatEstateExplorer/public/message.php" 
                title="Messages" 
                target="_blank"
                style="
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        width:40px;
                        height:40px;
                        border-radius:50%;
                        background-color:#007bff;
                        color:white;
                        text-decoration:none;
                        margin-left:10px;
                        font-size:1.2rem;
                        transition:background 0.2s;
                    "
                onmouseover="this.style.backgroundColor='#0056b3';"
                onmouseout="this.style.backgroundColor='#007bff';"
                >
                    <i class="fa-solid fa-message"></i>
                </a>
            </div>

            <div class="user-info-logout">
                <form action="../../auth/logout.php" method="POST" class="logout-form" style="display:inline;">
                    <button type="submit" class="logout-button" title="Logout" style="
                        background-color: #dc3545;
                        border: none;
                        color: white;
                        padding: 0.4rem 0.8rem;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 1rem;
                        font-family: inherit;
                    ">
                        Logout
                    </button>
                </form>
            </div>
        </nav>

        <div class="main-content" style="padding: 60px;">
            <?= $content ?>
        </div>

             <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; <?= date('Y') ?> BatEstate Explorer. All rights reserved.</p>
            <ul class="footer-links">
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
                <li><a href="#">Contact Us</a></li>
            </ul>
        </div>
    </footer>
</div>

<!-- ✅ Global JS config for all views -->
<script>
    window.AppConfig = {
        userToken: "<?= $_SESSION['user']['token'] ?? '' ?>",
        userId: <?= (int)($_SESSION['user']['id'] ?? 0) ?>,
        userType: "<?= $_SESSION['user']['user_type'] ?? '' ?>"
    };
</script>

<!-- Load page-specific JS after config -->
<?php if (!empty($pageScript)): ?>
    <script src="<?= htmlspecialchars($pageScript) ?>"></script>
<?php endif; ?>

</body>
</html>
