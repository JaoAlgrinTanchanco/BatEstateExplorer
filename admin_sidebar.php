<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="logo">BatEstateExplorer</div>
    <h3>Hi, Admin!</h3>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <li><a href="admin_dashboard_new.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
      <li class="nav-dropdown open">
        <a href="#" class="dropdown-toggle"><i class="fa-solid fa-users"></i> Manage Accounts <i class="fa-solid fa-chevron-down dropdown-icon"></i></a>
        <ul class="dropdown-menu">
          <li><a href="admin_direct_agents.php">Direct Agents</a></li>
          <li><a href="admin_associate_agents.php">Associate Agent</a></li>
        </ul>
      </li>
      <li><a href="admin_property_listings.php"><i class="fa-solid fa-house-chimney"></i> Property Listings</a></li>
      <li><a href="admin_applications.php" class="active"><i class="fa-solid fa-file-lines"></i> Applications</a></li>
      <li><a href="admin_reports.php"><i class="fa-solid fa-flag"></i> Reports</a></li>
      <li><a href="admin_performance.php"><i class="fa-solid fa-chart-bar"></i> Performance</a></li>
    </ul>
  </nav>
</aside>

<!-- Main Header -->
<header class="main-header">
  <div class="header-actions">
    <a href="logout.php"><button class="logout-btn">Log Out</button></a>
    <div class="profile-btn">
      <i class="fa-solid fa-user"></i>
    </div>
  </div>
</header>

<script>
// Sidebar navigation interactivity
document.querySelectorAll('.sidebar-nav a').forEach(link => {
  link.addEventListener('click', function(e) {
    if (!this.classList.contains('dropdown-toggle')) {
      document.querySelectorAll('.sidebar-nav a').forEach(l => l.classList.remove('active'));
      this.classList.add('active');
    }
  });
});

// Dropdown toggle
document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
  toggle.addEventListener('click', function(e) {
    e.preventDefault();
    this.parentElement.classList.toggle('open');
  });
});
</script> 