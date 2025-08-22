<?php
// admin_layout.php

// Requires:
// - $current_user array available
// - $content_html string with the page's main content

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Panel | BatEstate</title>
  <link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_layout.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
</head>
<body>
  <div class="admin-container">
    <div class="sidebar-wrapper collapsed"> <!-- collapsed class added for default demo -->
      <div class="role">   
        <p>Admin</p>
      </div>

      <div class="sidebar">
        <div class="sidebar-header">
          <img src="/BatEstateExplorer/assets/images/Vector 1.png" alt="BatEstate Logo" class="sidebar-logo" />
          <h2>BatEstate Explorer</h2>
        </div>

        <nav class="sidebar-nav">
          <ul>
            <li><a href="admin_dashboard.php?view=dashboard"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a></li>
            <li><a href="admin_dashboard.php?view=direct_agents"><i class="fa-solid fa-user-tie"></i><span>Direct Agents</span></a></li>
            <li><a href="admin_dashboard.php?view=associate_agents"><i class="fa-solid fa-people-group"></i><span>Associate Agents</span></a></li>
            <li><a href="admin_dashboard.php?view=properties"><i class="fa-solid fa-building"></i><span>Property Listings</span></a></li>
            <li><a href="admin_dashboard.php?view=applications"><i class="fa-solid fa-file-signature"></i><span>Applications</span></a></li>
            <li><a href="admin_dashboard.php?view=reports"><i class="fa-solid fa-chart-line"></i><span>Reports</span></a></li>
            <li><a href="admin_dashboard.php?view=performance"><i class="fa-solid fa-ranking-star"></i><span>Performance</span></a></li>
          </ul>
        </nav>

        <!-- Collapse toggle button -->
        <div class="sidebar-toggle">
          <i class="fa-solid fa-chevron-left"></i>
        </div>

        <div class="sidebar-footer">
          <a href="../../auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </div>
      </div>
    </div>

    <div class="main-content">
      

      <?= $content_html ?>

    </div>
  </div>

  <script src="/BatEstateExplorer/assets/js/admin_dashboard.js"></script>
</body>
</html>
