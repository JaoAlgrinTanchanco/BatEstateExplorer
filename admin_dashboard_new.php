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

// Get dashboard statistics
$stats = [];

// Total properties
$query = "SELECT COUNT(*) as count FROM properties";
$result = mysqli_query($conn, $query);
$stats['total_listings'] = mysqli_fetch_assoc($result)['count'];

// Pending applications
$query = "SELECT COUNT(*) as count FROM applications WHERE status = 'pending'";
$result = mysqli_query($conn, $query);
$stats['pending_applications'] = mysqli_fetch_assoc($result)['count'];

// Pending verifications (properties pending)
$query = "SELECT COUNT(*) as count FROM properties WHERE status = 'pending'";
$result = mysqli_query($conn, $query);
$stats['pending_verifications'] = mysqli_fetch_assoc($result)['count'];

// Associate agents (only approved)
$query = "SELECT COUNT(*) as count FROM agents a 
          JOIN users u ON a.user_id = u.id 
          WHERE u.user_type = 'associate_agent' AND u.status = 'active'";
$result = mysqli_query($conn, $query);
$stats['associate_agents'] = mysqli_fetch_assoc($result)['count'];

// Total agents (only approved)
$query = "SELECT COUNT(*) as count FROM users WHERE user_type IN ('direct_agent', 'associate_agent') AND status = 'active'";
$result = mysqli_query($conn, $query);
$stats['total_agents'] = mysqli_fetch_assoc($result)['count'];

// Direct agents (only approved)
$query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'direct_agent' AND status = 'active'";
$result = mysqli_query($conn, $query);
$stats['direct_agents'] = mysqli_fetch_assoc($result)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | BatEstateExplorer</title>
  <link rel="stylesheet" href="admin_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
</head>
<body>

  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">BatEstateExplorer</div>
        <h3>Hi, <?php echo htmlspecialchars($current_user['first_name']); ?>!</h3>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="admin_dashboard_new.php" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
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
      <header class="main-header">
        <h1>Admin Dashboard</h1>
        <div class="header-actions">
          <a href="logout.php"><button class="logout-btn">Log Out</button></a>
          <div class="profile-btn">
            <i class="fa-solid fa-user"></i>
          </div>
        </div>
      </header>

      <section class="info-section">
        <h2>Overall Information</h2>
        <div class="info-cards">
          <div class="card">
            <h3><?php echo $stats['total_listings']; ?></h3>
            <p>Total Listings</p>
          </div>
          <div class="card">
            <h3><?php echo $stats['pending_applications']; ?></h3>
            <p>Pending Application</p>
          </div>
          <div class="card">
            <h3><?php echo $stats['pending_verifications']; ?></h3>
            <p>Pending Verification</p>
          </div>
          <div class="card">
            <h3><?php echo $stats['associate_agents']; ?></h3>
            <p>Associate Agent</p>
          </div>
          <div class="card">
            <h3><?php echo $stats['total_agents']; ?></h3>
            <p>Agents</p>
          </div>
          <div class="card">
            <h3><?php echo $stats['direct_agents']; ?></h3>
            <p>Direct Agents</p>
          </div>
        </div>
      </section>

      <!-- Recent Activity Section -->
      <section class="recent-activity">
        <h2>Recent Activity</h2>
        <div class="activity-list">
          <?php
          // Get recent applications
          $query = "SELECT a.*, u.first_name, u.last_name, u.email 
                    FROM applications a 
                    JOIN users u ON a.user_id = u.id 
                    ORDER BY a.created_at DESC 
                    LIMIT 5";
          $result = mysqli_query($conn, $query);
          
          if (mysqli_num_rows($result) > 0) {
            while ($app = mysqli_fetch_assoc($result)) {
              $status_class = $app['status'] === 'pending' ? 'pending' : ($app['status'] === 'approved' ? 'approved' : 'rejected');
              echo '<div class="activity-item ' . $status_class . '">';
              echo '<div class="activity-icon"><i class="fa-solid fa-user-plus"></i></div>';
              echo '<div class="activity-content">';
              echo '<h4>' . htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) . '</h4>';
              echo '<p>Applied as ' . ucfirst(str_replace('_', ' ', $app['agent_type'])) . '</p>';
              echo '<span class="activity-time">' . date('M j, Y g:i A', strtotime($app['created_at'])) . '</span>';
              echo '</div>';
              echo '<div class="activity-status ' . $status_class . '">' . ucfirst($app['status']) . '</div>';
              echo '</div>';
            }
          } else {
            echo '<p class="no-activity">No recent activity</p>';
          }
          ?>
        </div>
      </section>
    </main>
  </div>

  <script>
    // Dropdown toggle - works from any page
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const dropdown = this.parentElement;
        
        // Toggle current dropdown
        dropdown.classList.toggle('open');
      });
    });

    // Auto-refresh dashboard stats every 30 seconds
    setInterval(function() {
      location.reload();
    }, 30000);
  </script>
</body>
</html> 