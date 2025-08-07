  <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />
  <title>BatEstateExplorer | Premium Real Estate in Batangas</title>
  <link rel="stylesheet" href="homepage.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
</head>
<body>
  <!-- Header -->
  <header class="glass-header">
  <div class="header-content">
    <h1 class="logo">BatEstateExplorer</h1>
    <nav>
      <ul>
        <li><a href="#properties">Agents</a></li>
        <li><button id="loginTrigger" class="btn rounded-btn">Log In</button></li>
        <li><a href="#" id="signupTrigger">Sign Up</a></li>
        <li><button id="becomeDirectAgentBtn" class="btn rounded-btn" style="background: #0074d9; color: #fff;">Become a Direct Agent</button></li>
        <li><button id="becomeAssociateAgentBtn" class="btn rounded-btn" style="background: #28a745; color: #fff;">Become an Associate Agent</button></li>
      </ul>
    </nav>
  </div>
</header>

<!-- Auth Modal -->
<div id="authModal" class="auth-modal hidden">
  <div class="auth-backdrop"></div>
  <div class="auth-card">
    <div class="switch">
      <button id="loginTab" class="active">Login</button>
      <button id="signupTab">Sign Up</button>
    </div>
    <form id="loginForm" class="auth-form active">
      <div class="form-group">
        <input type="email" id="loginEmail" placeholder=" " required>
        <label for="loginEmail">Email</label>
      </div>
      <div class="form-group">
        <input type="password" id="loginPassword" placeholder=" " required>
        <label for="loginPassword">Password</label>
      </div>
      <button type="submit" class="auth-button">Login</button>
      <a href="#" class="auth-link">Forgot password?</a>
      <div style="margin-top: 18px; text-align: center;">
        <a href="#" id="directAgentLoginLink" style="color: #0074d9; font-weight: 500; text-decoration: underline; cursor: pointer;">Direct Agent Login</a>
        <br>
        <a href="#" id="associateAgentLoginLink" style="color: #28a745; font-weight: 500; text-decoration: underline; cursor: pointer;">Associate Agent Login</a>
      </div>
    </form>
    <form id="signupForm" class="auth-form ">
      <div class="form-group">
        <input type="text" id="signupName" placeholder=" " required>
        <label for="signupName">Full Name</label>
      </div>
      <div class="form-group">
        <input type="email" id="signupEmail" placeholder=" " required>
        <label for="signupEmail">Email</label>
      </div>
      <div class="form-group">
        <input type="password" id="signupPassword" placeholder=" " required>
        <label for="signupPassword">Password</label>
      </div>
      <button type="submit" class="auth-button">Create Account</button>
    </form>
  </div>
