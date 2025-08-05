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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />
  <title>BatEstateExplorer | Associate Agent Homepage</title>
  <link rel="stylesheet" href="homepage.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
</head>
<body>
  <!-- Header -->
  <header class="glass-header">
  <div class="header-content">
    <h1 class="logo">BatEstateExplorer <span style="font-size:0.9rem; background:#0074d9; color:#fff; border-radius:8px; padding:2px 10px; margin-left:10px; vertical-align:middle;">Associate Agent</span></h1>
    <nav>
      <ul>
        <li><a href="#" class="btn rounded-btn" style="background: #000000; color: #fff;"><i class="fa-solid fa-user"></i> Agents</a></li> 
        <li><a href="associate_agent_search.php" class="btn rounded-btn" style="background: #000000; color: #fff;"><i class="fa-solid fa-house"></i> Properties</a></li> 

        <li><a href="associate_agent_dashboard_full.php" class="btn rounded-btn" style="background: #000000; color: #fff;"><i class="fa-solid fa-user"></i> Dashboard</a></li>
        <!-- Removed Become a Direct Agent button for associate agent homepage -->
      </ul>
    </nav>
  </div>
</header>

  <!-- Hero Section -->
  <section id="hero">
    <div class="hero-container">
      <h2 class="hero-title">Welcome, Associate Agent!</h2>
      <p class="hero-subtitle">Manage your company listings and connect with buyers in Batangas.</p>
      <!-- Search Box -->
     <div class="search-box glass-panel">
        <!-- Location -->
        <select id="location" class="rounded-input">
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

        <!-- Property Type -->
        <select id="category" class="rounded-input">
          <option value="">All Types</option>
          <option value="Lot">Lot</option>
          <option value="Property">Property</option>
        </select>

        <!-- Price Range -->
        <select id="price" class="rounded-input">
          <option value="">Any Price</option>
          <option value="Under ₱1M">Under ₱1M</option>
          <option value="₱1M - ₱3M">₱1M - ₱3M</option>
          <option value="₱3M - ₱5M">₱3M - ₱5M</option>
          <option value="Over ₱5M">Over ₱5M</option>
        </select>

        <!-- Bedrooms -->
        <select id="bedrooms" class="rounded-input">
          <option value="">Any</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4+">4+</option>
        </select>

        <!-- Bathrooms -->
        <select id="bathrooms" class="rounded-input">
          <option value="">Any</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4+">4+</option>
        </select>

        <!-- Size -->
        <select id="size" class="rounded-input">
          <option value="">Any Size</option>
          <option value="Under 100 sqm">Under 100 sqm</option>
          <option value="100-200 sqm">100-200 sqm</option>
          <option value="200-500 sqm">200-500 sqm</option>
          <option value="Over 500 sqm">Over 500 sqm</option>
        </select>

        <!--Search Button-->
        <button id="searchBtn" class="btn rounded-btn">Search</button>
      </div>
    </div>
  </section>

  <!-- Featured Properties -->
  <section id="properties">
    <div class="section-container">
      <h2 class="section-title">Featured Properties</h2>
      <div class="property-list grid-layout" id="listings">
        <!-- Dynamic cards injected by JS -->
      </div>
    </div>
  </section>

  <!-- Property Slider (NEW) -->
  <section id="property-slider-section">
    <div class="section-container">
      <div class="slider-wrapper">
        <button class="slider-arrow left" id="sliderLeft"><i class="fa-solid fa-chevron-left"></i></button>
        <div class="slider-track" id="propertySliderTrack"></div>
        <button class="slider-arrow right" id="sliderRight"><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section id="about">
    <div class="section-container">
      <h2>About Us</h2>
      <p>BatEstateExplorer is your gateway to premium real estate and lot listings across Batangas. Discover quality properties that suit your lifestyle and budget.</p>
    </div>
  </section>

  <!-- Footer -->
  <footer class="glass-footer">
    <div class="footer-content">
      <p>&copy; 2025 BatEstateExplorer. All rights reserved.</p>
      <div class="footer-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
      </div>
    </div>
  </footer>

  <script src="/homepage.js"></script>
  <script>
    document.getElementById('searchBtn').addEventListener('click', () => {
      const filters = {
        location: document.getElementById('location').value,
        category: document.getElementById('category').value,
        price: document.getElementById('price').value,
        bedrooms: document.getElementById('bedrooms').value,
        bathrooms: document.getElementById('bathrooms').value,
        size: document.getElementById('size').value,
      };
      
      // Build query string with non-empty filters
      const queryParams = new URLSearchParams();
      Object.entries(filters).forEach(([key, value]) => {
        if (value && value.trim() !== '') {
          queryParams.append(key, value);
        }
      });
      
      // Redirect to associate_agent_search.html with filters
      const searchUrl = `associate_agent_search.php?${queryParams.toString()}`;
      window.location.href = searchUrl;
    });

    // Replace static properties with API call
    async function loadSliderProperties() {
      try {
        const response = await fetch('http://127.0.0.1:3002/api/properties');
        const data = await response.json();
        const sliderProperties = [...data];
        
        function shuffleArray(arr) {
          for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
          }
        }
        shuffleArray(sliderProperties);
        const maxSlider = Math.min(10, sliderProperties.length);
        const sliderData = sliderProperties.slice(0, maxSlider);
        
        renderSlider(sliderData);
      } catch (err) {
        console.error('Error loading slider properties:', err);
      }
    }

    // Call this when page loads
    document.addEventListener('DOMContentLoaded', loadSliderProperties);
  </script>
</body>
</html> 