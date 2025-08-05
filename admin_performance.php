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

// Get performance statistics from database
$stats = [];

// Most selling property
$query = "SELECT p.title, COUNT(*) as sales_count 
          FROM properties p 
          WHERE p.status = 'sold' 
          GROUP BY p.id, p.title 
          ORDER BY sales_count DESC 
          LIMIT 1";
$result = mysqli_query($conn, $query);
$most_selling_property = mysqli_fetch_assoc($result);
$stats['most_selling_property'] = $most_selling_property ? $most_selling_property['title'] : 'No data';

// Top performing agent
$query = "SELECT u.first_name, u.last_name, COUNT(p.id) as properties_sold
          FROM users u 
          LEFT JOIN agents a ON u.id = a.user_id 
          LEFT JOIN properties p ON a.id = p.agent_id AND p.status = 'sold'
          WHERE u.user_type IN ('direct_agent', 'associate_agent')
          GROUP BY u.id, u.first_name, u.last_name
          ORDER BY properties_sold DESC
          LIMIT 1";
$result = mysqli_query($conn, $query);
$top_agent = mysqli_fetch_assoc($result);
$stats['top_agent'] = $top_agent ? $top_agent['first_name'] . ' ' . $top_agent['last_name'] : 'No data';

// Properties sold by agents
$query = "SELECT u.first_name, u.last_name, COUNT(p.id) as properties_sold
          FROM users u 
          LEFT JOIN agents a ON u.id = a.user_id 
          LEFT JOIN properties p ON a.id = p.agent_id AND p.status = 'sold'
          WHERE u.user_type IN ('direct_agent', 'associate_agent')
          GROUP BY u.id, u.first_name, u.last_name
          ORDER BY properties_sold DESC
          LIMIT 5";
$result = mysqli_query($conn, $query);
$agent_performance = [];
while ($row = mysqli_fetch_assoc($result)) {
    $agent_performance[] = $row;
}

// Most selling properties
$query = "SELECT p.title, COUNT(*) as units_sold
          FROM properties p 
          WHERE p.status = 'sold' 
          GROUP BY p.id, p.title 
          ORDER BY units_sold DESC 
          LIMIT 5";
$result = mysqli_query($conn, $query);
$most_selling_properties = [];
while ($row = mysqli_fetch_assoc($result)) {
    $most_selling_properties[] = $row;
}

// Company properties sold
$query = "SELECT c.name, COUNT(p.id) as properties_sold
          FROM companies c 
          LEFT JOIN agents a ON c.id = a.company_id 
          LEFT JOIN properties p ON a.id = p.agent_id AND p.status = 'sold'
          GROUP BY c.id, c.name
          ORDER BY properties_sold DESC
          LIMIT 3";
