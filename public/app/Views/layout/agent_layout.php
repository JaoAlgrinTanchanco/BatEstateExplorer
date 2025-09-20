<?php
if (!isset($page_title)) $page_title = "Agent Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/agent_layout.css">
  <link rel="stylesheet" href="../../assets/css/property_card.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

<nav class="navbar">
  <div class="nav-container">
    
    <!-- Left -->
    <div class="nav-logo">
      <img src="/BatEstateExplorer/assets/images/vector 1.png" 
           alt="BatEstate Explorer Logo" 
           class="nav-logo-img">
      <span>BatEstate Explorer</span>
      <?php if (isset($user['user_type'])): ?>
        <div class="agent-role">
          (<?= $user['user_type'] === 'direct_agent' ? 'Direct' : 'Associate' ?>)
        </div>
      <?php endif; ?>
    </div>

    <!-- Center -->
    <div class="nav-center">
      <?php if ($user['user_type'] === 'associate_agent'): ?>
        <a href="agent_dashboard.php?view=associate_home" class="nav-link <?= ($view === 'associate_home') ? 'active' : '' ?>">
          <i class="fa-solid fa-house"></i> Home
        </a>
        <a href="agent_dashboard.php?view=associate_profile" class="nav-link <?= ($view === 'associate_profile') ? 'active' : '' ?>">
          <i class="fa-solid fa-user"></i> Profile
        </a>
        <a href="agent_dashboard.php?view=associate_search" class="nav-link <?= ($view === 'associate_search') ? 'active' : '' ?>">
          <i class="fa-solid fa-search"></i> Search
        </a>
      <?php elseif ($user['user_type'] === 'direct_agent'): ?>
        <a href="agent_dashboard.php?view=direct_home" class="nav-link <?= ($view === 'direct_home') ? 'active' : '' ?>">
          <i class="fa-solid fa-house"></i> Home
        </a>
        <a href="agent_dashboard.php?view=direct_profile" class="nav-link <?= ($view === 'direct_profile') ? 'active' : '' ?>">
          <i class="fa-solid fa-user"></i> Profile
        </a>
        <a href="agent_dashboard.php?view=direct_search" class="nav-link <?= ($view === 'direct_search') ? 'active' : '' ?>">
          <i class="fa-solid fa-search"></i> Search
        </a>
      <?php endif; ?>
    </div>

    <!-- Right -->
    <div class="nav-right">
      <a href="/BatEstateExplorer/public/agent_message.php" target="_blank" class="glow-link">
        <i class="fa-solid fa-envelope"></i> Messages
      </a>
      <form action="../../auth/logout.php" method="POST" style="display:inline;">
        <button type="submit" class="logout-btn">
          <i class="fa-solid fa-right-from-bracket"></i> Logout
        </button>
      </form>
    </div>

  </div>
</nav>

<main class="dashboard-content">
  <?php require $view_file; ?>
</main>
<script src="/BatEstateExplorer/assets/js/agents.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
