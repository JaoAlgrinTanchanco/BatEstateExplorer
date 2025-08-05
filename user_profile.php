<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
$is_logged_in = is_logged_in();
$current_user = null;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
} else {
    // Redirect to login if not logged in
    header('Location: index.php');
    exit;
}

// Handle profile update
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($conn, $_POST['first_name']);
    $last_name = sanitize_input($conn, $_POST['last_name']);
    $phone = sanitize_input($conn, $_POST['phone']);
    $address = sanitize_input($conn, $_POST['address']);
    
    $update_query = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ssssi", $first_name, $last_name, $phone, $address, $current_user['id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Profile updated successfully!";
        // Refresh user data
        $current_user = get_logged_in_user($conn);
    } else {
        $error = "Error updating profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile | BatEstateExplorer</title>
  <link rel="stylesheet" href="homepage.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    .profile-container {
      max-width: 1100px;
      margin: 40px auto 0 auto;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.07);
      padding: 36px 40px 40px 40px;
    }
    .profile-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 30px;
    }
    .profile-info {
      display: flex;
      align-items: center;
      gap: 28px;
    }
    .profile-avatar {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      background: #e0e7ef;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.8rem;
      color: #888;
    }
    .profile-details {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .profile-details .profile-name {
      font-size: 1.3rem;
      font-weight: 600;
      color: #222;
    }
    .profile-details .profile-email,
    .profile-details .profile-location {
      color: #666;
      font-size: 1rem;
    }
    .profile-actions {
      position: relative;
    }
    .profile-actions .dots-btn {
      background: none;
      border: none;
      font-size: 1.7rem;
      color: #444;
      cursor: pointer;
      padding: 8px 12px;
      border-radius: 50%;
      transition: background 0.2s;
    }
    .profile-actions .dots-btn:hover {
      background: #f4f7fa;
    }
    .profile-actions .dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 38px;
      background: #fff;
      border: 1px solid #e7e7e7;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      min-width: 210px; /* Increased from previous value */
      z-index: 10;
    }
    .profile-actions .dropdown-menu.active {
      display: block;
    }
    .profile-actions .dropdown-menu button {
      width: 100%;
      background: none;
      border: none;
      padding: 12px 18px;
      text-align: left;
      font-size: 1rem;
      color: #222;
      cursor: pointer;
      border-radius: 10px;
      transition: background 0.2s;
    }
    .profile-actions .dropdown-menu button:hover {
      background: #f4f7fa;
    }
    .profile-actions .dropdown-menu .danger {
      color: #c00;
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
    .section-content {
      background: #fff;
      border-radius: 0 0 18px 18px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
      padding: 28px 0 0 0;
    }
    .property-list {
      display: flex;
      gap: 24px;
      flex-wrap: wrap;
      margin-bottom: 30px;
    }
    .property-card {
      background: #f8f8f8;
      border-radius: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      width: 270px;
      padding: 18px 18px 20px 18px;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
    }
    .property-card .property-image {
      width: 100%;
      height: 120px;
      border-radius: 18px 18px 40px 40px;
      background: #e0e7ef url('Pictures/bg4.jpg') center/cover no-repeat;
      margin-bottom: 12px;
      position: relative;
      display: flex;
      align-items: flex-end;
      justify-content: flex-start;
    }
    .property-card .property-badge {
      position: absolute;
      top: 8px;
      left: 8px;
      background: #000;
      color: #fff;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 0.7rem;
      font-weight: 600;
    }
    .property-card .fav-btn {
      position: absolute;
      right: 18px;
      bottom: 18px;
      background: none;
      border: none;
      outline: none;
      cursor: pointer;
      font-size: 1.5rem;
      z-index: 2;
      transition: transform 0.1s;
    }
    .property-card .fav-btn:active {
      transform: scale(0.92);
    }
    .property-card .fav-btn .fa-heart {
      color: #e74c3c;
      background: #fff;
      border-radius: 50%;
      padding: 2px 2px 0 2px;
      font-size: 1.3rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .property-card .fav-indicator {
      position: absolute;
      right: 8px;
      bottom: 8px;
      z-index: 2;
    }
    .property-card .fav-indicator .fa-heart {
      color: #e74c3c;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 50%;
      padding: 2px 2px 0 2px;
      font-size: 0.9rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .property-card .property-name {
      font-weight: 600;
      font-size: 1.08rem;
      margin-bottom: 2px;
      text-align: center;
    }
    .property-card .property-meta {
      color: #888;
      font-size: 0.97rem;
      margin-bottom: 2px;
      text-align: center;
    }
    .property-card .property-price {
      color: #0074d9;
      font-weight: 600;
      margin-bottom: 8px;
      text-align: center;
    }
    .property-card .details-btn {
      padding: 7px 18px;
      border-radius: 20px;
      border: none;
      font-weight: 500;
      font-size: 0.97rem;
      cursor: pointer;
      background: #000;
      color: #fff;
      transition: background 0.2s, color 0.2s;
      margin-top: 8px;
    }
    .property-card .details-btn:hover {
      background: #bfc2c4;
      color: #fff;
    }
    .sort-row {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 18px;
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
      width: 140px;
    }
    #sidebarMenuBtn {
      background: none;
      border: none;
      font-size: 2rem;
      margin-right: 18px;
      cursor: pointer;
      color: #222;
      display: inline-flex;
      align-items: center;
    }
    #sidebarMenu {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 320px;
      background: rgba(255,255,255,0.25);
      backdrop-filter: blur(16px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.10);
      border-radius: 0 24px 24px 0;
      z-index: 201;
      padding: 36px 0 24px 0;
      transition: transform 0.3s cubic-bezier(.4,1.3,.5,1), opacity 0.2s;
      transform: translateX(-100%);
      opacity: 0;
    }
    #sidebarMenu.open {
      display: block;
      transform: translateX(0);
      opacity: 1;
    }
    #sidebarOverlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0,0,0,0.18);
      z-index: 200;
      transition: opacity 0.2s;
    }
    #sidebarOverlay.open {
      display: block;
      opacity: 1;
    }
    .sidebar-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 18px;
      height: 100%;
      width: 100%;
    }
    .sidebar-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: #e0e7ef;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      color: #888;
      margin-bottom: 8px;
    }
    .sidebar-name {
      font-weight: 600;
      font-size: 1.1rem;
      margin-bottom: 2px;
      text-align: center;
    }
    .sidebar-company {
      color: #888;
      font-size: 0.98rem;
      margin-bottom: 18px;
      text-align: center;
    }
    .sidebar-dropdown {
      width: 80%;
      margin-bottom: 10px;
      position: relative;
    }
    .sidebar-dropdown-btn {
      width: 100%;
      padding: 10px 18px;
      border-radius: 30px;
      border: 1px solid #ccc;
      font-size: 1rem;
      background: #fff;
      color: #222;
      text-align: left;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .sidebar-dropdown-menu {
      display: none;
      position: absolute;
      left: 0;
      top: 110%;
      width: 100%;
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      z-index: 10;
      flex-direction: column;
      padding: 6px 0;
    }
    .sidebar-dropdown-menu.open {
      display: flex;
    }
    .sidebar-dropdown-menu button {
      background: none;
      border: none;
      width: 100%;
      padding: 10px 18px;
      text-align: left;
      font-size: 1rem;
      color: #222;
      cursor: pointer;
      border-radius: 10px;
      transition: background 0.2s;
    }
    .sidebar-dropdown-menu button:hover {
      background: #f4f7fa;
    }
    .sidebar-links {
      width: 80%;
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: 10px;
    }
    .sidebar-links button {
      width: 100%;
    }
    .sidebar-logout {
      width: 80%;
      margin-top: auto;
      margin-bottom: 10px;
    }
    @media (max-width: 700px) {
      #sidebarMenu {
        width: 90vw;
        border-radius: 0 18px 18px 0;
      }
    }
    .popup-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 38px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      min-width: 220px;
      z-index: 10;
      padding: 8px 0;
      border: 1px solid #e7e7e7;
    }
    .popup-menu.active {
      display: block;
    }
    .menu-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      cursor: pointer;
      transition: background 0.2s;
      font-size: 0.95rem;
      color: #222;
    }
    .menu-item:hover {
      background: #f8f9fa;
    }
    .menu-item i {
      width: 20px;
      text-align: center;
    }
    .menu-separator {
      height: 1px;
      background: #e7e7e7;
      margin: 4px 0;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="glass-header">
    <div class="header-content">
      <h1 class="logo">BatEstateExplorer</h1>
      <nav>
        <ul>
          <li><a href="user_index.html">Home</a></li>
          <li><a href="user_search.html">Properties</a></li>
          <li><a href="#sellers">Agents</a></li>
          <li><a href="#faqs">FAQs</a></li>
          <!--<li><input type="text" id="searchBar" placeholder="Search..." class="rounded-input" /></li>-->
          <!-- Removed heart (favorites) button -->
        </ul>
      </nav>
    </div>
  </header>

  <div class="profile-container">
    <div class="profile-header">
      <div class="profile-info">
        <div class="profile-avatar">
          <i class="fa-solid fa-user"></i>
        </div>
        <div class="profile-details">
          <span class="profile-name">Joiz Nikul Batumbakal</span>
          <span class="profile-email">joiz@email.com</span>
          <span class="profile-location">Batangas, Philippines</span>
        </div>
      </div>
      <div class="profile-actions">
        <button class="dots-btn" id="profileDotsBtn" title="Options">
          <i class="fa-solid fa-ellipsis"></i>
        </button>
        <div class="popup-menu" id="profileDropdownMenu">
          <!-- Top Section -->
          <div class="menu-item">
            <i class="fa-solid fa-users" style="color:#888;"></i>
            <span>Agents</span>
          </div>
          <div class="menu-item">
            <i class="fa-solid fa-envelope" style="color:#28a745; background:#28a745; color:#fff; border-radius:50%; width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; font-size:0.8rem;"></i>
            <span>Messages</span>
          </div>
          <div class="menu-item">
            <i class="fa-solid fa-user-pen" style="color:#000;"></i>
            <span> Edit Profile</span>
          </div>

          <div class="menu-item">
            <i class="fa-solid fa-right-from-bracket" style="color:#000;"></i>
            <span>Log Out</span>
          </div>
          
          <!-- Middle Section -->
          <div class="menu-separator"></div>
          <div class="menu-item">
            <i class="fa-solid fa-bell" style="color:#ff8c00;"></i>
            <span>Notifications</span>
          </div>
          <div class="menu-item">
            <i class="fa-solid fa-cog" style="color:#888;"></i>
            <span>Account Settings</span>
          </div>
          <div class="menu-item">
            <i class="fa-solid fa-question-circle" style="color:#000;"></i>
            <span>Help Center</span>
          </div>
          <div class="menu-item">
            <i class="fa-solid fa-flag" style="color:#000;"></i> 
            <span>File a Report</span>
          </div>
          <div class="menu-item">
            <i class="fa-solid fa-user-xmark" style="color:#c00;"></i>
            <span>Delete Account</span>
          </div>
          <!-- Bottom Section -->
          <div class="menu-separator"></div>
          <div class="menu-item">
            <span>Become a Direct Agent</span>
          </div>
          <div class="menu-item">
            <span>Register as an Associate Agent</span>
          </div>
        </div>
      </div>
    </div>
    <!-- Tab Bar -->
    <div class="tab-bar">
      <span class="tab active" id="savedListTab">Saved List</span>
      <span class="tab" id="reviewsTab">Reviews</span>
    </div>
    <!-- Section Content -->
    <div class="section-content" id="savedListSection">
      <div class="sort-row">
        <div class="sort-by">
          
          <label for="sortSaved">Sort By:</label>
          <select id="sortSaved">
            <option value="date">By Date</option>
            <option value="price">By Price</option>
          </select>
        </div>
      </div>
      <div class="property-list" id="savedPropertyList">
        <!-- Placeholder property cards -->
        <div class="property-card" data-date="2024-06-01" data-price="2500000">
          <div class="property-image">
            <div class="property-badge">Property</div>
            <div class="fav-indicator"><i class="fa-solid fa-heart"></i></div>
          </div>
          <div class="property-name">Sunrise Villa</div>
          <div class="property-meta">Batangas City</div>
          <div class="property-price">₱2,500,000</div>
          <button class="details-btn">View Details</button>
        </div>
        <div class="property-card" data-date="2024-05-15" data-price="1800000">
          <div class="property-image">
            <div class="property-badge">Lot</div>
            <div class="fav-indicator"><i class="fa-solid fa-heart"></i></div>
          </div>
          <div class="property-name">Greenfield Lot</div>
          <div class="property-meta">Lipa City</div>
          <div class="property-price">₱1,800,000</div>
          <button class="details-btn">View Details</button>
        </div>
        <div class="property-card" data-date="2024-04-20" data-price="3200000">
          <div class="property-image">
            <div class="property-badge">Property</div>
            <div class="fav-indicator"><i class="fa-solid fa-heart"></i></div>
          </div>
          <div class="property-name">Hilltop House</div>
          <div class="property-meta">Tanauan</div>
          <div class="property-price">₱3,200,000</div>
          <button class="details-btn">View Details</button>
        </div>
      </div>
    </div>
    <div class="section-content" id="reviewsSection" style="display:none;">
      <div class="property-list">
        <!-- Placeholder review cards -->
        <div class="property-card">
          <div class="property-image"></div>
          <div class="property-name">Sunrise Villa</div>
          <div class="property-meta">"Great property, smooth transaction!"</div>
          <div class="property-price">5/5 Stars</div>
        </div>
        <div class="property-card">
          <div class="property-image"></div>
          <div class="property-name">Greenfield Lot</div>
          <div class="property-meta">"Very accommodating seller."</div>
          <div class="property-price">4/5 Stars</div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Tab switching
    const savedListTab = document.getElementById('savedListTab');
    const reviewsTab = document.getElementById('reviewsTab');
    const savedListSection = document.getElementById('savedListSection');
    const reviewsSection = document.getElementById('reviewsSection');
    savedListTab.addEventListener('click', () => {
      savedListTab.classList.add('active');
      reviewsTab.classList.remove('active');
      savedListSection.style.display = '';
      reviewsSection.style.display = 'none';
    });
    reviewsTab.addEventListener('click', () => {
      reviewsTab.classList.add('active');
      savedListTab.classList.remove('active');
      reviewsSection.style.display = '';
      savedListSection.style.display = 'none';
    });
    // Triple-dot menu
    const profileDotsBtn = document.getElementById('profileDotsBtn');
    const profileDropdownMenu = document.getElementById('profileDropdownMenu');
    profileDotsBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      profileDropdownMenu.classList.toggle('active');
    });
    document.addEventListener('click', (e) => {
      if (!profileDropdownMenu.contains(e.target) && e.target !== profileDotsBtn) {
        profileDropdownMenu.classList.remove('active');
      }
    });

    
    // Sort functionality for Saved List
    const sortSaved = document.getElementById('sortSaved');
    const savedPropertyList = document.getElementById('savedPropertyList');
    sortSaved.addEventListener('change', function() {
      const cards = Array.from(savedPropertyList.querySelectorAll('.property-card'));
      let sorted;
      if (this.value === 'date') {
        sorted = cards.sort((a, b) => new Date(b.dataset.date) - new Date(a.dataset.date));
      } else if (this.value === 'price') {
        sorted = cards.sort((a, b) => a.dataset.price - b.dataset.price);
      }
      sorted.forEach(card => savedPropertyList.appendChild(card));
    });
  </script>
</body>
</html> 