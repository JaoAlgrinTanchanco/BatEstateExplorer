<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a direct agent
$is_logged_in = is_logged_in();
$current_user = null;
$is_direct_agent = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_direct_agent = ($current_user && $current_user['user_type'] === 'direct_agent');
}

// Redirect if not logged in or not a direct agent
if (!$is_logged_in || !$is_direct_agent) {
    header('Location: index.php');
    exit;
}

// Get agent details
$agent_id = $current_user['id'];
$agent_query = "SELECT a.*, c.name as company_name 
                FROM agents a 
                LEFT JOIN companies c ON a.company_id = c.id 
                WHERE a.user_id = ?";
$stmt = mysqli_prepare($conn, $agent_query);
mysqli_stmt_bind_param($stmt, "i", $agent_id);
mysqli_stmt_execute($stmt);
$agent_result = mysqli_stmt_get_result($stmt);
$agent = mysqli_fetch_assoc($agent_result);

// Get agent's properties count
$properties_query = "SELECT COUNT(*) as total FROM properties WHERE agent_id = ?";
$stmt = mysqli_prepare($conn, $properties_query);
mysqli_stmt_bind_param($stmt, "i", $agent['id']);
mysqli_stmt_execute($stmt);
$properties_result = mysqli_stmt_get_result($stmt);
$properties_count = mysqli_fetch_assoc($properties_result)['total'];

// Get recent properties
$recent_properties_query = "SELECT * FROM properties WHERE agent_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_properties_query);
mysqli_stmt_bind_param($stmt, "i", $agent['id']);
mysqli_stmt_execute($stmt);
$recent_properties = mysqli_stmt_get_result($stmt);

