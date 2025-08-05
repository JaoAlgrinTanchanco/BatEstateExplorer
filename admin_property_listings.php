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

// Get all properties with agent information
$query = "SELECT p.*, u.first_name, u.last_name, u.email, c.name as company_name 
          FROM properties p 
          LEFT JOIN agents a ON p.agent_id = a.id 
          LEFT JOIN users u ON a.user_id = u.id 
          LEFT JOIN companies c ON a.company_id = c.id 
          ORDER BY p.created_at DESC";
$result = mysqli_query($conn, $query);
$properties = [];
while ($row = mysqli_fetch_assoc($result)) {
    $properties[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Property Listings | BatEstateExplorer</title>
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

          <li><a href="#" class="active"><i class="fa-solid fa-house-chimney"></i> Property Listings</a></li>
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
        <span class="tab active">Direct Agent</span>
        <a href="admin_property_listings_agents.php" class="tab">Associate Agent</a>
      </div>
      <div class="property-list" id="propertyOwnersList">
        <div class="property-card" data-name="Sunrise Villa" data-type="House" data-price="3500000" data-date="2024-05-01">
          <div class="property-image"></div>
          <div class="property-info">
            <div class="property-name">Sunrise Villa</div>
            <div class="property-meta">
              <span>Type: House</span>
              <span>₱3,500,000</span>
              <span>Date: 2024-05-01</span>
            </div>
          </div>
          <div class="property-actions">
            <button onclick="viewPost(this)">View Post</button>
            <button onclick="viewPropertyDocument(this)">View Property Document</button>
            <button class="remove-btn" onclick="removePost(this)">Remove Post</button>
          </div>
        </div>
        <div class="property-card" data-name="Greenfield Lot" data-type="Lot" data-price="1200000" data-date="2024-04-15">
          <div class="property-image"></div>
          <div class="property-info">
            <div class="property-name">Greenfield Lot</div>
            <div class="property-meta">
              <span>Type: Lot</span>
              <span>₱1,200,000</span>
              <span>Date: 2024-04-15</span>
            </div>
          </div>
          <div class="property-actions">
            <button onclick="viewPost(this)">View Post</button>
            <button onclick="viewPropertyDocument(this)">View Property Document</button>
            <button class="remove-btn" onclick="removePost(this)">Remove Post</button>
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
    const allPropertyLists = [
      document.getElementById('propertyOwnersList'),
      document.getElementById('realEstateAgentsList')
    ];
    function sortProperties(criteria) {
      allPropertyLists.forEach(list => {
        const cards = Array.from(list.querySelectorAll('.property-card'));
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
        sorted.forEach(card => list.appendChild(card));
      });
    }
    sortSelect.addEventListener('change', function() {
      sortProperties(this.value);
    });

    // Property management functions
    function viewPost(btn) {
      const card = btn.closest('.property-card');
      const propName = card.querySelector('.property-name').textContent;
      const propType = card.querySelector('.property-meta span').textContent.replace('Type: ', '');
      const propPrice = card.querySelector('.property-meta span:nth-child(2)').textContent;
      const propDate = card.querySelector('.property-meta span:nth-child(3)').textContent.replace('Date: ', '');
      
      // Create modal content
      const modalContent = `
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #222;">Property Post - ${propName}</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
              <h3 style="color: #333; margin-bottom: 15px;">Property Details</h3>
              <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <p><strong>Property Name:</strong> ${propName}</p>
                <p><strong>Property Type:</strong> ${propType}</p>
                <p><strong>Price:</strong> ${propPrice}</p>
                <p><strong>Date Posted:</strong> ${propDate}</p>
                <p><strong>Location:</strong> Batangas City</p>
                <p><strong>Bedrooms:</strong> 3</p>
                <p><strong>Bathrooms:</strong> 2</p>
                <p><strong>Size:</strong> 180 sqm</p>
              </div>
            </div>
            <div>
              <h3 style="color: #333; margin-bottom: 15px;">Property Images</h3>
              <div style="background: #e0e7ef; height: 200px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #666;">
                Property Images Preview
              </div>
            </div>
          </div>
          <div style="margin-top: 20px;">
            <h3 style="color: #333; margin-bottom: 15px;">Property Description</h3>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
              <p>Beautiful ${propType.toLowerCase()} located in a prime area. This property features modern amenities, spacious rooms, and excellent location. Perfect for families looking for a comfortable home in Batangas.</p>
            </div>
          </div>
        </div>
      `;
      
      showModal(modalContent);
    }

    function viewPropertyDocument(btn) {
      const card = btn.closest('.property-card');
      const propName = card.querySelector('.property-name').textContent;
      
      // Create modal content
      const modalContent = `
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #222;">Property Documents - ${propName}</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
          </div>
          <div style="margin-bottom: 20px;">
            <h3 style="color: #333; margin-bottom: 15px;">Submitted Documents</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
              <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fa-solid fa-file-pdf" style="color: #c00; margin-right: 10px;"></i>Title Deed</span>
                <button style="background: #0074d9; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">View</button>
              </div>
              <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fa-solid fa-file-pdf" style="color: #c00; margin-right: 10px;"></i>Tax Declaration</span>
                <button style="background: #0074d9; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">View</button>
              </div>
              <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fa-solid fa-file-pdf" style="color: #c00; margin-right: 10px;"></i>Building Permit</span>
                <button style="background: #0074d9; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">View</button>
              </div>
              <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fa-solid fa-file-pdf" style="color: #c00; margin-right: 10px;"></i>Certificate of Occupancy</span>
                <button style="background: #0074d9; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">View</button>
              </div>
            </div>
          </div>
        </div>
      `;
      
      showModal(modalContent);
    }

    function removePost(btn) {
      const card = btn.closest('.property-card');
      const propName = card.querySelector('.property-name').textContent;
      
      if (confirm(`Are you sure you want to remove the post for "${propName}"? This action cannot be undone.`)) {
        // Show loading state
        btn.textContent = 'Removing...';
        btn.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
          // Remove the property card
          card.style.opacity = '0.5';
          card.style.background = '#fff0f0';
          card.style.border = '2px solid #c00';
          
          // Update button
          btn.textContent = 'Removed ✗';
          btn.style.background = '#c00';
          btn.style.color = 'white';
          btn.disabled = true;
          
          // Disable other buttons
          const otherBtns = card.querySelectorAll('button:not(.remove-btn)');
          otherBtns.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.5';
          });
          
          // Show success message
          showNotification(`Property post "${propName}" has been removed successfully.`, 'success');
          
          // Remove card after delay
          setTimeout(() => {
            card.remove();
          }, 3000);
          
        }, 1000);
      }
    }

    // Modal functions
    function showModal(content) {
      const modal = document.createElement('div');
      modal.id = 'modal';
      modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
      `;
      modal.innerHTML = content;
      document.body.appendChild(modal);
    }

    function closeModal() {
      const modal = document.getElementById('modal');
      if (modal) {
        modal.remove();
      }
    }

    // Notification function
    function showNotification(message, type) {
      const notification = document.createElement('div');
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1001;
        background: ${type === 'success' ? '#1a7f1a' : '#c00'};
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      `;
      notification.textContent = message;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.remove();
      }, 3000);
    }
  </script>
</body>
</html> 