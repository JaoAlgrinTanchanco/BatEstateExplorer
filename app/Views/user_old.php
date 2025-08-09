<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
$is_logged_in = is_logged_in();
$current_user = null;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />
  <title>BatEstateExplorer | Premium Real Estate in Batangas</title>
  <link rel="stylesheet" href="assets/css/homepage.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
</head>
<body>
  <!-- Header -->
  <header class="glass-header">
  <div class="header-content">
    <h1 class="logo">BatEstateExplorer</h1>
    <nav>
      <ul>
        <li><a href="#" class="btn rounded-btn" style="background: #000000; color: #fff;"><i class="fa-solid fa-user"></i> Agents</a></li> 
        <li><a href="user_search.php" class="btn rounded-btn" style="background: #000000; color: #fff;"><i class="fa-solid fa-house"></i> Properties</a></li> 
        <li><a href="user_profile.php" class="btn rounded-btn" style="background: #000000; color: #fff;"><i class="fa-solid fa-user"></i> Profile</a></li>
        <li><button id="becomeDirectAgentBtn" class="btn rounded-btn" style="background: #000000; color: #fff;">Become a Direct Agent</button></li>
      </ul>
    </nav>
  </div>
</header>

  <!-- Direct Agent Registration Modal -->
  <div id="directAgentRegModal" class="auth-modal hidden">
    <div class="auth-backdrop"></div>
    <div class="auth-card" style="max-width: 420px;">
      <h2 style="text-align:center; margin-bottom: 18px;">Become a Direct Agent</h2>
      <form id="directAgentRegForm" class="auth-form active" enctype="multipart/form-data">
        <div class="form-group" style="display: flex; gap: 10px;">
          <input type="text" id="daFirstName" placeholder=" " required style="flex:1;">
          <label for="daFirstName" style="left: 16px;">First Name</label>
          <input type="text" id="daLastName" placeholder=" " required style="flex:1;">
          <label for="daLastName" style="left: 52%;">Last Name</label>
        </div>
        <div class="form-group">
          <input type="email" id="daEmail" placeholder=" " required>
          <label for="daEmail">Email</label>
        </div>
        <div class="form-group" style="position:relative;">
          <input type="password" id="daPassword" placeholder=" " required>
          <label for="daPassword">Password</label>
          <i class="fa-solid fa-eye toggle-password" id="daTogglePassword" style="position:absolute; right:18px; top:50%; transform:translateY(-50%); cursor:pointer; color:#999;"></i>
        </div>
        <div class="form-group" style="position:relative;">
          <input type="password" id="daConfirmPassword" placeholder=" " required>
          <label for="daConfirmPassword">Confirm Password</label>
          <i class="fa-solid fa-eye toggle-password" id="daToggleConfirmPassword" style="position:absolute; right:18px; top:50%; transform:translateY(-50%); cursor:pointer; color:#999;"></i>
        </div>
        <div class="form-group">
          <input type="text" id="daBrokersID" placeholder=" " required>
          <label for="daBrokersID">Broker's ID</label>
        </div>
        <div class="form-group">
          <label for="daUploadID" style="position:static; color:#888; font-size:14px;">Upload ID (optional):</label>
          <input type="file" id="daUploadID" accept="image/*,application/pdf">
        </div>
        <button type="submit" class="auth-button">Register</button>
        <button type="button" id="daCancelBtn" class="auth-button" style="background:#eee; color:#222; margin-top:10px;">Cancel</button>
      </form>
    </div>
  </div>


  <!-- Hero Section -->
  <section id="hero">
    <div class="hero-container">
      <h2 class="hero-title">Find Your Dream Property in Batangas</h2>
      <p class="hero-subtitle">Modern lots and properties just for you</p>
  

      
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

  <script src="assets/js/homepage.js"></script>
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
      
      // Redirect to user_search.html with filters
      const searchUrl = `user_search.php?${queryParams.toString()}`;
      window.location.href = searchUrl;
    });

    // Show Direct Agent Registration Modal
    document.getElementById('becomeDirectAgentBtn').onclick = function() {
      document.getElementById('directAgentRegModal').classList.remove('hidden');
    };
    // Hide Direct Agent Registration Modal
    document.getElementById('daCancelBtn').onclick = function() {
      document.getElementById('directAgentRegModal').classList.add('hidden');
    };
    document.querySelectorAll('#directAgentRegModal .auth-backdrop').forEach(el => {
      el.onclick = function() {
        document.getElementById('directAgentRegModal').classList.add('hidden');
      };
    });
    // Password eye toggles
    document.getElementById('daTogglePassword').onclick = function() {
      const pw = document.getElementById('daPassword');
      pw.type = pw.type === 'password' ? 'text' : 'password';
      this.classList.toggle('fa-eye-slash');
    };
    document.getElementById('daToggleConfirmPassword').onclick = function() {
      const pw = document.getElementById('daConfirmPassword');
      pw.type = pw.type === 'password' ? 'text' : 'password';
      this.classList.toggle('fa-eye-slash');
    };
    // Prevent form submit (demo only)
    document.getElementById('directAgentRegForm').onsubmit = function(e) {
      e.preventDefault();
      alert('Registration submitted! (This would go to admin for approval.)');
      document.getElementById('directAgentRegModal').classList.add('hidden');
    };

    // Property slider logic
    // Remove static sliderProperties and use API
    async function loadSliderProperties() {
      try {
        const response = await fetch('get_properties.php');
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
        let sliderIndex = 0;
        const sliderTrack = document.getElementById('propertySliderTrack');
        function renderSlider() {
          sliderTrack.innerHTML = '';
          for (let i = sliderIndex; i < Math.min(sliderIndex + 4, sliderData.length); i++) {
            const p = sliderData[i];
            const card = document.createElement('div');
            card.className = 'property-card slider-card';
            card.innerHTML = `
              <div class="property-image">
                <div class="property-badge">${p.property_type}</div>
               <img src="${p.image || 'assets/images/bg4.jpg'}" alt="${p.title}" style="width:100%;height:100px;object-fit:cover;border-radius:12px 12px 30px 30px;">
              </div>
              <div class="property-name">${p.title}</div>
              <div class="property-meta">${p.location}</div>
              <div class="property-price">₱${parseInt(p.price).toLocaleString()}</div>
              <div class="property-features">${Array.isArray(p.features) ? p.features.join(' | ') : p.features}</div>
            `;
            sliderTrack.appendChild(card);
          }
        }
        renderSlider();
        document.getElementById('sliderLeft').onclick = function() {
          if (sliderIndex > 0) {
            sliderIndex--;
            renderSlider();
          }
        };
        document.getElementById('sliderRight').onclick = function() {
          if (sliderIndex < sliderData.length - 4) {
            sliderIndex++;
            renderSlider();
          }
        };
      } catch (err) {
        console.error('Error loading slider properties:', err);
        // Fallback to static data if API fails
        const fallbackProperties = [
          {
            id: 1,
            title: "Modern Family Home",
            property_type: "Property",
            location: "Batangas City",
            price: "3500000",
            features: ["3 BR", "2 BA", "180 sqm"]
          },
          {
            id: 2,
            title: "Luxury Condo Unit",
            property_type: "Property", 
            location: "Lipa City",
            price: "2800000",
            features: ["2 BR", "2 BA", "85 sqm"]
          }
        ];
        // Use fallback data
        const sliderProperties = [...fallbackProperties];
        // ... rest of the slider logic with fallback data
      }
    }
    document.addEventListener('DOMContentLoaded', loadSliderProperties);
  </script>
</body>
</html>