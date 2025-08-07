<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an associate agent
$is_logged_in = is_logged_in();
$current_user = null;
$is_associate_agent = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_associate_agent = ($current_user && $current_user['user_type'] === 'associate_agent');
}

// Redirect if not logged in or not an associate agent
if (!$is_logged_in || !$is_associate_agent) {
    header('Location: index.php');
    exit;
}

// Get agent's information including company details
$agent_query = "SELECT a.*, c.name as company_name, c.description as company_description 
                FROM agents a 
                LEFT JOIN companies c ON a.company_id = c.id 
                WHERE a.user_id = ?";
$stmt = mysqli_prepare($conn, $agent_query);
mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
mysqli_stmt_execute($stmt);
$agent_result = mysqli_stmt_get_result($stmt);
$agent = mysqli_fetch_assoc($agent_result);

if (!$agent) {
    header('Location: index.php');
    exit;
}

// Get agent's properties count
$agent_id = $agent['id'];
$properties_query = "SELECT COUNT(*) as total FROM properties WHERE agent_id = ?";
$stmt = mysqli_prepare($conn, $properties_query);
mysqli_stmt_bind_param($stmt, "i", $agent_id);
mysqli_stmt_execute($stmt);
$properties_result = mysqli_stmt_get_result($stmt);
$properties_count = mysqli_fetch_assoc($properties_result)['total'];

