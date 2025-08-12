<?php
session_start();

if (isset($_SESSION['flash_message'])) {
    echo '<div class="flash-message" id="flashMessage">'
        . htmlspecialchars($_SESSION['flash_message'])
        . '<button class="close-btn" onclick="document.getElementById(\'flashMessage\').style.display=\'none\'">&times;</button>'
        . '</div>';
    unset($_SESSION['flash_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BatEstate Explorer - Find Your Dream Property</title>
    <link rel="stylesheet" href="assets/css/hero.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-home"></i>
                <span>BatEstate Explorer</span>
            </div>
            <div class="nav-menu">
                <a href="#home" class="nav-link">Home</a>
                <a href="#properties" class="nav-link">Properties</a>
                <a href="#about" class="nav-link">About</a>
                <a href="#contact" class="nav-link">Contact</a>
                <a href="auth/login.php" class="nav-link login-btn">Login</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Find Your Perfect Property</h1>
            <p class="hero-subtitle">Discover amazing properties in your area with our comprehensive real estate platform</p>
            <div class="hero-buttons">
                <a href="auth/signup.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Get Started
                </a>
                <a href="auth/login.php" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </a>
            </div>
        </div>
        <div class="hero-image">
            <img src="assets/images/bg4.jpg" alt="Beautiful Property">
        </div>
    </section>

    <!-- Properties Section -->
    <section id="properties" class="properties">
        <div class="container">
            <h2 class="section-title">Featured Properties</h2>
            <p class="section-subtitle">Discover our handpicked selection of premium properties</p>
            <div class="properties-grid">
                <div class="property-card">
                    <div class="property-image">
                        <img src="assets/images/bg4.jpg" alt="Luxury Home">
                        <div class="property-badge">Featured</div>
                    </div>
                    <div class="property-content">
                        <h3>Modern Luxury Villa</h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> Prime Location</p>
                        <p class="property-price">$850,000</p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> 4 Beds</span>
                            <span><i class="fas fa-bath"></i> 3 Baths</span>
                            <span><i class="fas fa-ruler-combined"></i> 2,500 sqft</span>
                        </div>
                        <a href="auth/login.php" class="btn btn-outline">View Details</a>
                    </div>
                </div>
                <div class="property-card">
                    <div class="property-image">
                        <img src="assets/images/bg4.jpg" alt="Townhouse">
                        <div class="property-badge">New</div>
                    </div>
                    <div class="property-content">
                        <h3>Cozy Townhouse</h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> Family Neighborhood</p>
                        <p class="property-price">$450,000</p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> 3 Beds</span>
                            <span><i class="fas fa-bath"></i> 2 Baths</span>
                            <span><i class="fas fa-ruler-combined"></i> 1,800 sqft</span>
                        </div>
                        <a href="auth/login.php" class="btn btn-outline">View Details</a>
                    </div>
                </div>
                <div class="property-card">
                    <div class="property-image">
                        <img src="assets/images/bg4.jpg" alt="Apartment">
                        <div class="property-badge">Hot Deal</div>
                    </div>
                    <div class="property-content">
                        <h3>Downtown Apartment</h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> City Center</p>
                        <p class="property-price">$320,000</p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> 2 Beds</span>
                            <span><i class="fas fa-bath"></i> 2 Baths</span>
                            <span><i class="fas fa-ruler-combined"></i> 1,200 sqft</span>
                        </div>
                        <a href="auth/login.php" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            </div>
            <div class="properties-cta">
                <a href="auth/login.php" class="btn btn-primary btn-large">
                    <i class="fas fa-search"></i>
                    Browse All Properties
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Why Choose BatEstate Explorer?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Smart Search</h3>
                    <p>Find properties that match your exact criteria with our advanced search filters.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Trusted Platform</h3>
                    <p>Connect with verified agents and browse legitimate property listings.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Access our platform from anywhere with our responsive mobile design.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2 class="section-title">About BatEstate Explorer</h2>
                    <p>We are a leading real estate platform dedicated to connecting buyers, sellers, and agents in a seamless and trustworthy environment. Our mission is to make property discovery and transactions as simple and efficient as possible.</p>
                    <p>With years of experience in the real estate industry, we understand the importance of finding the perfect property that matches your lifestyle and investment goals.</p>
                    <div class="about-stats">
                        <div class="stat-item">
                            <h3>1000+</h3>
                            <p>Properties Listed</p>
                        </div>
                        <div class="stat-item">
                            <h3>500+</h3>
                            <p>Happy Clients</p>
                        </div>
                        <div class="stat-item">
                            <h3>50+</h3>
                            <p>Expert Agents</p>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="assets/images/bg4.jpg" alt="About BatEstate Explorer">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <h2 class="section-title">Get in Touch</h2>
            <p class="section-subtitle">Have questions? We'd love to hear from you.</p>
            <div class="contact-content">
                <div class="contact-info">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4>Address</h4>
                            <p>123 Real Estate Street<br>Property City, PC 12345</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h4>Phone</h4>
                            <p>+1 (555) 123-4567</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4>Email</h4>
                            <p>info@batestate.com</p>
                        </div>
                    </div>
                </div>
                <div class="contact-form">
                    <form id="contactForm">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <textarea name="message" placeholder="Your Message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="cta">
        <div class="container">
            <h2>Ready to Start Your Property Journey?</h2>
            <p>Join thousands of satisfied users who found their dream properties with us.</p>
            <div class="cta-buttons">
                <a href="auth/signup.php" class="btn btn-primary btn-large">
                    <i class="fas fa-rocket"></i>
                    Start Exploring Now
                </a>
                <a href="auth/agent_registration.php" class="btn btn-outline btn-large">
                    <i class="fas fa-user-tie"></i>
                    Become an Agent
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>BatEstate Explorer</h3>
                    <p>Your trusted partner in finding the perfect property.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#properties">Properties</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-envelope"></i> info@batestate.com</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 BatEstate Explorer. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/hero.js"></script>
    <script>
        // Contact form handler
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const name = formData.get('name');
            const email = formData.get('email');
            const message = formData.get('message');
            
            // Simple validation
            if (!name || !email || !message) {
                alert('Please fill in all fields.');
                return;
            }
            
            // Show success message (in a real app, this would send to a server)
            alert('Thank you for your message! We\'ll get back to you soon.');
            this.reset();
        });
    </script>
</body>
</html>

<style>
.flash-message {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 99999;

    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    padding: 15px 20px;
    max-width: 350px;
    border-radius: 6px;
    font-weight: 600;
    text-align: center;
    font-family: Arial, sans-serif;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.flash-message .close-btn {
    position: absolute;
    right: 12px;
    top: 12px;
    background: transparent;
    border: none;
    font-size: 18px;
    font-weight: bold;
    color: #155724;
    cursor: pointer;
}
</style>

<?php
session_start();

if (isset($_SESSION['flash_message'])) {
    echo '<div class="flash-message" id="flashMessage">'
        . htmlspecialchars($_SESSION['flash_message'])
        . '<button class="close-btn" onclick="document.getElementById(\'flashMessage\').style.display=\'none\'">&times;</button>'
        . '</div>';
    unset($_SESSION['flash_message']);
}
?>

<script>
  // Optional: auto-hide message after 5 seconds
  setTimeout(() => {
    const flash = document.getElementById('flashMessage');
    if(flash) flash.style.display = 'none';
  }, 5000);
</script>
