<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Properties | Associate Agent Dashboard</title>
  <link rel="stylesheet" href="homepage.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    /* Copy all styles from direct_agent_search.html for full design consistency */
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
      padding: 12px 20px;
      background: #f8f9fa;
      color: #666;
      border: 1px solid #ddd;
      border-radius: 70px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.2s;
    }
    .clear-btn:hover {
      background: #e9ecef;
    }
    .results-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
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
      font-size: 4rem;
      color: #ddd;
      margin-bottom: 20px;
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
          <li><a href="associate_agent_index.html">Home</a></li>
          <li><a href="#properties" class="active">Properties</a></li>
          <li><a href="#sellers">Agents</a></li>
          <li><a href="#faqs">FAQs</a></li>
          <li><a href="associate_agent_dashboard_full.html" class="btn rounded-btn" style="background: #000000; color: #fff;"><i class="fa-solid fa-user"></i> Dashboard</a></li>
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
    <div class="search-filters">
      <div class="filters-grid">
        <div class="filter-group">
          <label for="location">Location</label>
          <select id="location">
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
          <label for="propertyType">Property Type</label>
          <select id="propertyType">
            <option value="">All Types</option>
            <option value="Lot">Lot</option>
            <option value="Property">Property</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="priceRange">Price Range</label>
          <select id="priceRange">
            <option value="">Any Price</option>
            <option value="0-1000000">Under ₱1M</option>
            <option value="1000000-3000000">₱1M - ₱3M</option>
            <option value="3000000-5000000">₱3M - ₱5M</option>
            <option value="5000000+">Over ₱5M</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="bedrooms">Bedrooms</label>
          <select id="bedrooms">
            <option value="">Any</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4+">4+</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="bathrooms">Bathrooms</label>
          <select id="bathrooms">
            <option value="">Any</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4+">4+</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="sqm">Size (sqm)</label>
          <select id="sqm">
            <option value="">Any Size</option>
            <option value="0-100">Under 100 sqm</option>
            <option value="100-200">100-200 sqm</option>
            <option value="200-500">200-500 sqm</option>
            <option value="500+">Over 500 sqm</option>
          </select>
        </div>
      </div>
      <div class="search-actions">
        <button class="search-btn" id="searchBtn">
          <i class="fa-solid fa-search"></i> Search Properties
        </button>
        <button class="clear-btn" id="clearBtn">Clear Filters</button>
      </div>
    </div>

    <!-- Results Section -->
    <div class="results-header">
      <div class="results-count">
        Showing <span id="resultCount">12</span> properties
      </div>
      <div class="sort-controls">
        <label for="sortBy">Sort by:</label>
        <select id="sortBy">
          <option value="relevance">Relevance</option>
          <option value="price-low">Price: Low to High</option>
          <option value="price-high">Price: High to Low</option>
          <option value="date-new">Date: Newest First</option>
          <option value="date-old">Date: Oldest First</option>
        </select>
      </div>
    </div>

    <!-- Property Grid -->
    <div class="property-grid" id="propertyGrid">
      <!-- Property cards will be generated here -->
    </div>
  </div>

  <script>
    let properties = [];

    // Replace the static properties array with API call
    async function loadProperties() {
      try {
        const response = await fetch('get_property_details.php?all=1');
        properties = await response.json();
        generatePropertyCards(properties);
      } catch (err) {
        console.error('Error loading properties:', err);
        generatePropertyCards([]);
      }
    }

    // Function to generate property cards
    function generatePropertyCards(propertiesToShow = properties) {
      document.getElementById('resultCount').textContent = propertiesToShow.length;
      const grid = document.getElementById('propertyGrid');
      grid.innerHTML = '';
      if (propertiesToShow.length === 0) {
        grid.innerHTML = `
          <div class="no-results">
            <i class="fa-solid fa-search"></i>
            <h3>No properties found</h3>
            <p>Try adjusting your search filters</p>
          </div>
        `;
        return;
      }
      propertiesToShow.forEach(property => {
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
        grid.appendChild(card);
      });
    }

    // Update the applyFilters function to use API
    async function applyFilters() {
      const location = document.getElementById('location').value;
      const propertyType = document.getElementById('propertyType').value;
      const priceRange = document.getElementById('priceRange').value;
      const bedrooms = document.getElementById('bedrooms').value;
      const bathrooms = document.getElementById('bathrooms').value;
      const sqm = document.getElementById('sqm').value;

      try {
        const params = new URLSearchParams();
        if (location) params.append('location', location);
        if (propertyType) params.append('propertyType', propertyType);
        if (bedrooms) params.append('bedrooms', bedrooms);
        if (bathrooms) params.append('bathrooms', bathrooms);

        const response = await fetch(`user_search.php?${params}`);
        const filteredProperties = await response.json();
        generatePropertyCards(filteredProperties);
      } catch (err) {
        console.error('Error applying filters:', err);
      }
    }

    // Function to clear filters
    function clearFilters() {
      document.getElementById('location').value = '';
      document.getElementById('propertyType').value = '';
      document.getElementById('priceRange').value = '';
      document.getElementById('bedrooms').value = '';
      document.getElementById('bathrooms').value = '';
      document.getElementById('sqm').value = '';
      generatePropertyCards(properties); // Show all properties
    }

    // Function to get URL parameter
    function getUrlParameter(name) {
      const urlParams = new URLSearchParams(window.location.search);
      return urlParams.get(name);
    }

    // Function to apply URL filters on page load
    function applyUrlFilters() {
      console.log('applyUrlFilters called');
      const location = getUrlParameter('location');
      const category = getUrlParameter('category');
      const price = getUrlParameter('price');
      const bedrooms = getUrlParameter('bedrooms');
      const bathrooms = getUrlParameter('bathrooms');
      const size = getUrlParameter('size');

      console.log('URL parameters:', { location, category, price, bedrooms, bathrooms, size });

      if (location) {
        document.getElementById('location').value = location;
        console.log('Set location to:', location);
      }
      if (category) document.getElementById('propertyType').value = category;
      if (price) {
        // Map price ranges from index to search page format
        const priceMap = {
          'Under ₱1M': '0-1000000',
          '₱1M - ₱3M': '1000000-3000000',
          '₱3M - ₱5M': '3000000-5000000',
          'Over ₱5M': '5000000+'
        };
        document.getElementById('priceRange').value = priceMap[price] || price;
      }
      if (bedrooms) document.getElementById('bedrooms').value = bedrooms;
      if (bathrooms) document.getElementById('bathrooms').value = bathrooms;
      if (size) {
        // Map size ranges from index to search page format
        const sizeMap = {
          'Under 100 sqm': '0-100',
          '100-200 sqm': '100-200',
          '200-500 sqm': '200-500',
          'Over 500 sqm': '500+'
        };
        document.getElementById('sqm').value = sizeMap[size] || size;
      }

      // Apply filters if any URL parameters exist
      if (location || category || price || bedrooms || bathrooms || size) {
        console.log('URL parameters found, calling applyFilters()');
        applyFilters();
      } else {
        console.log('No URL parameters found');
      }
    }

    // Function to sort properties
    function sortProperties() {
      const sortBy = document.getElementById('sortBy').value;
      const currentProperties = Array.from(document.querySelectorAll('.property-card')).map(card => {
        const id = parseInt(card.querySelector('.view-details-btn').getAttribute('onclick').match(/\d+/)[0]);
        return properties.find(p => p.id === id);
      });

      let sortedProperties = [...currentProperties];

      switch (sortBy) {
        case 'price-low':
          sortedProperties.sort((a, b) => {
            const priceA = parseInt(a.price.replace(/[^0-9]/g, ''));
            const priceB = parseInt(b.price.replace(/[^0-9]/g, ''));
            return priceA - priceB;
          });
          break;
        case 'price-high':
          sortedProperties.sort((a, b) => {
            const priceA = parseInt(a.price.replace(/[^0-9]/g, ''));
            const priceB = parseInt(b.price.replace(/[^0-9]/g, ''));
            return priceB - priceA;
          });
          break;
        case 'date-new':
          sortedProperties.sort((a, b) => b.id - a.id);
          break;
        case 'date-old':
          sortedProperties.sort((a, b) => a.id - b.id);
          break;
        default: // relevance - no sorting
          break;
      }

      generatePropertyCards(sortedProperties);
    }

    // Event listeners
    document.getElementById('searchBtn').addEventListener('click', applyFilters);
    document.getElementById('clearBtn').addEventListener('click', clearFilters);
    document.getElementById('sortBy').addEventListener('change', sortProperties);

    // Load properties when page loads
    document.addEventListener('DOMContentLoaded', () => {
      loadProperties();
      applyUrlFilters();
    });

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