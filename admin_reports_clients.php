<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

// Redirect if not logged in or not admin
if (!$is_logged_in || !$is_admin) {
    header('Location: login.php');
    exit;
}

// Get client statistics - simplified query without non-existent columns
$query = "SELECT u.first_name, u.last_name, u.email, u.created_at,
          COUNT(DISTINCT m.id) as messages_sent
          FROM users u 
          LEFT JOIN messages m ON u.id = m.sender_id 
          WHERE u.user_type = 'user'
          GROUP BY u.id, u.first_name, u.last_name, u.email, u.created_at
          ORDER BY u.created_at DESC";
$result = mysqli_query($conn, $query);
$client_reports = [];
while ($row = mysqli_fetch_assoc($result)) {
    $client_reports[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Reports (Clients) | BatEstateExplorer</title>
  <link rel="stylesheet" href="admin_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    .reports-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    .sort-row {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 30px;
    }
    .sort-by {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .sort-by label {
      font-weight: 500;
      color: #555;
    }
    .sort-by select {
      padding: 7px 15px;
      border-radius: 20px;
      border: 1px solid #dcdfe3;
      font-size: 0.95rem;
      background: #fff;
      width: 160px;
    }
    .tab-bar {
      display: flex;
      gap: 0;
      margin-bottom: 18px;
      margin-top: 30px;
    }
    .tab {
      padding: 10px 28px;
      border-radius: 20px 20px 0 0;
      background: #f4f7fa;
      color: #222;
      text-decoration: none;
      font-weight: 500;
      border: 1px solid #dcdfe3;
      border-bottom: none;
      margin-right: 2px;
      transition: background 0.2s, color 0.2s;
      cursor: pointer;
    }
    .tab.active {
      background: #fff;
      color: #000;
      border-bottom: 2px solid #fff;
      cursor: default;
      pointer-events: none;
    }
    .tab:not(.active):hover {
      background: #e0e7ef;
      color: #000;
    }
    .report-list {
      display: flex;
      flex-direction: column;
      gap: 25px;
    }
    .report-card {
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      padding: 20px 30px;
      gap: 25px;
      border: 1px solid #e7e7e7;
    }
    .report-image {
      width: 70px;
      height: 55px;
      border-radius: 8px;
      background: #e0e7ef url('Pictures/bg4.jpg') center/cover no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 10px;
    }
    .report-info {
      flex: 1;
    }
    .report-info .report-name {
      font-weight: 600;
      font-size: 1.05rem;
      margin-bottom: 2px;
    }
    .report-info .report-meta {
      color: #888;
      font-size: 0.97rem;
      margin-bottom: 2px;
    }
    .report-info .report-meta span {
      margin-right: 12px;
    }
    .report-actions {
      display: flex;
      gap: 10px;
    }
    .report-actions button {
      padding: 8px 18px;
      border-radius: 20px;
      border: none;
      font-weight: 500;
      font-size: 0.97rem;
      cursor: pointer;
      background: #f4f7fa;
      color: #222;
      transition: background 0.2s, color 0.2s;
    }
    .report-actions button:hover {
      background: #000;
      color: #fff;
    }
    .report-actions .remove-btn {
      background: #fff0f0;
      color: #c00;
      border: 1px solid #f5c2c2;
    }
    .report-actions .remove-btn:hover {
      background: #c00;
      color: #fff;
      border: 1px solid #c00;
    }
    .no-reports {
      text-align: center;
      padding: 40px;
      color: #666;
      font-size: 1.1rem;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">BatEstateExplorer</div>
        <h3>Hi, Admin!</h3>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="admin_dashboard_new.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
          <li class="nav-dropdown">
            <a href="#" class="dropdown-toggle"><i class="fa-solid fa-users"></i> Manage Accounts <i class="fa-solid fa-chevron-down dropdown-icon"></i></a>
            <ul class="dropdown-menu">
              <li><a href="admin_direct_agents.php">Direct Agents</a></li>
              <li><a href="admin_associate_agents.php">Associate Agents</a></li>
            </ul>
          </li>

          <li><a href="admin_property_listings.php"><i class="fa-solid fa-house-chimney"></i> Property Listings</a></li>
          <li><a href="admin_applications.php"><i class="fa-solid fa-file-lines"></i> Applications</a></li>
          <li><a href="admin_reports.php"><i class="fa-solid fa-flag"></i> Reports</a></li>
          <li><a href="admin_performance.php"><i class="fa-solid fa-chart-bar"></i> Performance</a></li>
        </ul>
      </nav>
    </aside>
    <!-- Main Content -->
    <main class="main-content">
      <header class="main-header reports-header">
        <h1>Reports</h1>
        <div class="header-actions">
          <a href="logout.php"><button class="logout-btn">Log Out</button></a>
          <div class="profile-btn">
            <i class="fa-solid fa-user"></i>
          </div>
        </div>
      </header>
      <div class="sort-row">
        <div class="sort-by">
          <label for="sort">Sort By:</label>
          <select id="sort">
            <option value="name">Name</option>
            <option value="type">Type</option>
            <option value="price">Price</option>
            <option value="date">Date Uploaded</option>
          </select>
        </div>
      </div>
      <!-- Tab Bar -->
      <div class="tab-bar">
        <a href="admin_reports.php" class="tab">Direct Agent</a>
        <a href="admin_reports_agents.php" class="tab">Associate Agent</a>
        <span class="tab active">Clients</span>
      </div>
      <div class="report-list" id="clientReports">
        <?php if (empty($client_reports)): ?>
          <div class="no-reports">No client reports available</div>
        <?php else: ?>
          <?php foreach ($client_reports as $client): ?>
            <div class="report-card" data-name="<?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>" data-type="User" data-date="<?php echo $client['created_at']; ?>">
              <div class="report-image"></div>
              <div class="report-info">
                <div class="report-name"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></div>
                <div class="report-meta">
                  <span>Email: <?php echo htmlspecialchars($client['email']); ?></span>
                  <span>Messages: <?php echo $client['messages_sent']; ?></span>
                  <span>Joined: <?php echo date('M d, Y', strtotime($client['created_at'])); ?></span>
                </div>
              </div>
              <div class="report-actions">
                <button>View Report</button>
                <button class="remove-btn">Remove User</button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
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
    // Sort By Dropdown interactivity
    const sortSelect = document.getElementById('sort');
    const clientReports = document.getElementById('clientReports');
    function sortReports(criteria) {
      const cards = Array.from(clientReports.querySelectorAll('.report-card'));
      if (cards.length > 0) {
        let sorted;
        if (criteria === 'name') {
          sorted = cards.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
        } else if (criteria === 'type') {
          sorted = cards.sort((a, b) => a.dataset.type.localeCompare(b.dataset.type));
        } else if (criteria === 'price') {
          sorted = cards.sort((a, b) => a.dataset.price - b.dataset.price);
        } else if (criteria === 'date') {
          sorted = cards.sort((a, b) => new Date(b.dataset.date) - new Date(a.dataset.date));
        }
        sorted.forEach(card => clientReports.appendChild(card));
      }
    }
    sortSelect.addEventListener('change', function() {
      sortReports(this.value);
    });
  </script>
</body>
</html> 