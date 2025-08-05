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

// Get agent's properties count
$agent_id = $current_user['id'];
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
          <img src="Pictures/company_logo.png" alt="Company Logo" style="width:60px;height:60px;border-radius:50%;background:#fff;margin-right:10px;">
        </div>
        <div class="profile-details">
          <span class="profile-name" id="agentName"></span>
          <span class="profile-email" id="agentEmail"></span>
          <span class="profile-location" id="agentLocation"></span>
          <span class="profile-company" id="agentCompany" style="color:#0074d9;font-weight:600;"></span>
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
      <div class="profile-stat">Listings<br><b id="statListings">0</b></div>
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
      <div class="property-list" id="portfolioPropertyList"></div>
    </div>
    <div class="section-content" id="listingsSection" style="display:none;">
      <div class="glass-card" style="background:rgba(255,255,255,0.25); backdrop-filter:blur(12px); border-radius:18px; box-shadow:0 4px 16px rgba(0,0,0,0.07); padding:32px 24px;">
        <h2 style="font-size:1.2rem; color:#222; margin-bottom:18px;">Company Listings</h2>
        <table class="listings-table" style="width:100%; border-collapse:collapse; background:rgba(255,255,255,0.5); border-radius:14px; overflow:hidden;">
          <thead style="background:rgba(255,255,255,0.4);">
            <tr><th>Property</th><th>Location</th><th>Price</th><th>Bedrooms</th><th>Bathrooms</th><th>Size (sqm)</th><th>Status</th><th>Sold By</th><th>Actions</th></tr>
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
              <option value="Lot">Lot</option>
              <option value="Property">Property</option>
            </select>
          </div>
          <div class="form-group">
            <select id="addBedrooms" class="glass-input" required>
              <option value="">Select Bedrooms</option>
              <option value="0">0</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4+">4+</option>
            </select>
          </div>
          <div class="form-group">
            <select id="addBathrooms" class="glass-input" required>
              <option value="">Select Bathrooms</option>
              <option value="0">0</option>
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
            <div class="file-input-wrapper" required>
              <input type="file" id="addPhoto" multiple accept="image/*">
              <div class="file-input-display"  onclick="document.getElementById('addPhoto').click()">
                <span class="file-name" id="photoFileName">Property Images (No file chosen)</span>
                <span class="choose-btn">Choose Files</span>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="file-input-wrapper" required>
              <input type="file" id="addDoc" multiple accept="application/pdf,image/*">
              <div class="file-input-display" onclick="document.getElementById('addDoc').click()">
                <span class="file-name" id="docFileName">Property Documents (No file chosen)</span>
                <span class="choose-btn">Choose Files</span>
              </div>
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
    // Sample data for companies, agents, and properties
    const companies = [
      { id: 1, name: 'Prime Realty', logo: 'Pictures/company_logo.png' },
      { id: 2, name: 'Urban Estates', logo: 'Pictures/company_logo2.png' }
    ];
    const agents = [
      { id: 1, name: 'Anna Associate', email: 'anna@prime.com', location: 'Batangas City', companyId: 1 },
      { id: 2, name: 'Ben Associate', email: 'ben@prime.com', location: 'Lipa City', companyId: 1 },
      { id: 3, name: 'Cara Associate', email: 'cara@urban.com', location: 'Tanauan', companyId: 2 }
    ];
    const properties = [
      { id: 1, title: 'Modern Family Home', location: 'Batangas City', type: 'Property', price: '₱3,500,000', bedrooms: 3, bathrooms: 2, sqm: 180, image: 'Pictures/bg4.jpg', features: ['3 BR', '2 BA', '180 sqm'], companyId: 1, createdBy: 1, status: 'available', soldBy: null },
      { id: 2, title: 'Luxury Condo Unit', location: 'Lipa City', type: 'Property', price: '₱2,800,000', bedrooms: 2, bathrooms: 2, sqm: 85, image: 'Pictures/bg4.jpg', features: ['2 BR', '2 BA', '85 sqm'], companyId: 1, createdBy: 2, status: 'sold', soldBy: 2 },
      { id: 3, title: 'Premium Lot', location: 'Tanauan', type: 'Lot', price: '₱1,200,000', bedrooms: 0, bathrooms: 0, sqm: 300, image: 'Pictures/bg4.jpg', features: ['300 sqm', 'Residential', 'Ready for Construction'], companyId: 2, createdBy: 3, status: 'available', soldBy: null }
    ];
    // Simulate logged-in agent (Anna Associate)
    const loggedInAgent = agents[0];
    const company = companies.find(c => c.id === loggedInAgent.companyId);
    document.getElementById('agentName').textContent = loggedInAgent.name;
    document.getElementById('agentEmail').textContent = loggedInAgent.email;
    document.getElementById('agentLocation').textContent = loggedInAgent.location;
    document.getElementById('agentCompany').textContent = company.name;
    // Portfolio: Show all company properties
    function renderPortfolio() {
      const list = document.getElementById('portfolioPropertyList');
      list.innerHTML = '';
      properties.filter(p => p.companyId === company.id).forEach(p => {
        const card = document.createElement('div');
        card.className = 'property-card';
        card.innerHTML = `
          <div class="property-image"></div>
          <div class="property-name">${p.title}</div>
          <div class="property-meta">${p.location}</div>
          <div class="property-price">${p.price}</div>
          <div style="font-size:0.95rem;color:#0074d9;font-weight:500;">${company.name}</div>
          ${p.status === 'sold' ? `<div style='color:#c00;font-weight:600;'>Sold by ${agents.find(a=>a.id===p.soldBy).name} (${company.name})</div>` : ''}
          <button class="details-btn">Details</button>
        `;
        list.appendChild(card);
      });
    }
    renderPortfolio();
    // Listings Table: Show all company properties
    function renderListings() {
      const tbody = document.getElementById('companyListingsTableBody');
      tbody.innerHTML = '';
      let available = 0, sold = 0;
      properties.filter(p => p.companyId === company.id).forEach(p => {
        if (p.status === 'available') available++; else sold++;
        tbody.innerHTML += `
          <tr>
            <td>${p.title}</td><td>${p.location}</td><td>${p.price}</td><td>${p.bedrooms}</td><td>${p.bathrooms}</td><td>${p.sqm}</td>
            <td style="color:${p.status==='sold'?'#c00':'#1a7f1a'}; font-weight:500;">${p.status.charAt(0).toUpperCase()+p.status.slice(1)}</td>
            <td>${p.status==='sold'?agents.find(a=>a.id===p.soldBy).name+' ('+company.name+')':''}</td>
            <td><button class="glass-btn">Edit</button> <button class="glass-btn danger">Delete</button></td>
          </tr>
        `;
      });
      document.getElementById('statListings').textContent = available+sold;
      document.getElementById('statAvailable').textContent = available+" Available";
      document.getElementById('statSold').textContent = sold+" Sold";
    }
    renderListings();
    // My Listings Table: Show all statuses for the logged-in agent
    function renderMyListings() {
      const tbody = document.getElementById('myListingsTableBody');
      tbody.innerHTML = '';
      let available = 0, sold = 0;
      properties.filter(p => p.createdBy === loggedInAgent.id).forEach(p => {
        if (p.status === 'available') available++; else sold++;
        tbody.innerHTML += `
          <tr>
            <td>${p.title}</td><td>${p.location}</td><td>${p.price}</td><td>${p.bedrooms}</td><td>${p.bathrooms}</td><td>${p.sqm}</td>
            <td style="color:${p.status==='sold'?'#c00':'#1a7f1a'}; font-weight:500;">${p.status.charAt(0).toUpperCase()+p.status.slice(1)}</td>
            <td><button class="glass-btn">Edit</button> <button class="glass-btn danger">Delete</button></td>
          </tr>
        `;
      });
      document.getElementById('statListings').textContent = available+sold;
      document.getElementById('statAvailable').textContent = available+" Available";
      document.getElementById('statSold').textContent = sold+" Sold";
    }
    renderMyListings();
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
        const response = await fetch('http://127.0.0.1:3002/api/properties', {
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

    // Add form submission handler
    document.querySelector('.add-listing-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      // Create FormData object for file uploads
      const formData = new FormData();
      
      // Add text fields
      formData.append('title', document.getElementById('addName').value);
      formData.append('description', document.getElementById('addDesc').value);
      formData.append('location', document.getElementById('addLocation').value);
      formData.append('property_type', document.getElementById('addType').value);
      formData.append('price', document.getElementById('addPrice').value);
      formData.append('bedrooms', document.getElementById('addBedrooms').value);
      formData.append('bathrooms', document.getElementById('addBathrooms').value);
      formData.append('sqm', document.getElementById('addLotSize').value);
      formData.append('features', `${document.getElementById('addBedrooms').value} BR, ${document.getElementById('addBathrooms').value} BA, ${document.getElementById('addLotSize').value} sqm`);
      
      // Add files
      const photoFiles = document.getElementById('addPhoto').files;
      const docFiles = document.getElementById('addDoc').files;
      
      for (let i = 0; i < photoFiles.length; i++) {
        formData.append('images', photoFiles[i]);
      }
      
      for (let i = 0; i < docFiles.length; i++) {
        formData.append('documents', docFiles[i]);
      }

      try {
        const response = await fetch('http://127.0.0.1:3002/api/properties', {
          method: 'POST',
          headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('agentToken')
          },
          body: formData
        });
        
        if (!response.ok) {
          throw new Error('Failed to add listing');
        }
        
        const savedProperty = await response.json();
        
        // Update local state
        properties.push(savedProperty);
        renderPortfolio();
        renderListings();
        renderMyListings();
        
        // Reset form
        this.reset();
        document.getElementById('photoFileName').textContent = 'Property Images (No file chosen)';
        document.getElementById('docFileName').textContent = 'Property Documents (No file chosen)';
        
        alert('Listing added successfully!');
      } catch (err) {
        console.error('Error submitting form:', err);
        alert('Error adding listing: ' + err.message);
      }
    });

    // File input handlers
    document.getElementById('addPhoto').addEventListener('change', function() {
      const fileName = this.files.length > 0 ? `${this.files.length} file(s) selected` : 'Property Images (No file chosen)';
      document.getElementById('photoFileName').textContent = fileName;
    });

    document.getElementById('addDoc').addEventListener('change', function() {
      const fileName = this.files.length > 0 ? `${this.files.length} file(s) selected` : 'Property Documents (No file chosen)';
      document.getElementById('docFileName').textContent = fileName;
    });

    // Cancel button handler
    document.querySelector('.add-listing-form .glass-btn.danger').addEventListener('click', function() {
      document.querySelector('.add-listing-form').reset();
      document.getElementById('photoFileName').textContent = 'Property Images (No file chosen)';
      document.getElementById('docFileName').textContent = 'Property Documents (No file chosen)';
    });
  </script>
</body>
</html> 