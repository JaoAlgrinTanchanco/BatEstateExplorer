<?php
require_once __DIR__ . '/../../../../components/notification.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/database/cleanup_accounts.php';
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
  <!-- Floating Sidebar Toggle (chevron button) -->
  <div class="sidebar-toggle-btn" aria-label="Toggle sidebar">
    <i class="fa-solid fa-chevron-right"></i>
  </div>

  <div class="admin-container">
    <!-- Sidebar -->
    <div class="sidebar-wrapper collapsed"> <!-- collapsed = default -->
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
            <li><a href="admin_dashboard.php?view=wallet"><i class="fa-solid fa-wallet"></i><span>Wallet</span></a></li>
          </ul>
        </nav>

        <div class="sidebar-footer">
          <a href="javascript:void(0);" id="sidebarLogoutBtn">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
          </a>
        </div>

        <script>
          // Open admin logout modal when sidebar logout clicked
          document.getElementById('sidebarLogoutBtn').addEventListener('click', function() {
            document.getElementById('logoutModalAdmin').classList.add('active-admin');
          });
        </script>

      </div>
    </div>

    <!-- Backdrop (only visible when sidebar is open on mobile/tablet) -->
    <div class="sidebar-backdrop"></div>

    <!-- Main content -->
    <div class="main-content">
      <?= $content_html ?>
    </div>
  </div>

<!-- Logout Confirmation Modal -->
<div id="logoutModalAdmin" class="modal-admin">
  <div class="modal-content-admin">
    <h4>Confirm Logout</h4>
    <p>Are you sure you want to log out of your account?</p>
    <form id="logoutFormAdmin" method="POST" action="../../auth/logout.php">
      <div class="modal-actions-admin">
        <button type="button" id="cancelLogoutAdmin" class="cancel-btn-admin">Cancel</button>
        <button type="submit" class="delete-btn-admin">Yes, Logout</button>
      </div>
      <div id="logoutSpinnerAdmin" class="spinner-admin">
        <div class="loader"></div>
        <span>Logging out...</span>
      </div>
    </form>
  </div>
</div>

<script>
  // Open modal function (if needed)
  function openLogoutModalAdmin() {
    document.getElementById('logoutModalAdmin').classList.add('active-admin');
  }

  // Cancel button closes modal
  document.getElementById('cancelLogoutAdmin').addEventListener('click', function() {
    document.getElementById('logoutModalAdmin').classList.remove('active-admin');
  });

  // Show spinner on form submit
  document.getElementById('logoutFormAdmin').addEventListener('submit', function() {
    document.getElementById('logoutSpinnerAdmin').style.display = 'flex';
  });
</script>

  <script src="/BatEstateExplorer/assets/js/admin_dashboard.js"></script>

</body>
</html>