</div>

  <!-- Direct Agent Login Modal -->
  <div id="directAgentLoginModal" class="auth-modal hidden">
    <div class="auth-backdrop"></div>
    <div class="auth-card" style="max-width: 420px;">
      <h2 style="text-align:center; margin-bottom: 18px;">Direct Agent Login</h2>
      <form id="directAgentLoginForm" class="auth-form active">
        <div class="form-group">
          <input type="text" id="daLoginEmailOrPhone" placeholder=" " required>
          <label for="daLoginEmailOrPhone">Email or Phone number</label>
        </div>
        <div class="form-group" style="position:relative;">
          <input type="password" id="daLoginPassword" placeholder=" " required>
          <label for="daLoginPassword">Password</label>
          <i class="fa-solid fa-eye toggle-password" id="daLoginTogglePassword" style="position:absolute; right:18px; top:50%; transform:translateY(-50%); cursor:pointer; color:#999;"></i>
        </div>
        <a href="#" class="auth-link">Forgot password?</a>
        <button type="submit" class="auth-button">Login</button>
        <button type="button" id="daLoginCancelBtn" class="auth-button" style="background:#eee; color:#222; margin-top:10px;">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Direct Agent Registration Modal -->
  <div id="directAgentRegModal" class="auth-modal hidden">
    <div class="auth-backdrop"></div>
    <div class="auth-card" style="max-width: 500px; max-height: 80vh; overflow-y: auto;">
      <h2 style="text-align:center; margin-bottom: 18px;">Become a Direct Agent</h2>
      <p style="text-align:center; color:#666; font-size:14px; margin-bottom:20px;">Please provide your qualifications for admin review</p>
      <div style="background:#e3f2fd; border:1px solid #2196f3; border-radius:8px; padding:12px; margin-bottom:20px;">
        <p style="color:#1976d2; font-size:13px; margin:0;">
          <strong>Note:</strong> Only image files (JPG, PNG, GIF) are accepted. Maximum file size is 5MB per image.
        </p>
      </div>
      
      <form id="directAgentRegForm" class="auth-form active" enctype="multipart/form-data">
        <!-- Personal Information -->
        <h3 style="color:#333; font-size:16px; margin:20px 0 10px 0; border-bottom:1px solid #eee; padding-bottom:5px;">Personal Information</h3>
        
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
        
        <div class="form-group">
          <input type="tel" id="daPhone" placeholder=" " required>
          <label for="daPhone">Phone Number</label>
        </div>
        
        <div class="form-group">
          <input type="text" id="daAddress" placeholder=" " required>
          <label for="daAddress">Complete Address</label>
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

        <!-- Professional Qualifications -->
        <h3 style="color:#333; font-size:16px; margin:20px 0 10px 0; border-bottom:1px solid #eee; padding-bottom:5px;">Professional Qualifications</h3>
        
        <div class="form-group">
          <input type="text" id="daBrokersID" placeholder=" " required>
          <label for="daBrokersID">Broker's License Number</label>
        </div>
        
        <div class="form-group">
          <input type="text" id="daPRCNumber" placeholder=" " required>
          <label for="daPRCNumber">PRC License Number</label>
        </div>
        
        <div class="form-group">
          <select id="daExperience" required>
            <option value="">Select Years of Experience</option>
            <option value="0-1">0-1 years</option>
            <option value="2-3">2-3 years</option>
            <option value="4-5">4-5 years</option>
            <option value="6-10">6-10 years</option>
            <option value="10+">10+ years</option>
          </select>
          <label for="daExperience" style="position:static; color:#888; font-size:14px;">Years of Real Estate Experience</label>
        </div>
        
        <div class="form-group">
          <input type="text" id="daSpecializations" placeholder=" " required>
          <label for="daSpecializations">Specializations (e.g., Residential, Commercial, Luxury)</label>
        </div>
        
        <div class="form-group">
          <textarea id="daExperienceDetails" placeholder=" " rows="3" required style="resize:vertical; min-height:80px;"></textarea>
          <label for="daExperienceDetails">Previous Work Experience & Achievements</label>
        </div>

        <!-- Educational Background -->
        <h3 style="color:#333; font-size:16px; margin:20px 0 10px 0; border-bottom:1px solid #eee; padding-bottom:5px;">Educational Background</h3>
        
        <div class="form-group">
          <input type="text" id="daEducation" placeholder=" " required>
          <label for="daEducation">Highest Education Level</label>
        </div>
        
        <div class="form-group">
          <input type="text" id="daSchool" placeholder=" " required>
          <label for="daSchool">School/University</label>
        </div>
        
        <div class="form-group">
          <input type="text" id="daCourse" placeholder=" " required>
          <label for="daCourse">Course/Degree</label>
        </div>
        
        <div class="form-group">
          <input type="text" id="daGraduationYear" placeholder=" " required>
          <label for="daGraduationYear">Year Graduated</label>
        </div>

        <!-- Certifications & Training -->
        <h3 style="color:#333; font-size:16px; margin:20px 0 10px 0; border-bottom:1px solid #eee; padding-bottom:5px;">Certifications & Training</h3>
        
        <div class="form-group">
          <textarea id="daCertifications" placeholder=" " rows="3" style="resize:vertical; min-height:80px;"></textarea>
          <label for="daCertifications">Professional Certifications (if any)</label>
        </div>
        
        <div class="form-group">
          <textarea id="daTraining" placeholder=" " rows="3" style="resize:vertical; min-height:80px;"></textarea>
          <label for="daTraining">Relevant Training Programs Attended</label>
        </div>

        <!-- Documents Upload -->
        <h3 style="color:#333; font-size:16px; margin:20px 0 10px 0; border-bottom:1px solid #eee; padding-bottom:5px;">Required Documents</h3>
        
        <div class="form-group">
          <label for="daBrokerLicense" style="position:static; color:#888; font-size:14px;">Broker's License Image (required):</label>
          <input type="file" id="daBrokerLicense" accept="image/*" required>
          <small style="color:#666; font-size:12px;">Only JPG, PNG, GIF images accepted (max 5MB)</small>
        </div>
        
        <div class="form-group">
          <label for="daPRCLicense" style="position:static; color:#888; font-size:14px;">PRC License Image (required):</label>
          <input type="file" id="daPRCLicense" accept="image/*" required>
          <small style="color:#666; font-size:12px;">Only JPG, PNG, GIF images accepted (max 5MB)</small>
        </div>
        
        <div class="form-group">
          <label for="daResume" style="position:static; color:#888; font-size:14px;">Resume/CV Image (required):</label>
          <input type="file" id="daResume" accept="image/*" required>
          <small style="color:#666; font-size:12px;">Only JPG, PNG, GIF images accepted (max 5MB)</small>
        </div>
        
        <div class="form-group">
          <label for="daValidID" style="position:static; color:#888; font-size:14px;">Valid Government ID Image (required):</label>
          <input type="file" id="daValidID" accept="image/*" required>
          <small style="color:#666; font-size:12px;">Only JPG, PNG, GIF images accepted (max 5MB)</small>
        </div>
        
        <div class="form-group">
          <label for="daAdditionalDocs" style="position:static; color:#888; font-size:14px;">Additional Images (optional):</label>
          <input type="file" id="daAdditionalDocs" accept="image/*" multiple>
          <small style="color:#666; font-size:12px;">Only JPG, PNG, GIF images accepted (max 5MB each)</small>
        </div>

        <!-- Declaration -->
        <div style="background:#f8f9fa; padding:15px; border-radius:8px; margin:20px 0;">
          <p style="font-size:14px; color:#666; margin:0;">
            <strong>Declaration:</strong> I hereby declare that all information provided above is true and accurate. 
            I understand that providing false information may result in the rejection of my application or termination 
            of my account if discovered later.
          </p>
        </div>

        <button type="submit" class="auth-button">Submit Application</button>
        <button type="button" id="daCancelBtn" class="auth-button" style="background:#eee; color:#222; margin-top:10px;">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Associate Agent Login Modal -->
  <div id="associateAgentLoginModal" class="auth-modal hidden">
    <div class="auth-backdrop"></div>
    <div class="auth-card" style="max-width: 420px;">
      <h2 style="text-align:center; margin-bottom: 18px;">Associate Agent Login</h2>
      <form id="associateAgentLoginForm" class="auth-form active">
        <div class="form-group">
          <input type="text" id="aaLoginEmailOrPhone" placeholder=" " required>
          <label for="aaLoginEmailOrPhone">Email or Phone number</label>
        </div>
        <div class="form-group" style="position:relative;">
          <input type="password" id="aaLoginPassword" placeholder=" " required>
          <label for="aaLoginPassword">Password</label>
          <i class="fa-solid fa-eye toggle-password" id="aaLoginTogglePassword" style="position:absolute; right:18px; top:50%; transform:translateY(-50%); cursor:pointer; color:#999;"></i>
        </div>
        <a href="#" class="auth-link">Forgot password?</a>
        <button type="submit" class="auth-button">Login</button>
        <button type="button" id="aaLoginCancelBtn" class="auth-button" style="background:#eee; color:#222; margin-top:10px;">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Associate Agent Registration Modal -->
  <div id="associateAgentRegModal" class="auth-modal hidden">
    <div class="auth-backdrop"></div>
    <div class="auth-card" style="max-width: 420px;">
      <h2 style="text-align:center; margin-bottom: 18px;">Become an Associate Agent</h2>
      <div style="background:#e3f2fd; border:1px solid #2196f3; border-radius:8px; padding:12px; margin-bottom:20px;">
        <p style="color:#1976d2; font-size:13px; margin:0;">
          <strong>Note:</strong> Only image files (JPG, PNG, GIF) are accepted. Maximum file size is 5MB per image.
        </p>
      </div>
      <form id="associateAgentRegForm" class="auth-form active" enctype="multipart/form-data">
        <div class="form-group" style="display: flex; gap: 10px;">
          <input type="text" id="aaFirstName" placeholder=" " required style="flex:1;">
          <label for="aaFirstName" style="left: 16px;">First Name</label>
          <input type="text" id="aaLastName" placeholder=" " required style="flex:1;">
          <label for="aaLastName" style="left: 52%;">Last Name</label>
        </div>
        <div class="form-group">
          <input type="email" id="aaEmail" placeholder=" " required>
          <label for="aaEmail">Email</label>
        </div>
        <div class="form-group" style="position:relative;">
          <input type="password" id="aaPassword" placeholder=" " required>
          <label for="aaPassword">Password</label>
          <i class="fa-solid fa-eye toggle-password" id="aaTogglePassword" style="position:absolute; right:18px; top:50%; transform:translateY(-50%); cursor:pointer; color:#999;"></i>
        </div>
        <div class="form-group" style="position:relative;">
          <input type="password" id="aaConfirmPassword" placeholder=" " required>
          <label for="aaConfirmPassword">Confirm Password</label>
          <i class="fa-solid fa-eye toggle-password" id="aaToggleConfirmPassword" style="position:absolute; right:18px; top:50%; transform:translateY(-50%); cursor:pointer; color:#999;"></i>
        </div>
        <div class="form-group">
          <input type="text" id="aaBrokersID" placeholder=" " required>
          <label for="aaBrokersID">Broker's ID</label>
        </div>
        <div class="form-group">
          <select id="aaCompany" required>
            <option value="">Select Company</option>
            <option value="1">Real Estate Pro Batangas</option>
            <option value="2">Batangas Properties Inc.</option>
            <option value="3">Prime Real Estate Batangas</option>
          </select>
          <label for="aaCompany" style="position:static; color:#888; font-size:14px;">Company</label>
        </div>
        <div class="form-group">
          <label for="aaUploadID" style="position:static; color:#888; font-size:14px;">Upload ID (optional):</label>
          <input type="file" id="aaUploadID" accept="image/*,application/pdf">
        </div>
        <button type="submit" class="auth-button">Register</button>
        <button type="button" id="aaCancelBtn" class="auth-button" style="background:#eee; color:#222; margin-top:10px;">Cancel</button>
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
          <option value="">Location</option>
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
          <option value="">Property Type</option>
          <option value="Lot">Lot</option>
          <option value="Property">Property</option>
        </select>

        <!-- Price Range -->
        <select id="price" class="rounded-input">
          <option value="">Price Range</option>
          <option value="Under ₱1M">Under ₱1M</option>
          <option value="₱1M - ₱3M">₱1M - ₱3M</option>
          <option value="₱3M - ₱5M">₱3M - ₱5M</option>
          <option value="Over ₱5M">Over ₱5M</option>
        </select>

        <!-- Bedrooms -->
        <select id="bedrooms" class="rounded-input">
          <option value="">Bedrooms</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4+">4+</option>
        </select>

        <!-- Bathrooms -->
        <select id="bathrooms" class="rounded-input">
          <option value="">Bathrooms</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4+">4+</option>
        </select>

        <!-- Size -->
        <select id="size" class="rounded-input">
          <option value="">Size (sqm)</option>
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
      <div class="slider-wrapper" style="margin-bottom: 10px;">
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

  <script src="homepage.js"></script>
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
      console.log('Searching with filters:', filters);
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
    // Direct Agent Registration form submit
    document.getElementById('directAgentRegForm').onsubmit = async function(e) {
      e.preventDefault();
      
      // Get all form values
      const firstName = document.getElementById('daFirstName').value;
      const lastName = document.getElementById('daLastName').value;
      const email = document.getElementById('daEmail').value;
      const phone = document.getElementById('daPhone').value;
      const address = document.getElementById('daAddress').value;
      const password = document.getElementById('daPassword').value;
      const confirmPassword = document.getElementById('daConfirmPassword').value;
      const brokerId = document.getElementById('daBrokersID').value;
      const prcNumber = document.getElementById('daPRCNumber').value;
      const experience = document.getElementById('daExperience').value;
      const specializations = document.getElementById('daSpecializations').value;
      const experienceDetails = document.getElementById('daExperienceDetails').value;
      const education = document.getElementById('daEducation').value;
      const school = document.getElementById('daSchool').value;
      const course = document.getElementById('daCourse').value;
      const graduationYear = document.getElementById('daGraduationYear').value;
      const certifications = document.getElementById('daCertifications').value;
      const training = document.getElementById('daTraining').value;
      
      // Get file inputs
      const brokerLicense = document.getElementById('daBrokerLicense').files[0];
      const prcLicense = document.getElementById('daPRCLicense').files[0];
      const resume = document.getElementById('daResume').files[0];
      const validId = document.getElementById('daValidID').files[0];
      const additionalDocs = document.getElementById('daAdditionalDocs').files;
      
      // Validation
      if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
      }
      
      if (password.length < 6) {
        alert('Password must be at least 6 characters long!');
        return;
      }
      
              // File upload validation
        if (!brokerLicense || !prcLicense || !resume || !validId) {
          alert('Please upload all required documents (Broker License, PRC License, Resume, and Valid ID)');
          return;
        }
        
        // Check file sizes (5MB limit)
        const maxSize = 5 * 1024 * 1024;
        if (brokerLicense.size > maxSize || prcLicense.size > maxSize || resume.size > maxSize || validId.size > maxSize) {
          alert('One or more files exceed the 5MB size limit');
          return;
        }
      
      try {
        // Create FormData for file uploads
        const formData = new FormData();
        
        // Add user data
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('user_type', 'direct_agent');
        formData.append('broker_id', brokerId);
        formData.append('company_id', '');
        
        // Add qualification data
        formData.append('phone', phone);
        formData.append('address', address);
        formData.append('prc_number', prcNumber);
        formData.append('experience_years', experience);
        formData.append('specializations', specializations);
        formData.append('experience_details', experienceDetails);
        formData.append('education', education);
        formData.append('school', school);
        formData.append('course', course);
        formData.append('graduation_year', graduationYear);
        formData.append('certifications', certifications);
        formData.append('training', training);
        
        // Add file uploads
        formData.append('broker_license', brokerLicense);
        formData.append('prc_license', prcLicense);
        formData.append('resume', resume);
        formData.append('valid_id', validId);
        
        // Add additional documents if any
        for (let i = 0; i < additionalDocs.length; i++) {
          formData.append('additional_docs[]', additionalDocs[i]);
        }
        
        console.log('Submitting form data...');
        console.log('FormData entries:');
        for (let [key, value] of formData.entries()) {
          console.log(key + ': ' + value);
        }
        
        const response = await fetch('agent_registration_complete.php?v=' + Date.now(), {
          method: 'POST',
          body: formData
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Response data:', data);
        
        if (!data.success) {
          throw new Error(data.message || 'Registration failed');
        }
        
        alert(data.message);
        document.getElementById('directAgentRegModal').classList.add('hidden');
        this.reset();
        
      } catch (err) {
        console.error('Registration error:', err);
        alert('Registration error: ' + err.message);
      }
    };

    // Show Direct Agent Login Modal
    document.getElementById('directAgentLoginLink').onclick = function(e) {
      e.preventDefault();
      document.getElementById('authModal').classList.add('hidden');
      document.getElementById('directAgentLoginModal').classList.remove('hidden');
    };
    // Hide Direct Agent Login Modal
    document.getElementById('daLoginCancelBtn').onclick = function() {
      document.getElementById('directAgentLoginModal').classList.add('hidden');
    };
    document.querySelectorAll('#directAgentLoginModal .auth-backdrop').forEach(el => {
      el.onclick = function() {
        document.getElementById('directAgentLoginModal').classList.add('hidden');
      };
    });
    // Password eye toggle for Direct Agent Login
    document.getElementById('daLoginTogglePassword').onclick = function() {
      const pw = document.getElementById('daLoginPassword');
      pw.type = pw.type === 'password' ? 'text' : 'password';
      this.classList.toggle('fa-eye-slash');
    };
    // Direct Agent Login form submit
    document.getElementById('directAgentLoginForm').onsubmit = async function(e) {
      e.preventDefault();
      
      const email = document.getElementById('daLoginEmailOrPhone').value;
      const password = document.getElementById('daLoginPassword').value;
      
      try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);
        formData.append('user_type', 'direct_agent');
        
        const response = await fetch('login.php', {
          method: 'POST',
          body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
          throw new Error(data.message || 'Login failed');
        }
        
        alert('Login successful! Redirecting to Direct Agent Dashboard...');
        document.getElementById('directAgentLoginModal').classList.add('hidden');
        
        setTimeout(() => {
          window.location.href = 'direct_agent_dashboard_full.php';
        }, 1000);
        
      } catch (err) {
        console.error('Login error:', err);
        alert('Login error: ' + err.message);
      }
    };

    // Show Associate Agent Registration Modal
    document.getElementById('becomeAssociateAgentBtn').onclick = function() {
      document.getElementById('associateAgentRegModal').classList.remove('hidden');
    };
    // Hide Associate Agent Registration Modal
    document.getElementById('aaCancelBtn').onclick = function() {
      document.getElementById('associateAgentRegModal').classList.add('hidden');
    };
    document.querySelectorAll('#associateAgentRegModal .auth-backdrop').forEach(el => {
      el.onclick = function() {
        document.getElementById('associateAgentRegModal').classList.add('hidden');
      };
    });
    // Password eye toggles for Associate Agent
    document.getElementById('aaTogglePassword').onclick = function() {
      const pw = document.getElementById('aaPassword');
      pw.type = pw.type === 'password' ? 'text' : 'password';
      this.classList.toggle('fa-eye-slash');
    };
    document.getElementById('aaToggleConfirmPassword').onclick = function() {
      const pw = document.getElementById('aaConfirmPassword');
      pw.type = pw.type === 'password' ? 'text' : 'password';
      this.classList.toggle('fa-eye-slash');
    };
    // Associate Agent Registration form submit
    document.getElementById('associateAgentRegForm').onsubmit = async function(e) {
      e.preventDefault();
      
      const firstName = document.getElementById('aaFirstName').value;
      const lastName = document.getElementById('aaLastName').value;
      const email = document.getElementById('aaEmail').value;
      const password = document.getElementById('aaPassword').value;
      const confirmPassword = document.getElementById('aaConfirmPassword').value;
      const brokerId = document.getElementById('aaBrokersID').value;
      const companyId = document.getElementById('aaCompany').value;
      
      // Validation
      if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
      }
      
      if (password.length < 6) {
        alert('Password must be at least 6 characters long!');
        return;
      }
      
      try {
        // Create FormData for associate agent registration
        const formData = new FormData();
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('user_type', 'associate_agent');
        formData.append('broker_id', brokerId);
        formData.append('company_id', companyId);
        
        // Add optional file upload if provided
        const uploadId = document.getElementById('aaUploadID').files[0];
        if (uploadId) {
          formData.append('upload_id', uploadId);
        }
        
        console.log('Submitting associate agent form data...');
        console.log('FormData entries:');
        for (let [key, value] of formData.entries()) {
          console.log(key + ': ' + value);
        }
        
        const response = await fetch('agent_registration_complete.php?v=' + Date.now(), {
          method: 'POST',
          body: formData
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const data = await response.json();
        console.log('Response data:', data);
        
        if (!data.success) {
          throw new Error(data.message || 'Registration failed');
        }
        
        alert(data.message);
        document.getElementById('associateAgentRegModal').classList.add('hidden');
        this.reset();
        
      } catch (err) {
        console.error('Registration error:', err);
        alert('Registration error: ' + err.message);
      }
    };

    // Show Associate Agent Login Modal
    document.getElementById('associateAgentLoginLink').onclick = function(e) {
      e.preventDefault();
      document.getElementById('authModal').classList.add('hidden');
      document.getElementById('associateAgentLoginModal').classList.remove('hidden');
    };
    // Hide Associate Agent Login Modal
    document.getElementById('aaLoginCancelBtn').onclick = function() {
      document.getElementById('associateAgentLoginModal').classList.add('hidden');
    };
    document.querySelectorAll('#associateAgentLoginModal .auth-backdrop').forEach(el => {
      el.onclick = function() {
        document.getElementById('associateAgentLoginModal').classList.add('hidden');
      };
    });
    // Password eye toggle for Associate Agent Login
    document.getElementById('aaLoginTogglePassword').onclick = function() {
      const pw = document.getElementById('aaLoginPassword');
      pw.type = pw.type === 'password' ? 'text' : 'password';
      this.classList.toggle('fa-eye-slash');
    };
    // Associate Agent Login form submit
    document.getElementById('associateAgentLoginForm').onsubmit = async function(e) {
      e.preventDefault();
      
      const email = document.getElementById('aaLoginEmailOrPhone').value;
      const password = document.getElementById('aaLoginPassword').value;
      
      try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);
        formData.append('user_type', 'associate_agent');
        
        const response = await fetch('login.php', {
          method: 'POST',
          body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
          throw new Error(data.message || 'Login failed');
        }
        
        alert('Login successful! Redirecting to Associate Agent Dashboard...');
        document.getElementById('associateAgentLoginModal').classList.add('hidden');
        
        setTimeout(() => {
          window.location.href = 'associate_agent_dashboard_full.php';
        }, 1000);
        
      } catch (err) {
        console.error('Login error:', err);
        alert('Login error: ' + err.message);
      }
    };

    // User Login: AJAX authentication with login.php
    document.getElementById('loginForm').onsubmit = async function(e) {
      e.preventDefault();
      
      const email = document.getElementById('loginEmail').value;
      const password = document.getElementById('loginPassword').value;
      
      // Validation
      if (!email.trim() || !password.trim()) {
        alert('Please enter both email and password.');
        return;
      }
      
      try {
        const formData = new FormData();
        formData.append('email', email.trim());
        formData.append('password', password);
        
        const response = await fetch('login.php', {
          method: 'POST',
          body: formData
        });
        
        if (response.redirected) {
          // Handle redirect response
          window.location.href = response.url;
          return;
        }
        
        const data = await response.json();
        
        if (data.success) {
          alert('Login successful! Redirecting...');
          document.getElementById('authModal').classList.add('hidden');
          
          // Redirect based on user type (this will be handled by login.php)
          setTimeout(() => {
            window.location.href = 'user_index.php';
          }, 1000);
        } else {
          alert('Login failed: ' + (data.message || 'Invalid credentials.'));
        }
        
      } catch (err) {
        console.error('Login error:', err);
        alert('Login error: ' + err.message);
      }
    };

