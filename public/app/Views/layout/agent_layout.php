<?php
if (!isset($page_title)) $page_title = "Agent Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="main-header">
  <div class="logo">BatEstate Agent</div>
  <nav class="main-nav">
    <?php if ($user['user_type'] === 'associate_agent'): ?>
      <a href="agent_dashboard.php?view=associate_home" class="<?= ($view === 'associate_home') ? 'active' : '' ?>">
        <i class="fa-solid fa-house"></i> Home
      </a>
      <a href="agent_dashboard.php?view=associate_profile" class="<?= ($view === 'associate_profile') ? 'active' : '' ?>">
        <i class="fa-solid fa-user"></i> Profile
      </a>
      <a href="agent_dashboard.php?view=associate_search" class="<?= ($view === 'associate_search') ? 'active' : '' ?>">
        <i class="fa-solid fa-search"></i> Search Properties
      </a>
    <?php else: ?>
      <!-- Future: direct_agent nav here -->
    <?php endif; ?>
    <a href="/BatEstateExplorer/public/auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </nav>
</header>

<main class="dashboard-content">
  <?php require $view_file; ?>
</main>

</body>
</html>