// Calculate statistics (placeholder values for now)
$reviews_count = 0; // TODO: Implement reviews system
$inquiries_count = 0; // TODO: Implement inquiries system
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Direct Agent Dashboard | BatEstateExplorer</title>
  <link rel="stylesheet" href="homepage.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    .profile-container {
      max-width: 1200px;
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
    .profile-details .profile-broker-id {
      color: #0074d9;
      font-size: 1rem;
      font-weight: 500;
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
      min-width: 210px;
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
      display: flex;
      align-items: center;
      gap: 10px;
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
      border-radius: 10px;
      background: #e0e7ef url('Pictures/bg4.jpg') center/cover no-repeat;
      margin-bottom: 12px;
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
    .profile-stats {
      display: flex;
      gap: 24px;
      margin-top: 10px;
    }
    .profile-stat {
      background: #f4f7fa;
      border-radius: 10px;
      padding: 12px 20px;
      font-size: 1.05rem;
      color: #222;
      border: 1px solid #e7e7e7;
      text-align: center;
    }
    .glass-btn {
      padding: 8px 20px;
      border-radius: 20px;
      border: none;
      font-weight: 500;
      font-size: 1rem;
      cursor: pointer;
      background: rgba(255,255,255,0.25);
      backdrop-filter: blur(8px);
      color: #222;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      transition: background 0.2s, color 0.2s;
      margin-right: 6px;
    }
    .glass-btn:hover {
      background: #000000;
      color: #fff;
    }
    .glass-btn.danger {
      background: rgba(255,0,0,0.08);
      color: #c00;
    }
    .glass-btn.danger:hover {
      background: #c00;
      color: #fff;
    }
    .glass-input {
      background: rgba(255,255,255,0.5);
      border: 1px solid #dcdfe3;
      border-radius: 14px;
      padding: 10px 16px;
      font-size: 1rem;
      color: #222;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      outline: none;
      transition: border 0.2s;
    }
    .glass-input:focus {
      border: 1.5px solid #000000;
    }
    .glass-input option {
      padding: 8px 12px;
      background: #fff;
      color: #333;
    }
    .glass-input option:hover {
      background: #f4f7fa;
    }
    .glass-input::placeholder {
      color: #999;
      opacity: 1;
    }
    .glass-input:focus::placeholder {
      opacity: 0.5;
    }
    .file-input-wrapper {
      position: relative;
      display: inline-block;
      width: 100%;
    }
    .file-input-wrapper input[type="file"] {
      opacity: 0;
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    .file-input-display {
      background: rgba(255,255,255,0.5);
      border: 1px solid #dcdfe3;
      border-radius: 14px;
      padding: 10px 16px;
      font-size: 1rem;
      color: #222;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
      transition: border 0.2s;
    }
    .file-input-display:hover {
      border: 1.5px solid #000000;
    }
    .file-input-display .file-name {
      color: #999;
    }
    .file-input-display .file-name.has-file {
      color: #222;
    }
    .file-input-display .choose-btn {
      background: #000;
      color: #fff;
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 0.9rem;
      border: none;
      cursor: pointer;
      transition: background 0.2s;
    }
    .file-input-display .choose-btn:hover {
      background: #333;
    }
    .message-card .details-btn {
      background: rgba(196, 196, 196, 0.25);
      color: #666;
      border-radius: 18px;
      font-weight: 500;
      font-size: 1rem;
      padding: 8px 18px;
      border: none;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      transition: background 0.2s, color 0.2s;
    }
    .message-card .details-btn:hover {
      background: #000000;
      color: #fff;
    }
    .listings-table th, .listings-table td {
      padding: 16px 12px;
      text-align: center;
      vertical-align: middle;
    }
    .listings-table th {
      font-weight: 600;
      color: #222;
      background: rgba(255,255,255,0.4);
    }
    .listings-table td {
      background: rgba(255,255,255,0.5);
    }
    .listings-table td:last-child, .listings-table th:last-child {
      text-align: center;
    }
    .glass-btn {
      display: inline-block;
      min-width: 70px;
      margin: 4px 4px;
    }
    .popup-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 38px;
      background: #fff;
      border-radius: 12px;
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
      <h1 class="logo">BatEstateExplorer <span style="font-size:0.9rem; background:#0074d9; color:#fff; border-radius:8px; padding:2px 10px; margin-left:10px; vertical-align:middle;">Direct Agent</span></h1>
      
      <nav>
        <ul>
          <li><a href="direct_agent_index.php">Home</a></li>
          <li><a href="direct_agent_search.php">Properties</a></li>
          <li><a href="#sellers">Agents</a></li>
          <li><a href="#faqs">FAQs</a></li>
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
          <span class="profile-name"><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
          <span class="profile-email"><?php echo htmlspecialchars($current_user['email']); ?></span>
          <span class="profile-location"><?php echo htmlspecialchars($current_user['address'] ?: 'Batangas, Philippines'); ?></span>
          <span class="profile-broker-id">Broker ID: <?php echo htmlspecialchars($agent['broker_id'] ?? 'N/A'); ?></span>
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
            <span>Edit Profile</span>
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
            <span>Register as an Associate Agent</span>
          </div>
          <div class="menu-separator"></div>
          <div class="menu-item" onclick="logout()">
            <i class="fa-solid fa-sign-out-alt" style="color:#c00;"></i>
            <span>Logout</span>
          </div>
        </div>
      </div>
    </div>
            <div class="profile-stats">
              <div class="profile-stat">Listings<br><b><?php echo $properties_count; ?></b></div>
              <div class="profile-stat">Reviews<br><b><?php echo $reviews_count > 0 ? '4.8 ★' : '0'; ?></b></div>
              <div class="profile-stat">Inquiries<br><b><?php echo $inquiries_count; ?></b></div>
            </div>
    <!-- Tab Bar -->
    <div class="tab-bar">
      <span class="tab active" id="portfolioTab">Portfolio</span>
      <span class="tab" id="listingsTab">My Listings</span>
      <span class="tab" id="addTab">Add Listing</span>
      <span class="tab" id="messagesTab">Messages</span>
      <span class="tab" id="analyticsTab">Analytics/Reviews</span>
          </div>
    <!-- Section Content -->
    <div class="section-content" id="portfolioSection">
      <div class="property-list" id="portfolioPropertyList">
        <?php if (mysqli_num_rows($recent_properties) > 0): ?>
          <?php while ($property = mysqli_fetch_assoc($recent_properties)): 
            // Get the first image for this property
            $image_query = "SELECT image_path FROM property_images WHERE property_id = ? ORDER BY created_at ASC LIMIT 1";
            $image_stmt = mysqli_prepare($conn, $image_query);
            mysqli_stmt_bind_param($image_stmt, "i", $property['id']);
            mysqli_stmt_execute($image_stmt);
            $image_result = mysqli_stmt_get_result($image_stmt);
            $first_image = mysqli_fetch_assoc($image_result);
            $image_style = $first_image ? "background-image: url('" . htmlspecialchars($first_image['image_path']) . "'); background-size: cover; background-position: center;" : "";
          ?>
            <div class="property-card" data-property-id="<?php echo $property['id']; ?>">
              <div class="property-image" style="<?php echo $image_style; ?>"></div>
              <div class="property-name"><?php echo htmlspecialchars($property['title']); ?></div>
              <div class="property-meta"><?php echo htmlspecialchars($property['location']); ?></div>
              <div class="property-price">₱<?php echo number_format($property['price']); ?></div>
              <button class="details-btn" onclick="viewPropertyDetails(<?php echo $property['id']; ?>)">Details</button>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div style="text-align: center; padding: 40px; color: #666;">
            <i class="fa-solid fa-home" style="font-size: 3rem; margin-bottom: 20px; color: #ddd;"></i>
            <h3>No Properties Yet</h3>
            <p>You haven't added any properties yet. Click "Add Listing" to get started!</p>
          </div>
        <?php endif; ?>
          
      </div>
    </div>
    <div class="section-content" id="listingsSection" style="display:none;">
      <div class="glass-card" style="background:rgba(255,255,255,0.25); backdrop-filter:blur(12px); border-radius:18px; box-shadow:0 4px 16px rgba(0,0,0,0.07); padding:32px 24px;">
        <h2 style="font-size:1.2rem; color:#222; margin-bottom:18px;">My Listings</h2>
        <table class="listings-table" style="width:100%; border-collapse:collapse; background:rgba(255,255,255,0.5); border-radius:14px; overflow:hidden;">
          <thead style="background:rgba(255,255,255,0.4);">
            <tr><th>Property</th><th>Location</th><th>Price</th><th>Bedrooms</th><th>Bathrooms</th><th>Size (sqm)</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php 
            // Reset the result pointer for the properties query
            mysqli_data_seek($recent_properties, 0);
            while ($property = mysqli_fetch_assoc($recent_properties)): 
              $status_color = $property['status'] === 'sold' ? '#c00' : '#1a7f1a';
            ?>
            <tr data-property-id="<?php echo $property['id']; ?>">
              <td><?php echo htmlspecialchars($property['title']); ?></td>
              <td><?php echo htmlspecialchars($property['location']); ?></td>
              <td>₱<?php echo number_format($property['price']); ?></td>
              <td><?php echo $property['bedrooms']; ?></td>
              <td><?php echo $property['bathrooms']; ?></td>
              <td><?php echo $property['sqm']; ?></td>
              <td style="color:<?php echo $status_color; ?>; font-weight:500;"><?php echo ucfirst($property['status']); ?></td>
              <td>
                <button class="glass-btn" onclick="viewPropertyDetails(<?php echo $property['id']; ?>)">Details</button>
                <button class="glass-btn" onclick="editListing(<?php echo $property['id']; ?>)">Edit</button>
                <button class="glass-btn danger" onclick="deleteListing(<?php echo $property['id']; ?>)">Delete</button>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="section-content" id="addSection" style="display:none;">
      <div class="glass-card" style="background:rgba(255,255,255,0.25); backdrop-filter:blur(12px); border-radius:18px; box-shadow:0 4px 16px rgba(0,0,0,0.07); padding:32px 24px;">
        <h2 style="font-size:1.2rem; color:#222; margin-bottom:18px;">Add New Listing</h2>
        <form class="add-listing-form" style="display:flex; flex-direction:column; gap:18px;">
          <div class="form-group">
            <input type="text" id="addName" placeholder="Enter property name" required class="glass-input">
          </div>
          <div class="form-group">
            <select id="addLocation" required class="glass-input">
              <option value="">Select Location</option>
              <option value="Agoncillo">Agoncillo</option>
              <option value="Alitagtag">Alitagtag</option>
              <option value="Balayan">Balayan</option>
              <option value="Balete">Balete</option>
              <option value="Batangas City">Batangas City</option>
              <option value="Bauan">Bauan</option>
              <option value="Calaca">Calaca</option>
              <option value="Calatagan">Calatagan</option>
              <option value="Cuenca">Cuenca</option>
              <option value="Ibaan">Ibaan</option>
              <option value="Laurel">Laurel</option>
              <option value="Lemery">Lemery</option>
              <option value="Lian">Lian</option>
              <option value="Lipa City">Lipa City</option>
              <option value="Lobo">Lobo</option>
              <option value="Mabini">Mabini</option>
              <option value="Malvar">Malvar</option>
              <option value="Mataasnakahoy">Mataasnakahoy</option>
              <option value="Nasugbu">Nasugbu</option>
              <option value="Padre Garcia">Padre Garcia</option>
              <option value="Rosario">Rosario</option>
              <option value="San Jose">San Jose</option>
              <option value="San Juan">San Juan</option>
              <option value="San Luis">San Luis</option>
              <option value="San Nicolas">San Nicolas</option>
              <option value="San Pascual">San Pascual</option>
              <option value="Santa Teresita">Santa Teresita</option>
              <option value="Santo Tomas">Santo Tomas</option>
              <option value="Taal">Taal</option>
              <option value="Talisay">Talisay</option>
              <option value="Tanauan City">Tanauan City</option>
              <option value="Taysan">Taysan</option>
              <option value="Tingloy">Tingloy</option>
              <option value="Tuy">Tuy</option>
            </select>
          </div>
          <div class="form-group">
            
            <input type="number" id="addPrice" placeholder="Enter price in pesos" required class="glass-input">
          </div>
          <div class="form-group">
            <input type="number" id="addLotSize" placeholder="Enter lot size in sqm" required class="glass-input">
          </div>
          <div class="form-group">
                          <select id="addType" class="glass-input" required>
                <option value="">Select Property Type</option>
                <option value="Property">Property</option>
                <option value="Lot">Lot</option>
              </select>
          </div>
          <div class="form-group">
                          <select id="addBedrooms" class="glass-input" required>
                <option value="">Select Bedrooms</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4+">4+</option>
              </select>
          </div>
          <div class="form-group">
                          <select id="addBathrooms" class="glass-input" required>
                <option value="">Select Bathrooms</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4+">4+</option>
              </select>
          </div>
          <div class="form-group">
            
            <textarea id="addDesc" placeholder="Enter property description" class="glass-input" rows="4" required></textarea>
          </div>
          <div class="form-group">
            <div style="background: #f8f8f8; padding: 15px; border-radius: 8px; margin: 15px 0;">
              <p style="margin: 0; color: #666; font-style: italic;">Images and documents can be added after creating the listing using the "Add More Images" feature in the property details.</p>
            </div>
          </div>
          <div class="form-actions" style="display:flex; gap:16px; justify-content:flex-end;">
            <button type="submit" class="glass-btn">Add Listing</button>
            <button type="button" class="glass-btn danger">Cancel</button>
          </div>
        </form>
      </div>
    </div>
    <div class="section-content" id="messagesSection" style="display:none;">
        <h2 style="font-size:1.2rem; color:#222; margin-bottom:18px;">Messages</h2>
      <div class="messages-section" style="display:flex; flex-direction:column; gap:18px;">
        <!-- Email-like message cards -->
        <div class="message-card" style="background:#f8f8f8; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px 22px; display:flex; align-items:flex-start; justify-content:space-between; gap:18px;">
          <div style="flex:1;">
            <div style="font-weight:600; color:#0074d9; margin-bottom:4px;">Maria Buyer</div>
            <div style="color:#222; margin-bottom:8px;">Is Sunrise Villa still available?</div>
          </div>
          <div style="align-self:center;">
            <button class="details-btn" onclick="toggleReply(this)">Reply</button>
          </div>
        </div>
        <div class="reply-box" style="display:none; margin-left:24px; margin-bottom:8px;">
          <input type="text" placeholder="Type your reply..." style="width:70%; padding:8px 12px; border-radius:8px; border:1px solid #ccc;"> <button class="details-btn">Send</button>
        </div>
        <div class="message-card" style="background:#f8f8f8; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px 22px; display:flex; align-items:flex-start; justify-content:space-between; gap:18px;">
          <div style="flex:1;">
            <div style="font-weight:600; color:#0074d9; margin-bottom:4px;">Pedro Buyer</div>
            <div style="color:#222; margin-bottom:8px;">Can I schedule a viewing for Hilltop Estate?</div>
          </div>
          <div style="align-self:center;">
            <button class="details-btn" onclick="toggleReply(this)">Reply</button>
          </div>
        </div>
        <div class="reply-box" style="display:none; margin-left:24px; margin-bottom:8px;">
          <input type="text" placeholder="Type your reply..." style="width:70%; padding:8px 12px; border-radius:8px; border:1px solid #ccc;"> <button class="details-btn">Send</button>
        </div>
      </div>
    </div>
    <div class="section-content" id="analyticsSection" style="display:none;">
        <h2 style="font-size:1.2rem; color:#222; margin-bottom:18px;">Analytics & Reviews</h2>
      <div class="analytics-section" style="display:flex; flex-direction:column; gap:18px;">
        <div style="display:flex; align-items:center; gap:18px;">
          <span style="font-size:2.2rem; color:#FFD700;">★★★★☆</span>
          <span style="font-size:1.3rem; color:#222; font-weight:600;">4.8</span>
          <span style="color:#888;">Average Rating</span>
        </div>
        <div class="review-list" style="display:flex; flex-wrap:wrap; gap:18px;">
          <div class="review-card" style="background:#f8f8f8; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:16px 20px; min-width:260px; max-width:340px; flex:1;">
            <div style="font-weight:600; color:#0074d9; margin-bottom:4px;">Maria Buyer <span style="color:#FFD700; font-size:1.1rem; margin-left:6px;">★★★★★</span></div>
            <div style="color:#222;">Great experience, very responsive and helpful!</div>
          </div>
          <div class="review-card" style="background:#f8f8f8; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:16px 20px; min-width:260px; max-width:340px; flex:1;">
            <div style="font-weight:600; color:#0074d9; margin-bottom:4px;">Pedro Buyer <span style="color:#FFD700; font-size:1.1rem; margin-left:6px;">★★★★☆</span></div>
            <div style="color:#222;">Smooth transaction, would recommend.</div>
          </div>
        </div>
        <div style="margin-top:8px; font-size:1.05rem; color:#555;">Listings Status: <b>2 Available</b>, <b>1 Sold</b></div>
      </div>
    </div>
  </div>

  <script>
    // Tab switching
    const portfolioTab = document.getElementById('portfolioTab');
    const listingsTab = document.getElementById('listingsTab');
    const addTab = document.getElementById('addTab');
    const messagesTab = document.getElementById('messagesTab');
    const analyticsTab = document.getElementById('analyticsTab');
    const portfolioSection = document.getElementById('portfolioSection');
    const listingsSection = document.getElementById('listingsSection');
    const addSection = document.getElementById('addSection');
    const messagesSection = document.getElementById('messagesSection');
    const analyticsSection = document.getElementById('analyticsSection');
    function hideAllSections() {
      portfolioSection.style.display = 'none';
      listingsSection.style.display = 'none';
      addSection.style.display = 'none';
      messagesSection.style.display = 'none';
      analyticsSection.style.display = 'none';
    }
    portfolioTab.addEventListener('click', () => {
      hideAllSections();
      portfolioTab.classList.add('active');
      listingsTab.classList.remove('active');
      addTab.classList.remove('active');
      messagesTab.classList.remove('active');
      analyticsTab.classList.remove('active');
      portfolioSection.style.display = '';
    });
    listingsTab.addEventListener('click', () => {
      hideAllSections();
      listingsTab.classList.add('active');
      portfolioTab.classList.remove('active');
      addTab.classList.remove('active');
      messagesTab.classList.remove('active');
      analyticsTab.classList.remove('active');
      listingsSection.style.display = '';
    });
    addTab.addEventListener('click', () => {
      hideAllSections();
      addTab.classList.add('active');
      portfolioTab.classList.remove('active');
      listingsTab.classList.remove('active');
      messagesTab.classList.remove('active');
      analyticsTab.classList.remove('active');
      addSection.style.display = '';
    });
    messagesTab.addEventListener('click', () => {
      hideAllSections();
      messagesTab.classList.add('active');
      portfolioTab.classList.remove('active');
      listingsTab.classList.remove('active');
      addTab.classList.remove('active');
      analyticsTab.classList.remove('active');
      messagesSection.style.display = '';
    });
    analyticsTab.addEventListener('click', () => {
      hideAllSections();
      analyticsTab.classList.add('active');
      portfolioTab.classList.remove('active');
      listingsTab.classList.remove('active');
      addTab.classList.remove('active');
      messagesTab.classList.remove('active');
      analyticsSection.style.display = '';
    });
    // Triple-dot menu logic (ensure only one menu open at a time)
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

    function toggleReply(btn) {
      const replyBox = btn.parentElement.parentElement.nextElementSibling;
      if (replyBox && replyBox.classList.contains('reply-box')) {
        replyBox.style.display = replyBox.style.display === 'none' ? 'block' : 'none';
      }
    }



    // Form submission handling
    document.querySelector('.add-listing-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      // Get form elements
      const form = e.target;
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      
      // Show loading state
      submitBtn.textContent = 'Adding...';
      submitBtn.disabled = true;
      
      // Collect form data
      const formData = {
        title: document.getElementById('addName').value.trim(),
        description: document.getElementById('addDesc').value.trim(),
        location: document.getElementById('addLocation').value,
        property_type: document.getElementById('addType').value,
        price: document.getElementById('addPrice').value,
        bedrooms: parseInt(document.getElementById('addBedrooms').value),
        bathrooms: parseInt(document.getElementById('addBathrooms').value),
        sqm: parseInt(document.getElementById('addLotSize').value),
        agent_id: 1, // This should be the actual agent ID from the database
        features: `${document.getElementById('addBedrooms').value} BR, ${document.getElementById('addBathrooms').value} BA, ${document.getElementById('addLotSize').value} sqm`
      };
      
      // Validate required fields
      const requiredFields = ['title', 'location', 'price', 'sqm', 'property_type', 'bedrooms', 'bathrooms', 'description'];
      const missingFields = requiredFields.filter(field => !formData[field]);
      
      if (missingFields.length > 0) {
        alert('Please fill in all required fields: ' + missingFields.join(', '));
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        return;
      }
      

      try {
        // Create FormData object
        const formDataObj = new FormData();
        
        // Add text fields
        formDataObj.append('title', formData.title);
        formDataObj.append('description', formData.description);
        formDataObj.append('location', formData.location);
        formDataObj.append('property_type', formData.property_type);
        formDataObj.append('price', formData.price);
        formDataObj.append('bedrooms', formData.bedrooms);
        formDataObj.append('bathrooms', formData.bathrooms);
        formDataObj.append('sqm', formData.sqm);
        formDataObj.append('features', formData.features);
        
        // Send to backend API
        const response = await fetch('add_property.php', {
          method: 'POST',
          body: formDataObj
        });
        
        if (!response.ok) {
          throw new Error('Failed to add listing');
        }
        
        const savedProperty = await response.json();
        
        if (savedProperty.success) {
          // Show success message
          submitBtn.textContent = 'Success!';
          submitBtn.style.background = '#1a7f1a';
          submitBtn.style.color = '#fff';
          
          // Reset form
          form.reset();
          
          setTimeout(() => {
            // Refresh the page to show the new property
            location.reload();
          }, 1500);
        } else {
          throw new Error(savedProperty.message || 'Failed to add property');
        }
        
      } catch (err) {
        console.error('Error adding listing:', err);
        alert('Error adding listing: ' + err.message);
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      }
    });

    // Listing management functions
    function editListing(btn) {
      const row = btn.closest('tr');
      const cells = row.querySelectorAll('td');
      const propName = cells[0].textContent;
      const location = cells[1].textContent;
      const price = cells[2].textContent;
      const bedrooms = cells[3].textContent;
      const bathrooms = cells[4].textContent;
      const size = cells[5].textContent;
      
      // Create modal content
      const modalContent = `
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #222;">Edit Listing - ${propName}</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
          </div>
          <form id="editForm" style="display: flex; flex-direction: column; gap: 15px;">
            <div>
              <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Name</label>
              <input type="text" value="${propName}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 5px; font-weight: 500;">Location</label>
              <select style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                <option value="Batangas City" ${location === 'Batangas City' ? 'selected' : ''}>Batangas City</option>
                <option value="Lipa City" ${location === 'Lipa City' ? 'selected' : ''}>Lipa City</option>
                <option value="Tanauan" ${location === 'Tanauan' ? 'selected' : ''}>Tanauan</option>
              </select>
            </div>
            <div>
              <label style="display: block; margin-bottom: 5px; font-weight: 500;">Price</label>
              <input type="number" value="${price.replace(/[^\d]/g, '')}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bedrooms</label>
              <select style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                <option value="0" ${bedrooms === '0' ? 'selected' : ''}>0</option>
                <option value="1" ${bedrooms === '1' ? 'selected' : ''}>1</option>
                <option value="2" ${bedrooms === '2' ? 'selected' : ''}>2</option>
                <option value="3" ${bedrooms === '3' ? 'selected' : ''}>3</option>
                <option value="4+" ${bedrooms === '4' ? 'selected' : ''}>4+</option>
              </select>
            </div>
            <div>
              <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bathrooms</label>
              <select style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                <option value="0" ${bathrooms === '0' ? 'selected' : ''}>0</option>
                <option value="1" ${bathrooms === '1' ? 'selected' : ''}>1</option>
                <option value="2" ${bathrooms === '2' ? 'selected' : ''}>2</option>
                <option value="3" ${bathrooms === '3' ? 'selected' : ''}>3</option>
                <option value="4+" ${bathrooms === '4' ? 'selected' : ''}>4+</option>
              </select>
            </div>
            <div>
              <label style="display: block; margin-bottom: 5px; font-weight: 500;">Size (sqm)</label>
              <input type="number" value="${size}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
              <button type="submit" style="background: #1a7f1a; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; flex: 1;">Save Changes</button>
              <button type="button" onclick="closeModal()" style="background: #666; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; flex: 1;">Cancel</button>
            </div>
          </form>
        </div>
      `;
      
      showModal(modalContent);
      
      // Handle form submission
      document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Show loading state
        const submitBtn = e.target.querySelector('button[type="submit"]');
        submitBtn.textContent = 'Saving...';
        submitBtn.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
          // Update the table row
          const inputs = e.target.querySelectorAll('input, select');
          cells[0].textContent = inputs[0].value;
          cells[1].textContent = inputs[1].value;
          cells[2].textContent = '₱' + parseInt(inputs[2].value).toLocaleString();
          cells[3].textContent = inputs[3].value;
          cells[4].textContent = inputs[4].value;
          cells[5].textContent = inputs[5].value;
          
          closeModal();
          showNotification('Listing updated successfully!', 'success');
        }, 1000);
      });
    }

    function deleteListing(propertyId) {
      const row = document.querySelector(`tr[data-property-id="${propertyId}"]`);
      const propName = row.querySelector('td').textContent;
      
      if (confirm(`Are you sure you want to delete the listing "${propName}"? This action cannot be undone.`)) {
        // Show loading state
        const deleteBtn = row.querySelector('.danger');
        const originalText = deleteBtn.textContent;
        deleteBtn.textContent = 'Deleting...';
        deleteBtn.disabled = true;
        
        // Call backend API
        const formData = new FormData();
        formData.append('property_id', propertyId);
        
        fetch('delete_property.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Show success message
            showNotification(data.message, 'success');
            
            // Remove the row
            row.style.opacity = '0.5';
            row.style.background = '#fff0f0';
            
            // Update button
            deleteBtn.textContent = 'Deleted ✗';
            deleteBtn.style.background = '#c00';
            deleteBtn.style.color = 'white';
            deleteBtn.disabled = true;
            
            // Disable other buttons
            const otherBtns = row.querySelectorAll('button:not(.danger)');
            otherBtns.forEach(btn => {
              btn.disabled = true;
              btn.style.opacity = '0.5';
            });
            
            // Refresh the page after delay
            setTimeout(() => {
              location.reload();
            }, 2000);
          } else {
            throw new Error(data.message || 'Failed to delete property');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error deleting property: ' + error.message);
          deleteBtn.textContent = originalText;
          deleteBtn.disabled = false;
        });
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

    // Property Details Modal
    function viewPropertyDetails(propertyId) {
      fetch(`get_property_details.php?id=${propertyId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const property = data.property;
            const modalContent = `
              <div style="background: white; padding: 30px; border-radius: 15px; max-width: 900px; width: 90%; max-height: 80vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                  <h2 style="margin: 0; color: #222;">Property Post - ${property.title}</h2>
                  <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                  <!-- Left Column: Property Details -->
                  <div>
                    <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">Property Details</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                      <p style="margin: 8px 0;"><strong>Property Name:</strong> ${property.title}</p>
                      <p style="margin: 8px 0;"><strong>Property Type:</strong> ${property.property_type}</p>
                      <p style="margin: 8px 0;"><strong>Price:</strong> ₱${parseInt(property.price).toLocaleString()}</p>
                      <p style="margin: 8px 0;"><strong>Date Posted:</strong> ${new Date(property.created_at).toLocaleDateString()}</p>
                      <p style="margin: 8px 0;"><strong>Location:</strong> ${property.location}</p>
                      <p style="margin: 8px 0;"><strong>Bedrooms:</strong> ${property.bedrooms}</p>
                      <p style="margin: 8px 0;"><strong>Bathrooms:</strong> ${property.bathrooms}</p>
                      <p style="margin: 8px 0;"><strong>Size:</strong> ${property.sqm} sqm</p>
                      <p style="margin: 8px 0;"><strong>Status:</strong> <span style="color: ${property.status === 'sold' ? '#c00' : '#1a7f1a'}; font-weight: 500;">${property.status.toUpperCase()}</span></p>
                    </div>
                  </div>
                  
                  <!-- Right Column: Property Images -->
                  <div>
                    <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">Property Images</h3>
                    ${property.images && property.images.length > 0 ? `
                      <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; min-height: 300px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                          ${property.images.map(img => `
                            <div style="position: relative;">
                              <img src="${img.image_path}" alt="Property Image" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; cursor: pointer;" onclick="openImageModal('${img.image_path}')">
                              <button onclick="deleteImageFromDetails(${img.id}, ${property.id})" style="position: absolute; top: 5px; right: 5px; background: #c00; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; z-index: 10;">×</button>
                            </div>
                          `).join('')}
                        </div>
                        <div style="margin-top: 15px; text-align: center;">
                          <button onclick="addMoreImages(${property.id})" class="glass-btn" style="background: #0074d9; color: white;">
                            <i class="fa-solid fa-plus"></i> Add More Images
                          </button>
                        </div>
                      </div>
                    ` : `
                      <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; min-height: 300px; display: flex; align-items: center; justify-content: center; color: #666;">
                        <div style="text-align: center;">
                          <i class="fa-solid fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 10px; color: #ffc107;"></i>
                          <p><strong>Images Missing</strong></p>
                          <p style="font-size: 0.9rem; margin-bottom: 15px;">This property was created without images. You can add images now.</p>
                          <button onclick="addMoreImages(${property.id})" class="glass-btn" style="background: #0074d9; color: white;">
                            <i class="fa-solid fa-plus"></i> Add Images Now
                          </button>
                        </div>
                      </div>
                    `}
                  </div>
                </div>
                
                <!-- Property Description -->
                <div style="margin-top: 30px;">
                  <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">Property Description</h3>
                  <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <p style="line-height: 1.6; margin: 0;">${property.description}</p>
                  </div>
                </div>
                
                ${property.documents && property.documents.length > 0 ? `
                  <div style="margin-top: 20px;">
                    <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">Property Documents (${property.documents.length})</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                      ${property.documents.map(doc => `
                        <a href="${doc.document_path}" target="_blank" style="display: inline-block; padding: 8px 12px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333;">
                          <i class="fa-solid fa-file"></i> View Document
                        </a>
                      `).join('')}
                    </div>
                  </div>
                ` : ''}
                
                <div style="text-align: right; margin-top: 30px;">
                  <button onclick="editListing(${property.id})" class="glass-btn" style="margin-right: 10px;">Edit Property</button>
                  <button onclick="closeModal()" class="glass-btn danger">Close</button>
                </div>
              </div>
            `;
            showModal(modalContent);
          } else {
            alert('Error loading property details: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error loading property details');
        });
    }

    // Enhanced Edit Listing Function
    function editListing(propertyId) {
      fetch(`get_property_details.php?id=${propertyId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const property = data.property;
            const modalContent = `
              <div style="background: white; padding: 30px; border-radius: 15px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                  <h2 style="margin: 0; color: #222;">Edit Listing - ${property.title}</h2>
                  <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <form id="editPropertyForm" style="display: flex; flex-direction: column; gap: 15px;">
                  <input type="hidden" name="property_id" value="${property.id}">
                  
                  <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Name</label>
                    <input type="text" name="title" value="${property.title}" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                  </div>
                  
                                     <div>
                                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Type</label>
                       <select name="property_type" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                         <option value="Property" ${property.property_type === 'Property' ? 'selected' : ''}>Property</option>
                         <option value="Lot" ${property.property_type === 'Lot' ? 'selected' : ''}>Lot</option>
                       </select>
                   </div>
                   
                   <div>
                     <label style="display: block; margin-bottom: 5px; font-weight: 500;">Location</label>
                     <select name="location" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                      <option value="Agoncillo" ${property.location === 'Agoncillo' ? 'selected' : ''}>Agoncillo</option>
                      <option value="Alitagtag" ${property.location === 'Alitagtag' ? 'selected' : ''}>Alitagtag</option>
                      <option value="Balayan" ${property.location === 'Balayan' ? 'selected' : ''}>Balayan</option>
                      <option value="Balete" ${property.location === 'Balete' ? 'selected' : ''}>Balete</option>
                      <option value="Batangas City" ${property.location === 'Batangas City' ? 'selected' : ''}>Batangas City</option>
                      <option value="Bauan" ${property.location === 'Bauan' ? 'selected' : ''}>Bauan</option>
                      <option value="Calaca" ${property.location === 'Calaca' ? 'selected' : ''}>Calaca</option>
                      <option value="Calatagan" ${property.location === 'Calatagan' ? 'selected' : ''}>Calatagan</option>
                      <option value="Cuenca" ${property.location === 'Cuenca' ? 'selected' : ''}>Cuenca</option>
                      <option value="Ibaan" ${property.location === 'Ibaan' ? 'selected' : ''}>Ibaan</option>
                      <option value="Laurel" ${property.location === 'Laurel' ? 'selected' : ''}>Laurel</option>
                      <option value="Lemery" ${property.location === 'Lemery' ? 'selected' : ''}>Lemery</option>
                      <option value="Lian" ${property.location === 'Lian' ? 'selected' : ''}>Lian</option>
                      <option value="Lipa City" ${property.location === 'Lipa City' ? 'selected' : ''}>Lipa City</option>
                      <option value="Lobo" ${property.location === 'Lobo' ? 'selected' : ''}>Lobo</option>
                      <option value="Mabini" ${property.location === 'Mabini' ? 'selected' : ''}>Mabini</option>
                      <option value="Malvar" ${property.location === 'Malvar' ? 'selected' : ''}>Malvar</option>
                      <option value="Mataasnakahoy" ${property.location === 'Mataasnakahoy' ? 'selected' : ''}>Mataasnakahoy</option>
                      <option value="Nasugbu" ${property.location === 'Nasugbu' ? 'selected' : ''}>Nasugbu</option>
                      <option value="Padre Garcia" ${property.location === 'Padre Garcia' ? 'selected' : ''}>Padre Garcia</option>
                      <option value="Rosario" ${property.location === 'Rosario' ? 'selected' : ''}>Rosario</option>
                      <option value="San Jose" ${property.location === 'San Jose' ? 'selected' : ''}>San Jose</option>
                      <option value="San Juan" ${property.location === 'San Juan' ? 'selected' : ''}>San Juan</option>
                      <option value="San Luis" ${property.location === 'San Luis' ? 'selected' : ''}>San Luis</option>
                      <option value="San Nicolas" ${property.location === 'San Nicolas' ? 'selected' : ''}>San Nicolas</option>
                      <option value="San Pascual" ${property.location === 'San Pascual' ? 'selected' : ''}>San Pascual</option>
                      <option value="Santa Teresita" ${property.location === 'Santa Teresita' ? 'selected' : ''}>Santa Teresita</option>
                      <option value="Santo Tomas" ${property.location === 'Santo Tomas' ? 'selected' : ''}>Santo Tomas</option>
                      <option value="Taal" ${property.location === 'Taal' ? 'selected' : ''}>Taal</option>
                      <option value="Talisay" ${property.location === 'Talisay' ? 'selected' : ''}>Talisay</option>
                      <option value="Tanauan City" ${property.location === 'Tanauan City' ? 'selected' : ''}>Tanauan City</option>
                      <option value="Taysan" ${property.location === 'Taysan' ? 'selected' : ''}>Taysan</option>
                      <option value="Tingloy" ${property.location === 'Tingloy' ? 'selected' : ''}>Tingloy</option>
                      <option value="Tuy" ${property.location === 'Tuy' ? 'selected' : ''}>Tuy</option>
                    </select>
                  </div>
                  
                  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                      <label style="display: block; margin-bottom: 5px; font-weight: 500;">Price</label>
                      <input type="number" name="price" value="${property.price}" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div>
                                             <label style="display: block; margin-bottom: 5px; font-weight: 500;">Status</label>
                       <select name="status" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                         <option value="available" ${property.status === 'available' ? 'selected' : ''}>Available</option>
                         <option value="sold" ${property.status === 'sold' ? 'selected' : ''}>Sold</option>
                       </select>
                    </div>
                  </div>
                  
                  <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div>
                                             <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bedrooms</label>
                       <select name="bedrooms" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                         <option value="1" ${property.bedrooms == 1 ? 'selected' : ''}>1</option>
                         <option value="2" ${property.bedrooms == 2 ? 'selected' : ''}>2</option>
                         <option value="3" ${property.bedrooms == 3 ? 'selected' : ''}>3</option>
                         <option value="4+" ${property.bedrooms == '4+' ? 'selected' : ''}>4+</option>
                       </select>
                    </div>
                    <div>
                                             <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bathrooms</label>
                       <select name="bathrooms" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                         <option value="1" ${property.bathrooms == 1 ? 'selected' : ''}>1</option>
                         <option value="2" ${property.bathrooms == 2 ? 'selected' : ''}>2</option>
                         <option value="3" ${property.bathrooms == 3 ? 'selected' : ''}>3</option>
                         <option value="4+" ${property.bathrooms == '4+' ? 'selected' : ''}>4+</option>
                       </select>
                    </div>
                    <div>
                      <label style="display: block; margin-bottom: 5px; font-weight: 500;">Size (sqm)</label>
                      <input type="number" name="sqm" value="${property.sqm}" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                  </div>
                  
                  <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                    <textarea name="description" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; resize: vertical;">${property.description}</textarea>
                  </div>
                  
                  ${property.images && property.images.length > 0 ? `
                    <div>
                      <label style="display: block; margin-bottom: 5px; font-weight: 500;">Current Images (${property.images.length})</label>
                      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-bottom: 10px;">
                        ${property.images.map(img => `
                          <div style="position: relative;">
                            <img src="${img.image_path}" alt="Property Image" style="width: 100%; height: 100px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;">
                          </div>
                        `).join('')}
                      </div>
                    </div>
                  ` : ''}
                  
                  <div style="background: #f8f8f8; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <p style="margin: 0; color: #666; font-style: italic;">Image and document editing has been simplified. Use "Add More Images" from the property details to add new images.</p>
                  </div>
                  
                  <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="glass-btn" style="background: #1a7f1a; color: white;">Save Changes</button>
                    <button type="button" onclick="closeModal()" class="glass-btn danger">Cancel</button>
                  </div>
                </form>
              </div>
            `;
            showModal(modalContent);
            
            // Add form submission handler
            document.getElementById('editPropertyForm').addEventListener('submit', function(e) {
              e.preventDefault();
              updateProperty(this);
            });
          } else {
            alert('Error loading property details: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error loading property details');
        });
    }

    // Update Property Function
    function updateProperty(form) {
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      
      submitBtn.textContent = 'Saving...';
      submitBtn.disabled = true;
      
      // Create a simple object with only the form data we need
      const formData = new FormData();
      
      // Get form elements with error checking
      const propertyIdInput = form.querySelector('input[name="property_id"]');
      const titleInput = form.querySelector('input[name="title"]');
      const descriptionInput = form.querySelector('textarea[name="description"]');
      const locationInput = form.querySelector('select[name="location"]');
      const propertyTypeInput = form.querySelector('select[name="property_type"]');
      const priceInput = form.querySelector('input[name="price"]');
      const bedroomsInput = form.querySelector('select[name="bedrooms"]');
      const bathroomsInput = form.querySelector('select[name="bathrooms"]');
      const sqmInput = form.querySelector('input[name="sqm"]');
      const statusInput = form.querySelector('select[name="status"]');
      
      // Check if all elements exist
      if (!propertyIdInput || !titleInput || !descriptionInput || !locationInput || 
          !propertyTypeInput || !priceInput || !bedroomsInput || !bathroomsInput || 
          !sqmInput || !statusInput) {
        console.error('Missing form elements:', {
          propertyIdInput: !!propertyIdInput,
          titleInput: !!titleInput,
          descriptionInput: !!descriptionInput,
          locationInput: !!locationInput,
          propertyTypeInput: !!propertyTypeInput,
          priceInput: !!priceInput,
          bedroomsInput: !!bedroomsInput,
          bathroomsInput: !!bathroomsInput,
          sqmInput: !!sqmInput,
          statusInput: !!statusInput
        });
        throw new Error('Some form elements are missing');
      }
      
      formData.append('property_id', propertyIdInput.value);
      formData.append('title', titleInput.value);
      formData.append('description', descriptionInput.value);
      formData.append('location', locationInput.value);
      formData.append('property_type', propertyTypeInput.value);
      formData.append('price', priceInput.value);
      formData.append('bedrooms', bedroomsInput.value);
      formData.append('bathrooms', bathroomsInput.value);
      formData.append('sqm', sqmInput.value);
      formData.append('status', statusInput.value);
      
      fetch('update_property_simple.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          showNotification('Property updated successfully!', 'success');
          closeModal();
          // Refresh the page to show updated data
          setTimeout(() => {
            location.reload();
          }, 1000);
        } else {
          throw new Error(data.message || 'Failed to update property');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error updating property: ' + error.message);
      })
      .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      });
    }

    // Logout Function
    function logout() {
      if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
      }
    }

    // Add More Images Function
    function addMoreImages(propertyId) {
      const modalContent = `
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 500px; width: 90%;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #222;">Add More Images</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
          </div>
          
          <form id="addImagesForm" style="display: flex; flex-direction: column; gap: 15px;">
            <input type="hidden" name="property_id" value="${propertyId}">
            
            <div>
              <label style="display: block; margin-bottom: 5px; font-weight: 500;">Select Images</label>
              <input type="file" name="new_images[]" multiple accept="image/*" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
              <small style="color: #666; font-size: 12px;">Only JPG, PNG, GIF images accepted (max 5MB each)</small>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
              <button type="submit" class="glass-btn" style="background: #1a7f1a; color: white;">Add Images</button>
              <button type="button" onclick="closeModal()" class="glass-btn danger">Cancel</button>
            </div>
          </form>
        </div>
      `;
      showModal(modalContent);
      
      // Add form submission handler
      document.getElementById('addImagesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addImagesToProperty(this);
      });
    }

    // Add Images to Property Function
    function addImagesToProperty(form) {
      const formData = new FormData(form);
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      
      submitBtn.textContent = 'Adding...';
      submitBtn.disabled = true;
      
      fetch('add_images_to_property.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          showNotification('Images added successfully!', 'success');
          closeModal();
          // Refresh the page to show new images
          setTimeout(() => {
            location.reload();
          }, 1000);
        } else {
          throw new Error(data.message || 'Failed to add images');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error adding images: ' + error.message);
      })
      .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      });
    }

    // Open Image Modal Function
    function openImageModal(imagePath) {
      const modalContent = `
        <div style="background: rgba(0,0,0,0.9); padding: 20px; border-radius: 10px; max-width: 90vw; max-height: 90vh; display: flex; align-items: center; justify-content: center;">
          <div style="position: relative;">
            <img src="${imagePath}" alt="Property Image" style="max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 8px;">
            <button onclick="closeModal()" style="position: absolute; top: -40px; right: 0; background: none; border: none; font-size: 24px; cursor: pointer; color: white;">×</button>
          </div>
        </div>
      `;
      showModal(modalContent);
    }

    // Delete Image Function
    function deleteImage(imageId) {
      if (confirm('Are you sure you want to delete this image?')) {
        // Add to form data for deletion
        const form = document.getElementById('editPropertyForm');
        let deleteImagesInput = form.querySelector('input[name="delete_images[]"]');
        if (!deleteImagesInput) {
          deleteImagesInput = document.createElement('input');
          deleteImagesInput.type = 'hidden';
          deleteImagesInput.name = 'delete_images[]';
          form.appendChild(deleteImagesInput);
        }
        deleteImagesInput.value = imageId;
        
        // Remove the image from display
        const imageContainer = event.target.closest('div');
        imageContainer.remove();
      }
    }

    // Delete Document Function
    function deleteDocument(docId) {
      if (confirm('Are you sure you want to delete this document?')) {
        // Add to form data for deletion
        const form = document.getElementById('editPropertyForm');
        let deleteDocsInput = form.querySelector('input[name="delete_documents[]"]');
        if (!deleteDocsInput) {
          deleteDocsInput = document.createElement('input');
          deleteDocsInput.type = 'hidden';
          deleteDocsInput.name = 'delete_documents[]';
          form.appendChild(deleteDocsInput);
        }
        deleteDocsInput.value = docId;
        
        // Remove the document from display
        const docContainer = event.target.closest('div');
        docContainer.remove();
      }
    }

    // Delete Image from Details Modal Function
    function deleteImageFromDetails(imageId, propertyId) {
      if (confirm('Are you sure you want to delete this image?')) {
        fetch('delete_image.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            image_id: imageId,
            property_id: propertyId
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification('Image deleted successfully!', 'success');
            // Refresh the property details modal
            viewPropertyDetails(propertyId);
            // Update the property card image
            updatePropertyCardImage(propertyId);
          } else {
            throw new Error(data.message || 'Failed to delete image');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error deleting image: ' + error.message);
        });
      }
    }

    // Update Property Card Image Function
    function updatePropertyCardImage(propertyId) {
      // Find the property card
      const propertyCard = document.querySelector(`[data-property-id="${propertyId}"]`);
      if (propertyCard) {
        const propertyImage = propertyCard.querySelector('.property-image');
        if (propertyImage) {
          // Fetch the updated property details to get the new first image
          fetch(`get_property_details.php?id=${propertyId}`)
            .then(response => response.json())
            .then(data => {
              if (data.success && data.property.images && data.property.images.length > 0) {
                // Update with the first available image
                propertyImage.style.backgroundImage = `url('${data.property.images[0].image_path}')`;
              } else {
                // No images left, show placeholder
                propertyImage.style.backgroundImage = 'none';
                propertyImage.style.backgroundColor = '#f0f0f0';
              }
            })
            .catch(error => {
              console.error('Error updating property card image:', error);
            });
        }
      }
    }

    // Cancel button handling
    document.querySelector('.glass-btn.danger').addEventListener('click', function() {
      if (confirm('Are you sure you want to cancel? All entered data will be lost.')) {
        document.querySelector('.add-listing-form').reset();
      }
    });
  </script>
</body>
</html> 