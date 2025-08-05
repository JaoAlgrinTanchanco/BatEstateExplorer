<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
$is_logged_in = is_logged_in();
$current_user = null;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
}

// Handle search filters
$search_results = [];
$search_performed = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
    $search_performed = true;
    
    $location = isset($_GET['location']) ? sanitize_input($conn, $_GET['location']) : '';
    $category = isset($_GET['category']) ? sanitize_input($conn, $_GET['category']) : '';
    $price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
    $price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 999999999;
    $bedrooms = isset($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : 0;
    $bathrooms = isset($_GET['bathrooms']) ? (int)$_GET['bathrooms'] : 0;
    
    // Build search query with property images
    $search_query = "SELECT p.*, u.first_name, u.last_name, 
                     (SELECT pi.image_path FROM property_images pi WHERE pi.property_id = p.id LIMIT 1) as main_image
                     FROM properties p 
                     LEFT JOIN agents a ON p.agent_id = a.id
                     LEFT JOIN users u ON a.user_id = u.id 
                     WHERE p.status = 'available'";
    $params = [];
    $types = "";
    
    if (!empty($location)) {
        $search_query .= " AND p.location LIKE ?";
        $params[] = "%$location%";
        $types .= "s";
    }
    
    if (!empty($category)) {
        $search_query .= " AND p.property_type = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    if ($price_min > 0 || $price_max < 999999999) {
        $search_query .= " AND p.price BETWEEN ? AND ?";
        $params[] = $price_min;
        $params[] = $price_max;
        $types .= "dd";
    }
    
    if ($bedrooms > 0) {
        $search_query .= " AND p.bedrooms >= ?";
        $params[] = $bedrooms;
        $types .= "i";
    }
    
    if ($bathrooms > 0) {
        $search_query .= " AND p.bathrooms >= ?";
        $params[] = $bathrooms;
        $types .= "i";
    }
    
    $search_query .= " ORDER BY p.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $search_query);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $search_results = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Properties | BatEstateExplorer</title>
  <link rel="stylesheet" href="homepage.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    .search-container {
      max-width: 1200px;
      margin: 40px auto 0 auto;
      padding: 0 20px;
    }
    .search-header {
      text-align: center;
      margin-bottom: 40px;
    }
    .search-header h1 {
      font-size: 2.5rem;
      color: #222;
      margin-bottom: 10px;
    }
    .search-header p {
      color: #666;
      font-size: 1.1rem;
    }
    .search-filters {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(16px);
      border-radius: 70px;
      padding: 30px;
      margin-bottom: 40px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.07);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .filters-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 25px;
    }
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .filter-group label {
      font-weight: 600;
      color: #333;
      font-size: 0.95rem;
    }
    .filter-group input,
    .filter-group select {
      padding: 12px 16px;
      border: 1px solid #dcdfe3;
      border-radius: 70px;
      font-size: 1rem;
      background: #fff;
      transition: border-color 0.2s;
      color: #333;
    }
    .filter-group input:focus,
    .filter-group select:focus {
      outline: none;
      border-color: #888;
    }
    .filter-group input::placeholder {
      color: #999;
    }
    .filter-group select option {
      padding: 8px 12px;
      background: #fff;
      color: #333;
    }
    .filter-group select option:hover {
      background: #f4f7fa;
    }
    .search-actions {
      display: flex;
      gap: 15px;
      justify-content: center;
      align-items: center;
    }
    .search-btn {
      padding: 12px 30px;
      background: #000;
      color: #fff;
      border: none;
      border-radius: 70px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
      border: 1px solid #000;
    }
    .search-btn:hover {
      background: #bfc2c4;
    }
    .clear-btn {
      padding: 12px 30px;
      background: transparent;
      color: #666;
      border: 1px solid #dcdfe3;
      border-radius: 70px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    .clear-btn:hover {
      background: #f8f9fa;
      border-color: #999;
    }
    .results-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding: 0 10px;
    }
    .results-count {
      font-size: 1.1rem;
      color: #666;
    }
    .sort-controls {
      display: flex;
      gap: 15px;
      align-items: center;
    }
    .sort-controls label {
      font-weight: 500;
      color: #555;
    }
    .sort-controls select {
      padding: 8px 12px;
      border: 1px solid #dcdfe3;
      border-radius: 70px;
      background: #fff;
      color: #333;
      font-size: 0.95rem;
    }
    .sort-controls select:focus {
      outline: none;
      border-color: #888;
    }
    .sort-controls select option {
      padding: 8px 12px;
      background: #fff;
      color: #333;
    }
    .property-grid {
      display: flex;
      gap: 24px;
      flex-wrap: wrap;
      margin-bottom: 40px;
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
      background: #e0e7ef center/cover no-repeat;
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
    .property-card .property-actions {
      display: flex;
      gap: 8px;
      width: 100%;
    }
    .property-card .view-details-btn {
      flex: 1;
      padding: 7px 18px;
      border-radius: 20px;
      border: none;
      font-weight: 500;
      font-size: 0.97rem;
      cursor: pointer;
      background: #000;
      color: #fff;
      transition: background 0.2s, color 0.2s;
    }
    .property-card .view-details-btn:hover {
      background: #bfc2c4;
      color: #fff;
    }
    .pagination {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 40px;
    }
    .pagination button {
      padding: 10px 15px;
      border: 1px solid #ddd;
      background: #fff;
      color: #666;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.2s;
    }
    .pagination button:hover {
      background: #f8f9fa;
    }
    .pagination button.active {
      background: #000000;
      color: #fff;
      border-color: #000000;
    }
    .no-results {
      text-align: center;
      padding: 60px 20px;
      color: #666;
    }
    .no-results i {
      font-size: 3rem;
      margin-bottom: 20px;
      color: #ccc;
    }
    .no-results h3 {
      margin-bottom: 10px;
      color: #333;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="container">
      <div class="logo">
        <h1>BatEstateExplorer</h1>
      </div>
      <nav class="nav">
        <ul>
          <li><a href="user_index.html">Home</a></li>
          <li><a href="#properties" class="active">Properties</a></li>
          <li><a href="#sellers">Agents</a></li>
          <li><a href="#faqs">FAQs</a></li>
          <li><a href="user_profile.html" class="btn rounded-btn" style="background: #000000; color: #fff;"><i class="fa-solid fa-user"></i> Profile</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="search-container">
    <!-- Search Header -->
    <div class="search-header">
      <h1>Find Your Perfect Property</h1>
      <p>Search through thousands of properties in Batangas</p>
    </div>

    <!-- Search Filters -->
    <form method="GET" action="">
      <div class="search-filters">
        <div class="filters-grid">
          <div class="filter-group">
            <label for="location">Location</label>
            <select name="location" id="location">
              <option value="">All Locations</option>
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
          <div class="filter-group">
            <label for="category">Property Type</label>
            <select name="category" id="category">
              <option value="">All Types</option>
              <option value="Lot">Lot</option>
              <option value="Property">Property</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="price_min">Min Price</label>
            <input type="number" name="price_min" id="price_min" placeholder="₱0" min="0">
          </div>
          <div class="filter-group">
            <label for="price_max">Max Price</label>
            <input type="number" name="price_max" id="price_max" placeholder="₱Any" min="0">
          </div>
          <div class="filter-group">
            <label for="bedrooms">Bedrooms</label>
            <select name="bedrooms" id="bedrooms">
              <option value="">Any</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4+</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="bathrooms">Bathrooms</label>
            <select name="bathrooms" id="bathrooms">
              <option value="">Any</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4+</option>
            </select>
          </div>
        </div>
        <div class="search-actions">
          <button type="submit" class="search-btn">
            <i class="fa-solid fa-search"></i> Search Properties
          </button>
          <button type="button" class="clear-btn" onclick="clearFilters()">
            <i class="fa-solid fa-times"></i> Clear Filters
          </button>
        </div>
      </div>
    </form>

    <!-- Results Header -->
    <div class="results-header">
      <div class="results-count">
        <?php if ($search_performed): ?>
          Found <span id="resultCount"><?php echo mysqli_num_rows($search_results); ?></span> properties
        <?php else: ?>
          All available properties
        <?php endif; ?>
      </div>
      <div class="sort-controls">
        <label for="sortBy">Sort by:</label>
        <select id="sortBy" onchange="sortProperties()">
          <option value="relevance">Relevance</option>
          <option value="price-low">Price: Low to High</option>
          <option value="price-high">Price: High to Low</option>
          <option value="newest">Newest First</option>
        </select>
      </div>
    </div>

    <!-- Property Grid -->
    <div class="property-grid" id="propertyGrid">
      <?php if ($search_performed && mysqli_num_rows($search_results) > 0): ?>
        <?php while ($property = mysqli_fetch_assoc($search_results)): ?>
          <div class="property-card">
            <div class="property-image" style="background-image: url('<?php echo $property['main_image'] ? $property['main_image'] : 'Pictures/bg4.jpg'; ?>');">
              <div class="property-badge"><?php echo htmlspecialchars($property['property_type']); ?></div>
            </div>
            <div class="property-name"><?php echo htmlspecialchars($property['title']); ?></div>
            <div class="property-meta"><?php echo htmlspecialchars($property['location']); ?></div>
            <div class="property-price">₱<?php echo number_format($property['price'], 0, '.', ','); ?></div>
            <div class="property-actions">
              <button class="view-details-btn" onclick="viewPropertyDetails(<?php echo $property['id']; ?>)">View Details</button>
            </div>
          </div>
        <?php endwhile; ?>
      <?php elseif ($search_performed): ?>
        <div class="no-results">
          <i class="fa-solid fa-search"></i>
          <h3>No properties found</h3>
          <p>Try adjusting your search filters</p>
        </div>
      <?php else: ?>
        <!-- Load all properties by default -->
        <div id="defaultProperties">
          <!-- This will be populated by JavaScript -->
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Load all properties by default if no search performed
    <?php if (!$search_performed): ?>
    loadAllProperties();
    <?php endif; ?>

    async function loadAllProperties() {
      try {
        const response = await fetch('get_property_details.php?all=1');
        const properties = await response.json();
        generatePropertyCards(properties);
      } catch (err) {
        console.error('Error loading properties:', err);
        document.getElementById('defaultProperties').innerHTML = `
          <div class="no-results">
            <i class="fa-solid fa-exclamation-triangle"></i>
            <h3>Error loading properties</h3>
            <p>Please try refreshing the page</p>
          </div>
        `;
      }
    }

    function generatePropertyCards(properties) {
      const container = document.getElementById('defaultProperties') || document.getElementById('propertyGrid');
      document.getElementById('resultCount').textContent = properties.length;
      
      if (properties.length === 0) {
        container.innerHTML = `
          <div class="no-results">
            <i class="fa-solid fa-search"></i>
            <h3>No properties found</h3>
            <p>Try adjusting your search filters</p>
          </div>
        `;
        return;
      }

      container.innerHTML = '';
      properties.forEach(property => {
        const card = document.createElement('div');
        card.className = 'property-card';
        card.innerHTML = `
          <div class="property-image" style="background-image: url('${property.main_image || 'Pictures/bg4.jpg'}');">
            <div class="property-badge">${property.property_type}</div>
          </div>
          <div class="property-name">${property.title}</div>
          <div class="property-meta">${property.location}</div>
          <div class="property-price">₱${parseInt(property.price).toLocaleString()}</div>
          <div class="property-actions">
            <button class="view-details-btn" onclick="viewPropertyDetails(${property.id})">View Details</button>
          </div>
        `;
        container.appendChild(card);
      });
    }

    function clearFilters() {
      window.location.href = window.location.pathname;
    }

    function sortProperties() {
      // Implement sorting logic here
      console.log('Sorting properties...');
    }

    function viewPropertyDetails(propertyId) {
      fetch(`get_public_property_details.php?id=${propertyId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const property = data.property;
            const modalContent = `
              <div style="background: white; padding: 30px; border-radius: 15px; max-width: 900px; width: 90%; max-height: 80vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                  <h2 style="margin: 0; color: #222;">Property Details - ${property.title}</h2>
                  <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                  <!-- Left Column: Property Details -->
                  <div>
                    <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">Property Information</h3>
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
                            </div>
                          `).join('')}
                        </div>
                      </div>
                    ` : `
                      <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; min-height: 300px; display: flex; align-items: center; justify-content: center; color: #666;">
                        <div style="text-align: center;">
                          <i class="fa-solid fa-image" style="font-size: 3rem; margin-bottom: 10px; color: #ccc;"></i>
                          <p><strong>No Images Available</strong></p>
                          <p style="font-size: 0.9rem;">This property doesn't have any images uploaded yet.</p>
                        </div>
                      </div>
                    `}
                  </div>
                </div>
                
                <!-- Property Description -->
                <div style="margin-top: 30px;">
                  <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">Property Description</h3>
                  <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <p style="line-height: 1.6; margin: 0;">${property.description || 'No description available for this property.'}</p>
                  </div>
                </div>
                
                <!-- Agent Information -->
                <div style="margin-top: 30px;">
                  <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">Agent Information</h3>
                  <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                      <div>
                        <p style="margin: 8px 0;"><strong>Agent Name:</strong> ${property.first_name} ${property.last_name}</p>
                        <p style="margin: 8px 0;"><strong>Phone:</strong> ${property.phone || 'Not provided'}</p>
                        <p style="margin: 8px 0;"><strong>Email:</strong> ${property.email || 'Not provided'}</p>
                      </div>
                      <div>
                        <p style="margin: 8px 0;"><strong>License Number:</strong> ${property.license_number || 'Not provided'}</p>
                        <p style="margin: 8px 0;"><strong>Company:</strong> ${property.user_type === 'direct_agent' ? 'N/A' : (property.company_name || 'Not provided')}</p>
                        <p style="margin: 8px 0;"><strong>Agent Type:</strong> ${property.user_type === 'direct_agent' ? 'Direct Agent' : (property.user_type === 'associate_agent' ? 'Associate Agent' : 'Unknown')}</p>
                      </div>
                    </div>
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

    function openImageModal(imagePath) {
      const modalContent = `
        <div style="background: rgba(0,0,0,0.9); padding: 20px; border-radius: 10px; max-width: 90vw; max-height: 90vh; display: flex; align-items: center; justify-content: center;">
          <div style="position: relative;">
            <img src="${imagePath}" alt="Property Image" style="max-width: 100%; max-height: 80vh; object-fit: contain;">
            <button onclick="closeModal()" style="position: absolute; top: -40px; right: 0; background: none; border: none; font-size: 24px; cursor: pointer; color: white;">×</button>
          </div>
        </div>
      `;
      showModal(modalContent);
    }

    function contactAgent(agentName, phone, email) {
      const modalContent = `
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 500px; width: 90%;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #222;">Contact Agent</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
          </div>
          <div style="margin-bottom: 20px;">
            <p><strong>Agent:</strong> ${agentName}</p>
            ${phone ? `<p><strong>Phone:</strong> ${phone}</p>` : ''}
            ${email ? `<p><strong>Email:</strong> ${email}</p>` : ''}
          </div>
          <div style="text-align: center;">
            ${phone ? `<a href="tel:${phone}" style="background: #0074d9; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin-right: 10px; cursor: pointer; text-decoration: none; display: inline-block;">
              <i class="fa-solid fa-phone"></i> Call Now
            </a>` : ''}
            ${email ? `<a href="mailto:${email}" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin-right: 10px; cursor: pointer; text-decoration: none; display: inline-block;">
              <i class="fa-solid fa-envelope"></i> Send Email
            </a>` : ''}
            <button onclick="closeModal()" style="background: #666; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">Close</button>
          </div>
        </div>
      `;
      showModal(modalContent);
    }
  </script>
</body>
</html> 