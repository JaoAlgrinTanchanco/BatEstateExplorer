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
    header('Location: admin_login.php');
    exit;
}

// Get properties grouped by agent
$query = "SELECT u.first_name, u.last_name, u.email, u.user_type, c.name as company_name,
          COUNT(p.id) as property_count, 
          SUM(CASE WHEN p.status = 'available' THEN 1 ELSE 0 END) as available_count,
          SUM(CASE WHEN p.status = 'sold' THEN 1 ELSE 0 END) as sold_count
          FROM users u 
          LEFT JOIN agents a ON u.id = a.user_id 
          LEFT JOIN companies c ON a.company_id = c.id 
          LEFT JOIN properties p ON a.id = p.agent_id 
          WHERE u.user_type IN ('direct_agent', 'associate_agent')
          GROUP BY u.id, u.first_name, u.last_name, u.email, u.user_type, c.name
          ORDER BY property_count DESC";
$result = mysqli_query($conn, $query);
$agent_properties = [];
while ($row = mysqli_fetch_assoc($result)) {
    $agent_properties[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Property Listings (Associate Agent) | BatEstateExplorer</title>
  <link rel="stylesheet" href="admin_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    .property-listings-header {
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
    .property-section-title {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 18px;
      margin-top: 30px;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .property-list {
      display: flex;
      flex-direction: column;
      gap: 25px;
    }
    .property-card {
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      padding: 20px 30px;
      gap: 25px;
      border: 1px solid #e7e7e7;
    }
    .property-image {
      width: 70px;
      height: 55px;
      border-radius: 8px;
      background: #e0e7ef url('Pictures/bg4.jpg') center/cover no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 10px;
    }
    .property-info {
      flex: 1;
    }
    .property-info .property-name {
      font-weight: 600;
      font-size: 1.05rem;
      margin-bottom: 2px;
    }
    .property-info .property-meta {
      color: #888;
      font-size: 0.97rem;
      margin-bottom: 2px;
    }
    .property-info .property-meta span {
      margin-right: 12px;
    }
    .property-actions {
      display: flex;
      gap: 10px;
    }
    .property-actions button {
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
    .property-actions button:hover {
      background: #000;
      color: #fff;
    }
    .property-actions .remove-btn {
      background: #fff0f0;
      color: #c00;
      border: 1px solid #f5c2c2;
    }
    .property-actions .remove-btn:hover {
      background: #c00;
      color: #fff;
      border: 1px solid #c00;
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
      <header class="main-header property-listings-header">
        <h1>Property Listings</h1>
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
        <a href="admin_property_listings.php" class="tab">Direct Agent</a>
        <span class="tab active">Associate Agent</span>
      </div>
      <div class="property-list" id="realEstateAgentsList">
        <div class="property-card" data-name="Skyline Condo" data-type="Condo" data-price="2500000" data-date="2024-03-20">
          <div class="property-image"></div>
          <div class="property-info">
            <div class="property-name">Skyline Condo</div>
            <div class="property-meta">
              <span>Type: Condo</span>
              <span>₱2,500,000</span>
              <span>Date: 2024-03-20</span>
            </div>
          </div>
          <div class="property-actions">
            <button>View Post</button>
            <button>View Property Document</button>
            <button class="remove-btn">Remove Post</button>
          </div>
        </div>
        <div class="property-card" data-name="Lakeside House" data-type="House" data-price="4100000" data-date="2024-02-10">
          <div class="property-image"></div>
          <div class="property-info">
            <div class="property-name">Lakeside House</div>
            <div class="property-meta">
              <span>Type: House</span>
              <span>₱4,100,000</span>
              <span>Date: 2024-02-10</span>
            </div>
          </div>
          <div class="property-actions">
            <button>View Post</button>
            <button>View Property Document</button>
            <button class="remove-btn">Remove Post</button>
          </div>
        </div>
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
    const realEstateAgentsList = document.getElementById('realEstateAgentsList');
    function sortProperties(criteria) {
      const cards = Array.from(realEstateAgentsList.querySelectorAll('.property-card'));
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
      sorted.forEach(card => realEstateAgentsList.appendChild(card));
    }
    sortSelect.addEventListener('change', function() {
      sortProperties(this.value);
    });
  </script>
</body>
</html> 