// Property slider logic
const properties = [
  {
    id: 1,
    title: "Modern Family Home",
    location: "Batangas City",
    type: "Property",
    price: "₱3,500,000",
    bedrooms: 3,
    bathrooms: 2,
    sqm: 180,
    image: "Pictures/bg4.jpg",
    features: ["3 BR", "2 BA", "180 sqm"]
  },
  {
    id: 2,
    title: "Luxury Condo Unit",
    location: "Lipa City",
    type: "Property",
    price: "₱2,800,000",
    bedrooms: 2,
    bathrooms: 2,
    sqm: 85,
    image: "Pictures/bg4.jpg",
    features: ["2 BR", "2 BA", "85 sqm"]
  },
  {
    id: 3,
    title: "Premium Lot",
    location: "Tanauan",
    type: "Lot",
    price: "₱1,200,000",
    bedrooms: 0,
    bathrooms: 0,
    sqm: 300,
    image: "Pictures/bg4.jpg",
    features: ["300 sqm", "Residential", "Ready for Construction"]
  },
  {
    id: 4,
    title: "Spacious Villa",
    location: "Nasugbu",
    type: "Property",
    price: "₱5,200,000",
    bedrooms: 4,
    bathrooms: 3,
    sqm: 250,
    image: "Pictures/bg4.jpg",
    features: ["4 BR", "3 BA", "250 sqm"]
  },
  {
    id: 5,
    title: "Lot Space",
    location: "Batangas City",
    type: "Lot",
    price: "₱4,500,000",
    bedrooms: 0,
    bathrooms: 2,
    sqm: 200,
    image: "Pictures/bg4.jpg",
    features: ["200 sqm", "Commercial", "High Traffic"]
  },
  {
    id: 6,
    title: "Cozy Townhouse",
    location: "Lipa City",
    type: "Property",
    price: "₱2,100,000",
    bedrooms: 2,
    bathrooms: 2,
    sqm: 120,
    image: "Pictures/bg4.jpg",
    features: ["2 BR", "2 BA", "120 sqm"]
  }
];
function shuffleArray(arr) {
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
}
const sliderProperties = [...properties];
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
      <div class=\"property-image\">
        <div class=\"property-badge\">${p.type}</div>
        <img src=\"${p.image}\" alt=\"${p.title}\" style=\"width:100%;height:140px;object-fit:cover;border-radius:12px 12px 30px 30px;\">
      </div>
      <div class=\"property-name\">${p.title}</div>
      <div class=\"property-meta\">${p.location}</div>
      <div class=\"property-price\">${p.price}</div>
      <div class=\"property-features\">${p.features ? p.features.join(' | ') : ''}</div>
      <div class=\"property-actions\"><button class=\"view-details-btn\" onclick=\"viewPropertyDetails(${p.id})\">View Details</button></div>
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
// View Details Modal (reuse from user_search.html)
function viewPropertyDetails(propertyId) {
  const property = properties.find(p => p.id === propertyId);
  if (!property) return;
  const modalContent = `
    <div style=\"background: white; padding: 30px; border-radius: 15px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto;\">
      <div style=\"display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;\">
        <h2 style=\"margin: 0; color: #222;\">${property.title}</h2>
        <button onclick=\"closeModal()\" style=\"background: none; border: none; font-size: 24px; cursor: pointer; color: #666;\">×</button>
      </div>
      <div style=\"display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;\">
        <div>
          <img src=\"${property.image}\" alt=\"${property.title}\" style=\"width: 100%; height: 200px; object-fit: cover; border-radius: 10px;\">
        </div>
        <div>
          <h3 style=\"color: #333; margin-bottom: 10px;\">Property Details</h3>
          <p><strong>Location:</strong> ${property.location}</p>
          <p><strong>Type:</strong> ${property.type}</p>
          <p><strong>Price:</strong> ${property.price}</p>
          <p><strong>Bedrooms:</strong> ${property.bedrooms}</p>
          <p><strong>Bathrooms:</strong> ${property.bathrooms}</p>
          <p><strong>Size:</strong> ${property.sqm} sqm</p>
        </div>
      </div>
      <div style=\"margin-bottom: 20px;\">
        <h3 style=\"color: #333; margin-bottom: 10px;\">Description</h3>
        <p>This beautiful property offers modern amenities and is located in a prime area. Perfect for families looking for comfort and convenience.</p>
      </div>
      <div style=\"text-align: center;\">
        <button onclick=\"closeModal()\" style=\"background: #666; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;\">Close</button>
      </div>
    </div>
  `;
  showModal(modalContent);
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
  </script>
</body>
</html>