// Get recent properties
$recent_properties_query = "SELECT * FROM properties WHERE agent_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_properties_query);
mysqli_stmt_bind_param($stmt, "i", $agent_id);
mysqli_stmt_execute($stmt);
$recent_properties = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Associate Agent Dashboard | BatEstateExplorer</title>
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
      <h1 class="logo">BatEstateExplorer <span style="font-size:0.9rem; background:#0074d9; color:#fff; border-radius:8px; padding:2px 10px; margin-left:10px; vertical-align:middle;">Associate Agent</span></h1>
      <nav>
        <ul>
          <li><a href="associate_agent_index.php">Home</a></li>
          <li><a href="associate_agent_search.php">Properties</a></li>
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
          <span class="profile-location"><?php echo htmlspecialchars($agent['location'] ?? 'Location not specified'); ?></span>
          <span class="profile-company"><?php echo htmlspecialchars($agent['company_name'] ?? 'Company not assigned'); ?></span>
        </div>
      </div>
      <div class="profile-actions">
        <button class="dots-btn" id="profileDotsBtn" title="Options">
          <i class="fa-solid fa-ellipsis"></i>
        </button>
        <div class="popup-menu" id="profileDropdownMenu">
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
        </div>
      </div>
    </div>
    <div class="profile-stats">
      <div class="profile-stat">Listings<br><b id="statListings"><?php echo $properties_count; ?></b></div>
      <div class="profile-stat">Reviews<br><b>4.8 ★</b></div>
      <div class="profile-stat">Inquiries<br><b>12</b></div>
    </div>
    <!-- Tab Bar -->
    <div class="tab-bar">
      <span class="tab active" id="portfolioTab">Portfolio</span>
      <span class="tab" id="listingsTab">Company Listings</span>
      <span class="tab" id="addTab">Add Listing</span>
      <span class="tab" id="messagesTab">Messages</span>
      <span class="tab" id="myListingsTab">My Listings</span>
      <span class="tab" id="analyticsTab">Analytics/Reviews</span>
    </div>
    <!-- Section Content -->
    <div class="section-content" id="portfolioSection">
      <div class="property-list" id="portfolioPropertyList">
        <!-- Company properties will be loaded here dynamically -->
      </div>
    </div>
    <div class="section-content" id="listingsSection" style="display:none;">
      <div class="glass-card" style="background:rgba(255,255,255,0.25); backdrop-filter:blur(12px); border-radius:18px; box-shadow:0 4px 16px rgba(0,0,0,0.07); padding:32px 24px;">
        <h2 style="font-size:1.2rem; color:#222; margin-bottom:18px;">Company Listings</h2>
        <table class="listings-table" style="width:100%; border-collapse:collapse; background:rgba(255,255,255,0.5); border-radius:14px; overflow:hidden;">
          <thead style="background:rgba(255,255,255,0.4);">
            <tr><th>Property</th><th>Location</th><th>Price</th><th>Bedrooms</th><th>Bathrooms</th><th>Size (sqm)</th><th>Status</th><th>Created By</th><th>Sold By</th><th>Actions</th></tr>
          </thead>
          <tbody id="companyListingsTableBody"></tbody>
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
    <div class="section-content" id="myListingsSection" style="display:none;">
      <div class="glass-card" style="background:rgba(255,255,255,0.25); backdrop-filter:blur(12px); border-radius:18px; box-shadow:0 4px 16px rgba(0,0,0,0.07); padding:32px 24px;">
        <h2 style="font-size:1.2rem; color:#222; margin-bottom:18px;">My Listings</h2>
        <table class="listings-table" style="width:100%; border-collapse:collapse; background:rgba(255,255,255,0.5); border-radius:14px; overflow:hidden;">
          <thead style="background:rgba(255,255,255,0.4);">
            <tr><th>Property</th><th>Location</th><th>Price</th><th>Bedrooms</th><th>Bathrooms</th><th>Size (sqm)</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody id="myListingsTableBody"></tbody>
        </table>
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
          <!-- Only show reviews for sold properties by this agent -->
          <!-- Example review card -->
          <div class="review-card" style="background:#f8f8f8; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:16px 20px; min-width:260px; max-width:340px; flex:1;">
            <div style="font-weight:600; color:#0074d9; margin-bottom:4px;">Maria Buyer <span style="color:#FFD700; font-size:1.1rem; margin-left:6px;">★★★★★</span></div>
            <div style="color:#222;">Great experience, very responsive and helpful!</div>
          </div>
        </div>
        <div style="margin-top:8px; font-size:1.05rem; color:#555;">Listings Status: <b id="statAvailable">0 Available</b>, <b id="statSold">0 Sold</b></div>
      </div>
    </div>
  </div>
  <script>
    console.log('=== ASSOCIATE AGENT DASHBOARD LOADED - VERSION WITH ENHANCED DEBUGGING ===');
    console.log('=== VERSION 2.0 - CACHE BUSTER ===');
    
    // Global variables
    let properties = [];
    let company = {};
    let loggedInAgent = { id: <?php echo $agent['id']; ?> };

    // Load company listings data
    async function loadCompanyListings() {
      try {
        const response = await fetch('get_company_listings.php');
        const data = await response.json();
        
        if (data.success) {
          properties = data.properties;
          company = { name: data.company_name };
          renderPortfolio();
          renderListings();
          renderMyListings();
        } else {
          console.error('Error loading company listings:', data.message);
          alert('Error loading company listings: ' + data.message);
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error loading company listings');
      }
    }

    // Load data on page load
    loadCompanyListings();

    // Portfolio: Show available properties as cards
    function renderPortfolio() {
      const list = document.getElementById('portfolioPropertyList');
      list.innerHTML = '';
      
      const availableProperties = properties.filter(p => p.status === 'available');
      
      if (availableProperties.length === 0) {
        list.innerHTML = '<p style="text-align:center; color:#666; padding:40px;">No available properties found.</p>';
        return;
      }
      
      availableProperties.forEach(p => {
        const card = document.createElement('div');
        card.className = 'property-card';
        card.innerHTML = `
          <div class="property-image" style="background-image: url('${p.main_image || 'Pictures/bg4.jpg'}');">
            <div class="property-badge">${p.property_type}</div>
          </div>
          <div class="property-name">${p.title}</div>
          <div class="property-meta">${p.location}</div>
          <div class="property-price">₱${parseInt(p.price).toLocaleString()}</div>
          <div class="property-actions">
            <button class="details-btn" onclick="viewPropertyDetails(${p.id}, 'portfolio')">Details</button>
          </div>
        `;
        list.appendChild(card);
      });
    }

    // Listings Table: Show all company properties
    function renderListings() {
      const tbody = document.getElementById('companyListingsTableBody');
      tbody.innerHTML = '';
      let available = 0, sold = 0;
      
      properties.forEach(p => {
        if (p.status === 'available') available++; else sold++;
        tbody.innerHTML += `
          <tr data-property-id="${p.id}">
            <td>${p.title}</td>
            <td>${p.location}</td>
            <td>₱${parseInt(p.price).toLocaleString()}</td>
            <td>${p.bedrooms}</td>
            <td>${p.bathrooms}</td>
            <td>${p.sqm}</td>
            <td style="color:${p.status==='sold'?'#c00':'#1a7f1a'}; font-weight:500;">${p.status.charAt(0).toUpperCase()+p.status.slice(1)}</td>
            <td>${p.created_by_name}</td>
            <td>${p.sold_by_name || 'N/A'}</td>
            <td style="display: flex; gap: 8px; align-items: center;">
              <button class="glass-btn" onclick="viewPropertyDetails(${p.id}, 'company')">Details</button>
            </td>
          </tr>
        `;
      });
      
      document.getElementById('statListings').textContent = available+sold;
      document.getElementById('statAvailable').textContent = available+" Available";
      document.getElementById('statSold').textContent = sold+" Sold";
    }

    // My Listings: Show agent's own properties
    function renderMyListings() {
      const tbody = document.getElementById('myListingsTableBody');
      tbody.innerHTML = '';
      
      console.log('=== RENDER MY LISTINGS DEBUG ===');
      console.log('loggedInAgent:', loggedInAgent);
      console.log('loggedInAgent.id:', loggedInAgent.id);
      console.log('All properties:', properties);
      console.log('Properties count:', properties.length);
      
      // Get agent's own properties
      const myProperties = properties.filter(p => {
        console.log(`Checking property ${p.id}: agent_id = ${p.agent_id}, loggedInAgent.id = ${loggedInAgent.id}, match = ${p.agent_id === loggedInAgent.id}`);
        return p.agent_id === loggedInAgent.id;
      });
      
      console.log('My properties after filter:', myProperties);
      console.log('My properties count:', myProperties.length);
      
      if (myProperties.length === 0) {
        console.log('No properties found for this agent');
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #666;">No properties found. Click "Add Listing" to get started!</td></tr>';
        return;
      }
      
      myProperties.forEach(p => {
        console.log(`Rendering property ${p.id}: ${p.title}`);
        const status_color = p.status === 'sold' ? '#c00' : '#1a7f1a';
        const rowHTML = `
          <tr data-property-id="${p.id}">
            <td>${p.title}</td>
            <td>${p.location}</td>
            <td>₱${parseInt(p.price).toLocaleString()}</td>
            <td>${p.bedrooms}</td>
            <td>${p.bathrooms}</td>
            <td>${p.sqm}</td>
            <td style="color:${status_color}; font-weight:500;">${p.status.charAt(0).toUpperCase()+p.status.slice(1)}</td>
            <td style="display: flex; gap: 8px; align-items: center; min-width: 200px;">
              <button class="glass-btn" data-action="details" data-property-id="${p.id}" data-section="my">Details</button>
              <button class="glass-btn" data-action="edit" data-property-id="${p.id}">Edit</button>
              <button class="glass-btn danger" data-action="delete" data-property-id="${p.id}">Delete</button>
            </td>
          </tr>
        `;
        console.log(`Generated HTML for property ${p.id}:`, rowHTML);
        
        // Add the row to the table
        tbody.innerHTML += rowHTML;
      });
      
      // Add a small delay to ensure DOM is ready, then verify buttons
      setTimeout(() => {
        console.log('=== VERIFYING BUTTONS AFTER RENDER ===');
        const allRows = tbody.querySelectorAll('tr[data-property-id]');
        allRows.forEach(row => {
          const propertyId = row.getAttribute('data-property-id');
          const buttons = row.querySelectorAll('button');
          console.log(`Row ${propertyId} has ${buttons.length} buttons:`, buttons);
          buttons.forEach((btn, index) => {
            console.log(`Button ${index}:`, {
              text: btn.textContent,
              classes: btn.className,
              onclick: btn.getAttribute('onclick')
            });
          });
        });
      }, 100);
      
      console.log('=== END RENDER MY LISTINGS DEBUG ===');
    }
    // Tab switching logic
    const tabIds = [
      'portfolioTab',
      'listingsTab',
      'addTab',
      'messagesTab',
      'myListingsTab',
      'analyticsTab'
    ];
    const sectionIds = [
      'portfolioSection',
      'listingsSection',
      'addSection',
      'messagesSection',
      'myListingsSection',
      'analyticsSection'
    ];
    function hideAllSections() {
      sectionIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
      });
      tabIds.forEach(id => {
        const tab = document.getElementById(id);
        if (tab) tab.classList.remove('active');
      });
    }
    tabIds.forEach((tabId, idx) => {
      const tab = document.getElementById(tabId);
      if (tab) {
        tab.addEventListener('click', () => {
          hideAllSections();
          tab.classList.add('active');
          const section = document.getElementById(sectionIds[idx]);
          if (section) section.style.display = '';
        });
      }
    });
    // Show Portfolio by default
    hideAllSections();
    document.getElementById('portfolioTab').classList.add('active');
    document.getElementById('portfolioSection').style.display = '';

    // Triple dot menu logic
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
    // Real backend integration for Add Listing
    async function addListingToAll(property) {
      try {
        // Send to backend API
        const response = await fetch('add_property.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(property)
        });
        if (!response.ok) throw new Error('Failed to add listing');
        const savedProperty = await response.json();
        // Update local UI (Portfolio, My Listings, Company Listings, and all search pages)
        properties.push(savedProperty);
        renderPortfolio();
        renderListings();
        renderMyListings();
        // Optionally, trigger a refresh in user_search.html, direct_agent_search.html, associate_agent_search.html
        // (If using a shared state or event system)
        showNotification('Listing added successfully!', 'success');
      } catch (err) {
        showNotification('Error adding listing: ' + err.message, 'danger');
      }
    }

    // Add Listing form submission
    document.querySelector('.add-listing-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      // Get form data
      const formData = {
        title: document.getElementById('addName').value,
        description: document.getElementById('addDesc').value,
        location: document.getElementById('addLocation').value,
        property_type: document.getElementById('addType').value,
        price: document.getElementById('addPrice').value,
        bedrooms: document.getElementById('addBedrooms').value,
        bathrooms: document.getElementById('addBathrooms').value,
        sqm: document.getElementById('addLotSize').value,
        features: `${document.getElementById('addBedrooms').value} BR, ${document.getElementById('addBathrooms').value} BA, ${document.getElementById('addLotSize').value} sqm`
      };
      
      // Validate required fields
      const requiredFields = ['title', 'description', 'location', 'property_type', 'price', 'bedrooms', 'bathrooms', 'sqm'];
      const missingFields = requiredFields.filter(field => !formData[field]);
      
      if (missingFields.length > 0) {
        alert('Please fill in all required fields: ' + missingFields.join(', '));
        return;
      }
      
      // Show loading state
      const submitBtn = e.target.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Adding...';
      submitBtn.disabled = true;
      
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
          e.target.reset();
          
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
                  
                  <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bedrooms</label>
                    <select name="bedrooms" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                      <option value="1" ${property.bedrooms === '1' ? 'selected' : ''}>1</option>
                      <option value="2" ${property.bedrooms === '2' ? 'selected' : ''}>2</option>
                      <option value="3" ${property.bedrooms === '3' ? 'selected' : ''}>3</option>
                      <option value="4+" ${property.bedrooms === '4+' ? 'selected' : ''}>4+</option>
                    </select>
                  </div>
                  
                  <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bathrooms</label>
                    <select name="bathrooms" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                      <option value="1" ${property.bathrooms === '1' ? 'selected' : ''}>1</option>
                      <option value="2" ${property.bathrooms === '2' ? 'selected' : ''}>2</option>
                      <option value="3" ${property.bathrooms === '3' ? 'selected' : ''}>3</option>
                      <option value="4+" ${property.bathrooms === '4+' ? 'selected' : ''}>4+</option>
                    </select>
                  </div>
                  
                  <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Size (sqm)</label>
                    <input type="number" name="sqm" value="${property.sqm}" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                  </div>
                  
                  <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                    <textarea name="description" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; min-height: 100px; resize: vertical;">${property.description}</textarea>
                  </div>
                  
                  <div style="margin-top: 20px;">
                    <h4 style="margin-bottom: 10px; color: #333;">Current Images (${property.images ? property.images.length : 0})</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                      <p style="margin: 0; color: #666; font-style: italic;">Image and document editing has been simplified. Use 'Add More Images' from the property details to add new images.</p>
                    </div>
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
            document.getElementById('editPropertyForm').addEventListener('submit', function(e) {
              e.preventDefault();
              
              const formData = new FormData(e.target);
              
              // Show loading state
              const submitBtn = e.target.querySelector('button[type="submit"]');
              const originalText = submitBtn.textContent;
              submitBtn.textContent = 'Saving...';
              submitBtn.disabled = true;
              
              fetch('update_property_simple.php', {
                method: 'POST',
                body: formData
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  closeModal();
                  showNotification('Property updated successfully!', 'success');
                  // Reload the page to refresh data
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
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
              });
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

    // Property Details Modal
    function viewPropertyDetails(propertyId, section = 'default') {
      fetch(`get_property_details.php?id=${propertyId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const property = data.property;
            
            // Determine if this is a view-only modal (Company Listings section)
            const isViewOnly = section === 'company';
            
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
                      ${property.status === 'sold' && property.sold_by_name ? `<p style="margin: 8px 0;"><strong>Sold By:</strong> ${property.sold_by_name}</p>` : ''}
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
                              ${!isViewOnly ? `<button onclick="deleteImageFromDetails(${img.id}, ${property.id})" style="position: absolute; top: 5px; right: 5px; background: #c00; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; z-index: 10;">×</button>` : ''}
                            </div>
                          `).join('')}
                        </div>
                        ${!isViewOnly ? `
                          <div style="margin-top: 15px; text-align: center;">
                            <button onclick="addMoreImages(${property.id})" class="glass-btn" style="background: #0074d9; color: white;">
                              <i class="fa-solid fa-plus"></i> Add More Images
                            </button>
                          </div>
                        ` : ''}
                      </div>
                    ` : `
                      <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; min-height: 300px; display: flex; align-items: center; justify-content: center; color: #666;">
                        <div style="text-align: center;">
                          <i class="fa-solid fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 10px; color: #ffc107;"></i>
                          <p><strong>Images Missing</strong></p>
                          <p style="font-size: 0.9rem; margin-bottom: 15px;">This property was created without images.${!isViewOnly ? ' You can add images now.' : ''}</p>
                          ${!isViewOnly ? `
                            <button onclick="addMoreImages(${property.id})" class="glass-btn" style="background: #0074d9; color: white;">
                              <i class="fa-solid fa-plus"></i> Add Images Now
                            </button>
                          ` : ''}
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
                
                <div style="text-align: center; margin-top: 30px;">
                  ${!isViewOnly && property.status === 'available' ? 
                    `<button onclick="markAsSold(${property.id})" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin-right: 10px; cursor: pointer;">
                       <i class="fa-solid fa-check"></i> Mark as Sold
                     </button>` : ''
                  }
                  ${!isViewOnly && property.status === 'sold' ? 
                    `<button onclick="markAsSold(${property.id})" style="background: #ffc107; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin-right: 10px; cursor: pointer;">
                       <i class="fa-solid fa-undo"></i> Revert to Available
                     </button>` : ''
                  }
                  ${!isViewOnly ? 
                    `<button onclick="editListing(${property.id})" class="glass-btn" style="margin-right: 10px;">Edit Property</button>` : ''
                  }
                  <button onclick="closeModal()" style="background: #666; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">Close</button>
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

    // Modal functions
    function showNotification(message, type = 'info') {
      const notification = document.createElement('div');
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#1a7f1a' : type === 'error' ? '#c00' : '#0074d9'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-size: 14px;
        max-width: 300px;
        word-wrap: break-word;
      `;
      notification.textContent = message;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        notification.style.transition = 'all 0.3s ease';
        setTimeout(() => {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 300);
      }, 3000);
    }

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
        z-index: 10000;
        padding: 20px;
        box-sizing: border-box;
      `;
      modal.innerHTML = content;
      document.body.appendChild(modal);
      
      // Close modal when clicking outside
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          closeModal();
        }
      });
    }

    function closeModal() {
      const modal = document.getElementById('modal');
      if (modal) {
        modal.remove();
      }
    }

    function openImageModal(imagePath) {
      const modalContent = `
        <div style="background: white; padding: 20px; border-radius: 15px; max-width: 80%; max-height: 80%; text-align: center;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #222;">Property Image</h3>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
          </div>
          <img src="${imagePath}" alt="Property Image" style="max-width: 100%; max-height: 60vh; object-fit: contain; border-radius: 8px;">
        </div>
      `;
      showModal(modalContent);
    }

    // Mark property as sold
    function markAsSold(propertyId) {
      // First, get the current property status
      fetch(`get_property_details.php?id=${propertyId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const property = data.property;
            const isCurrentlySold = property.status === 'sold';
            const action = isCurrentlySold ? 'revert to available' : 'mark as sold';
            
            if (confirm(`Are you sure you want to ${action} this property?`)) {
              const formData = new FormData();
              formData.append('property_id', propertyId);
              formData.append('action', isCurrentlySold ? 'revert' : 'mark_sold');
              
              fetch('mark_property_sold.php', {
                method: 'POST',
                body: formData
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  showNotification(data.message, 'success');
                  // Reload the page to refresh data
                  setTimeout(() => {
                    location.reload();
                  }, 1000);
                } else {
                  throw new Error(data.message || 'Failed to update property status');
                }
              })
              .catch(error => {
                console.error('Error:', error);
                alert('Error updating property status: ' + error.message);
              });
            }
          } else {
            alert('Error loading property details: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error loading property details');
        });
    }

    // Delete listing function - ROBUST VERSION
    async function deleteListing(propertyId) {
      console.log('=== DELETE LISTING CALLED ===');
      console.log('Property ID:', propertyId);
      
      try {
        const response = await fetch('delete_property.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ property_id: propertyId })
        });
        
        const result = await response.json();
        
        if (result.success) {
          // Reload the listings silently (no alert)
          loadCompanyListings();
        } else {
          console.error('Error deleting listing:', result.message);
        }
      } catch (error) {
        console.error('Error:', error);
      }
    }
    
    // Add event delegation for delete buttons
    document.addEventListener('DOMContentLoaded', function() {
      console.log('=== SETTING UP EVENT DELEGATION ===');
      
      // Use event delegation for delete buttons
      document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('danger') && e.target.textContent === 'Delete') {
          e.preventDefault();
          const row = e.target.closest('tr[data-property-id]');
          if (row) {
            const propertyId = row.getAttribute('data-property-id');
            console.log('Delete button clicked via event delegation for property:', propertyId);
            deleteListing(propertyId);
          }
        }
      });
    });

    function deleteImageFromDetails(imageId, propertyId) {
      const formData = new FormData();
      formData.append('image_id', imageId);
      formData.append('property_id', propertyId);
      
      fetch('delete_image.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload the page to refresh data silently
          setTimeout(() => {
            location.reload();
          }, 1000);
        } else {
          throw new Error(data.message || 'Failed to delete image');
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
    }

    function addMoreImages(propertyId) {
      const modalContent = `
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 600px; width: 90%;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #222;">Add More Images</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
          </div>
          <form id="addImagesForm" enctype="multipart/form-data">
            <input type="hidden" name="property_id" value="${propertyId}">
            <div style="margin-bottom: 20px;">
              <label style="display: block; margin-bottom: 10px; font-weight: 500;">Select Images</label>
              <input type="file" name="new_images[]" multiple accept="image/*" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
              <button type="submit" style="background: #0074d9; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Upload Images</button>
              <button type="button" onclick="closeModal()" style="background: #666; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Cancel</button>
            </div>
          </form>
        </div>
      `;
      showModal(modalContent);
      
      // Handle form submission
      document.getElementById('addImagesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Uploading...';
        submitBtn.disabled = true;
        
        fetch('add_images_to_property.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            closeModal();
            showNotification('Images uploaded successfully!', 'success');
            // Reload the page to refresh data
            setTimeout(() => {
              location.reload();
            }, 1000);
          } else {
            throw new Error(data.message || 'Failed to upload images');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error uploading images: ' + error.message);
          submitBtn.textContent = originalText;
          submitBtn.disabled = false;
        });
      });
    }
  </script>
</body>
</html> 