$result = mysqli_query($conn, $query);
$company_performance = [];
while ($row = mysqli_fetch_assoc($result)) {
    $company_performance[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Performance | BatEstateExplorer</title>
  <link rel="stylesheet" href="admin_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .performance-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    .performance-graph-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 400px;
      margin-top: 40px;
    }
    .bar-graph {
      display: flex;
      align-items: flex-end;
      gap: 40px;
      height: 260px;
      margin-bottom: 30px;
    }
    .bar {
      width: 40px;
      background: #222;
      border-radius: 8px 8px 0 0;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      transition: background 0.2s;
    }
    .bar-label {
      text-align: center;
      margin-top: 8px;
      font-size: 1rem;
      color: #333;
    }
    .performance-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 0;
    }
    .metrics-summary {
      display: flex;
      justify-content: center;
      gap: 40px;
      margin-bottom: 30px;
      margin-top: 10px;
    }
    .metric-box {
      background: #f4f7fa;
      border-radius: 12px;
      padding: 18px 32px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      text-align: center;
      border: 1px solid #e7e7e7;
      min-width: 180px;
    }
    .metric-title {
      font-size: 1.05rem;
      color: #888;
      margin-bottom: 6px;
    }
    .metric-value {
      font-size: 1.7rem;
      font-weight: 600;
      color: #222;
    }
    .metric-highlight {
      color: #1a7f1a;
      font-weight: 700;
    }
    .metric-danger {
      color: #c00;
      font-weight: 700;
    }
    .metric-property {
      color: #0074d9;
      font-weight: 700;
    }
    .chart-container {
      width: 100%;
      max-width: 700px;
      margin: 0 auto;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      padding: 32px 24px 24px 24px;
    }
    .no-data {
      text-align: center;
      padding: 40px;
      color: #666;
      font-size: 1.1rem;
    }
    .chart-section {
      margin-top: 48px;
      width: 100%;
      max-width: 700px;
    }
    .chart-section h2 {
      font-size: 1.2rem;
      font-weight: 600;
      color: #222;
      margin-bottom: 18px;
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
          <li><a href="admin_performance.php" class="active"><i class="fa-solid fa-chart-bar"></i> Performance</a></li>
        </ul>
      </nav>
    </aside>
    <!-- Main Content -->
    <main class="main-content">
      <header class="main-header performance-header">
        <h1 class="performance-title">Performance</h1>
        <div class="header-actions">
          <a href="logout.php"><button class="logout-btn">Log Out</button></a>
          <div class="profile-btn">
            <i class="fa-solid fa-user"></i>
          </div>
        </div>
      </header>
      <div class="performance-graph-container">
        <div class="metrics-summary">
          <div class="metric-box">
            <div class="metric-title">Most Selling Property</div>
            <div class="metric-value metric-property"><?php echo htmlspecialchars($stats['most_selling_property']); ?></div>
          </div>
          <div class="metric-box">
            <div class="metric-title">Top Performing Agent</div>
            <div class="metric-value metric-highlight"><?php echo htmlspecialchars($stats['top_agent']); ?></div>
          </div>
        </div>
        
        <div class="chart-section">
          <h2>Properties Sold by Agents</h2>
          <div class="chart-container">
            <?php if (empty($agent_performance)): ?>
              <div class="no-data">No performance data available</div>
            <?php else: ?>
              <canvas id="performanceChart" height="260"></canvas>
            <?php endif; ?>
          </div>
        </div>
        
        <div class="chart-section">
          <h2>Most Selling Properties</h2>
          <div class="chart-container">
            <?php if (empty($most_selling_properties)): ?>
              <div class="no-data">No property sales data available</div>
            <?php else: ?>
              <canvas id="propertyChart" height="260"></canvas>
            <?php endif; ?>
          </div>
        </div>
        
        <div class="chart-section">
          <h2>Company Properties Sold</h2>
          <div class="chart-container">
            <?php if (empty($company_performance)): ?>
              <div class="no-data">No company performance data available</div>
            <?php else: ?>
              <canvas id="companyChart" height="260"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
  <script>
    <?php if (!empty($agent_performance)): ?>
    // Chart.js interactive bar chart
    const ctx = document.getElementById('performanceChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: [<?php echo implode(',', array_map(function($agent) { return '"' . $agent['first_name'] . ' ' . $agent['last_name'] . '"'; }, $agent_performance)); ?>],
        datasets: [{
          label: 'Properties Sold',
          data: [<?php echo implode(',', array_map(function($agent) { return $agent['properties_sold']; }, $agent_performance)); ?>],
          backgroundColor: [
            <?php 
            $colors = ['#1a7f1a', '#bbb', '#bbb', '#bbb', '#bbb'];
            echo implode(',', array_slice($colors, 0, count($agent_performance)));
            ?>
          ],
          borderRadius: 8,
          hoverBackgroundColor: [
            <?php 
            $hoverColors = ['#2ecc40', '#888', '#888', '#888', '#888'];
            echo implode(',', array_slice($hoverColors, 0, count($agent_performance)));
            ?>
          ]
        }]
      },
      options: {
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return `Properties Sold: ${context.parsed.y}`;
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#333', font: { size: 14 } },
            title: {
              display: true,
              text: 'Agents',
              color: '#333',
              font: { size: 16, weight: 'bold' },
              padding: { top: 12 }
            }
          },
          y: {
            beginAtZero: true,
            grid: { color: '#eee' },
            ticks: { color: '#888', font: { size: 13 } },
            title: {
              display: true,
              text: 'Properties Sold',
              color: '#333',
              font: { size: 16, weight: 'bold' },
              padding: { bottom: 12 }
            }
          }
        }
      }
    });
    <?php endif; ?>
    
    <?php if (!empty($most_selling_properties)): ?>
    // Most Selling Properties Chart
    const ctx2 = document.getElementById('propertyChart').getContext('2d');
    new Chart(ctx2, {
      type: 'bar',
      data: {
        labels: [<?php echo implode(',', array_map(function($property) { return '"' . $property['title'] . '"'; }, $most_selling_properties)); ?>],
        datasets: [{
          label: 'Units Sold',
          data: [<?php echo implode(',', array_map(function($property) { return $property['units_sold']; }, $most_selling_properties)); ?>],
          backgroundColor: [
            <?php 
            $colors = ['#0074d9', '#bbb', '#bbb', '#bbb', '#bbb'];
            echo implode(',', array_slice($colors, 0, count($most_selling_properties)));
            ?>
          ],
          borderRadius: 8,
          hoverBackgroundColor: [
            <?php 
            $hoverColors = ['#339af0', '#888', '#888', '#888', '#888'];
            echo implode(',', array_slice($hoverColors, 0, count($most_selling_properties)));
            ?>
          ]
        }]
      },
      options: {
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return `Units Sold: ${context.parsed.y}`;
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#333', font: { size: 14 } },
            title: {
              display: true,
              text: 'Properties',
              color: '#333',
              font: { size: 16, weight: 'bold' },
              padding: { top: 12 }
            }
          },
          y: {
            beginAtZero: true,
            grid: { color: '#eee' },
            ticks: { color: '#888', font: { size: 13 } },
            title: {
              display: true,
              text: 'Units Sold',
              color: '#333',
              font: { size: 16, weight: 'bold' },
              padding: { bottom: 12 }
            }
          }
        }
      }
    });
    <?php endif; ?>
    
    <?php if (!empty($company_performance)): ?>
    // Company Properties Sold Chart
    const ctx3 = document.getElementById('companyChart').getContext('2d');
    new Chart(ctx3, {
      type: 'bar',
      data: {
        labels: [<?php echo implode(',', array_map(function($company) { return '"' . $company['name'] . '"'; }, $company_performance)); ?>],
        datasets: [{
          label: 'Properties Sold',
          data: [<?php echo implode(',', array_map(function($company) { return $company['properties_sold']; }, $company_performance)); ?>],
          backgroundColor: [
            <?php 
            $colors = ['#0074d9', '#1a7f1a', '#bbb'];
            echo implode(',', array_slice($colors, 0, count($company_performance)));
            ?>
          ],
          borderRadius: 8,
          hoverBackgroundColor: [
            <?php 
            $hoverColors = ['#339af0', '#2ecc40', '#888'];
            echo implode(',', array_slice($hoverColors, 0, count($company_performance)));
            ?>
          ]
        }]
      },
      options: {
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return `Properties Sold: ${context.parsed.y}`;
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#333', font: { size: 14 } },
            title: {
              display: true,
              text: 'Companies',
              color: '#333',
              font: { size: 16, weight: 'bold' },
              padding: { top: 12 }
            }
          },
          y: {
            beginAtZero: true,
            grid: { color: '#eee' },
            ticks: { color: '#888', font: { size: 13 } },
            title: {
              display: true,
              text: 'Properties Sold',
              color: '#333',
              font: { size: 16, weight: 'bold' },
              padding: { bottom: 12 }
            }
          }
        }
      }
    });
    <?php endif; ?>
  </script>
</body>
</html> 