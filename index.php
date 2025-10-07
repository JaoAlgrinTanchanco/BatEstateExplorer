<?php
<<<<<<< HEAD
=======
require_once $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/database/cleanup_accounts.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/database/cleanup_database.php';

>>>>>>> origin/ansel
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
    <style>
        /* Scroll animations */
        .scroll-animation {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }
        .scroll-animation.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Flash message styles (existing) */
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
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar scroll-animation">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="assets/images/vector 1.png" alt="BatEstate Explorer Logo" class="nav-logo-img">
                <span>BatEstate Explorer</span>
            </div>

            <div class="nav-menu scroll-animation">
                <a href="#home" class="nav-link">Home</a>
                <a href="#properties" class="nav-link">Properties</a>
                <a href="#about" class="nav-link">About</a>
                <a href="#contact" class="nav-link">Contact</a>
                <a href="#cta" class="nav-link glow-link">Become an Agent</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero scroll-animation">
        <div class="hero-content scroll-animation">
            <h1 class="hero-title scroll-animation">Find Your Perfect Property</h1>
            <p class="hero-subtitle scroll-animation">Discover amazing properties in your area with our comprehensive real estate platform</p>
            <div class="hero-buttons scroll-animation">
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
    </section>

    <!-- Properties Section -->
    <section id="properties" class="properties scroll-animation">
        <div class="container scroll-animation">
            <h2 class="section-title scroll-animation">Featured Properties</h2>
            <p class="section-subtitle scroll-animation">Discover our handpicked selection of premium properties</p>
            <div class="properties-grid">
                <div class="property-card scroll-animation">
                    <div class="property-image scroll-animation">
                        <img src="assets/images/aa.jpeg" alt="Luxury Home">
                        <div class="property-badge">Featured</div>
                    </div>
                    <div class="property-content scroll-animation">
                        <h3>Modern Luxury Villa</h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> Prime Location</p>
                        <p class="property-price">$850,000</p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> 4 Beds</span>
                            <span><i class="fas fa-bath"></i> 3 Baths</span>
                            <span><i class="fas fa-ruler-combined"></i> 2,500 sqft</span>
                        </div>
                        <a href="auth/login.php" class="btn btn-outline" style="padding: 7px 12px; margin-top: 20px; font-size: 12px;">View Details</a>
                    </div>
                </div>
                <div class="property-card scroll-animation">
                    <div class="property-image scroll-animation">
                        <img src="assets/images/bb.jpeg" alt="Townhouse">
                        <div class="property-badge">New</div>
                    </div>
                    <div class="property-content scroll-animation">
                        <h3>Cozy Townhouse</h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> Family Neighborhood</p>
                        <p class="property-price">$450,000</p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> 3 Beds</span>
                            <span><i class="fas fa-bath"></i> 2 Baths</span>
                            <span><i class="fas fa-ruler-combined"></i> 1,800 sqft</span>
                        </div>
                        <a href="auth/login.php" class="btn btn-outline" style="padding: 7px 12px; margin-top: 20px; font-size: 12px;">View Details</a>
                    </div>
                </div>
                <div class="property-card scroll-animation">
                    <div class="property-image scroll-animation">
                        <img src="assets/images/cc.jpeg" alt="Apartment">
                        <div class="property-badge">Hot Deal</div>
                    </div>
                    <div class="property-content scroll-animation">
                        <h3>Downtown Apartment</h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> City Center</p>
                        <p class="property-price">$320,000</p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> 2 Beds</span>
                            <span><i class="fas fa-bath"></i> 2 Baths</span>
                            <span><i class="fas fa-ruler-combined"></i> 1,200 sqft</span>
                        </div>
                        <a href="auth/login.php" class="btn btn-outline" style="padding: 7px 12px; margin-top: 20px; font-size: 12px;">View Details</a>
                    </div>
                </div>
            </div>
            <div class="properties-cta scroll-animation">
                <a href="auth/login.php" class="btn btn-primary btn-large">
                    <i class="fas fa-search"></i>
                    Browse All Properties
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features scroll-animation">
        <div class="container scroll-animation">
            <h2 class="section-title scroll-animation">Why Choose BatEstate Explorer?</h2>
            <div class="features-grid">
                <div class="feature-card scroll-animation">
                    <div class="feature-icon"><i class="fas fa-search"></i></div>
                    <h3>Smart Search</h3>
                    <p>Find properties that match your exact criteria with our advanced search filters.</p>
                </div>
                <div class="feature-card scroll-animation">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>Trusted Platform</h3>
                    <p>Connect with verified agents and browse legitimate property listings.</p>
                </div>
                <div class="feature-card scroll-animation">
                    <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h3>Mobile Friendly</h3>
                    <p>Access our platform from anywhere with our responsive mobile design.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about scroll-animation">
        <div class="container scroll-animation">
            <div class="about-content scroll-animation">
                <div class="about-text scroll-animation">
                    <h2 class="section-title scroll-animation">About BatEstate Explorer</h2>
                    <p>We are a leading real estate platform dedicated to connecting buyers, sellers, and agents in a seamless and trustworthy environment...</p>
                    <p>With years of experience in the real estate industry, we understand the importance of finding the perfect property...</p>
                    <div class="about-stats">
                        <div class="stat-item scroll-animation">
                            <h3>1000+</h3>
                            <p>Properties Listed</p>
                        </div>
                        <div class="stat-item scroll-animation">
                            <h3>500+</h3>
                            <p>Happy Clients</p>
                        </div>
                        <div class="stat-item scroll-animation">
                            <h3>50+</h3>
                            <p>Expert Agents</p>
                        </div>
                    </div>
                </div>
                <div class="about-image scroll-animation">
                    <img src="assets/images/dd.jpeg" alt="About BatEstate Explorer">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact scroll-animation">
        <div class="container scroll-animation">
            <h2 class="section-title scroll-animation">Get in Touch</h2>
            <p class="section-subtitle scroll-animation">Have questions? We'd love to hear from you.</p>
            <div class="contact-content scroll-animation">
                <div class="contact-info scroll-animation">
                    <div class="contact-item scroll-animation">
                        <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h4>Address</h4>
                            <p>123 Real Estate Street<br>Property City, PC 12345</p>
                        </div>
                    </div>
                    <div class="contact-item scroll-animation">
                        <div class="contact-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <h4>Phone</h4>
                            <p>+1 (555) 123-4567</p>
                        </div>
                    </div>
                    <div class="contact-item scroll-animation">
                        <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <h4>Email</h4>
                            <p>info@batestate.com</p>
                        </div>
                    </div>
                </div>
                <div class="contact-form scroll-animation">
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
    <section id="cta" class="cta scroll-animation">
        <div class="container scroll-animation">
            <h2 class="scroll-animation">Ready to Start Your Property Journey?</h2>
            <p class="scroll-animation">Join thousands of satisfied users who found their dream properties with us.</p>
            <div class="cta-buttons scroll-animation">
                <a href="auth/signup.php" class="btn btn-primary btn-large" style="margin-bottom: 0.5rem">
                    <i class="fas fa-rocket"></i>
                    Start Exploring Now
                </a>
                <a href="auth/agent_registration.php" class="btn btn-secondary btn-large">
                    <i class="fas fa-user-tie"></i>
                    Become an Agent
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer scroll-animation">
        <div class="container scroll-animation">
            <div class="footer-content scroll-animation">
                <div class="footer-section scroll-animation">
                    <h3>BatEstate Explorer</h3>
                    <p>Your trusted partner in finding the perfect property.</p>
                </div>
                <div class="footer-section scroll-animation">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#properties">Properties</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section scroll-animation">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-envelope"></i> info@batestate.com</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                </div>
            </div>
            <div class="footer-bottom scroll-animation">
                <p>&copy; 2025 BatEstate Explorer. All rights reserved.</p>
            </div>
        </div>
    </footer>

<script>
  // Smooth scroll to section and highlight active nav link
  const navLinks = document.querySelectorAll('.nav-link');

  function removeActive() {
    navLinks.forEach(link => {
      link.style.color = '';
      link.style.transform = '';
    });
  }

  navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const targetId = this.getAttribute('href').substring(1);
      const target = document.getElementById(targetId);

      if (target) {
        const targetTop = target.getBoundingClientRect().top + window.scrollY;
        const sectionHeight = target.offsetHeight;
        const viewportHeight = window.innerHeight;
        const scrollTo = targetTop - (viewportHeight / 2) + (sectionHeight / 2);

        window.scrollTo({
          top: scrollTo,
          behavior: 'smooth'
        });

        // Highlight active link
        removeActive();
        this.style.color = '#000';
        this.style.transform = 'scale(1.3)';
      }
    });
  });

  // Highlight nav link on scroll based on viewport
  const sections = document.querySelectorAll('section');
  window.addEventListener('scroll', () => {
    let scrollPos = window.scrollY + window.innerHeight / 2; // center of viewport
    sections.forEach(sec => {
      const secTop = sec.offsetTop;
      const secBottom = secTop + sec.offsetHeight;
      const id = sec.getAttribute('id');

      if (scrollPos >= secTop && scrollPos < secBottom) {
        removeActive();
        const activeLink = document.querySelector(`.nav-link[href="#${id}"]`);
        if (activeLink) {
          activeLink.style.color = '#000';
          activeLink.style.transform = 'scale(1.3)';
        }
      }
    });
  });

  // Contact form handler
  document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const name = formData.get('name');
    const email = formData.get('email');
    const message = formData.get('message');

    if (!name || !email || !message) {
      alert('Please fill in all fields.');
      return;
    }

    alert("Thank you for your message! We'll get back to you soon.");
    this.reset();
  });

  // Navbar scroll behavior
  window.addEventListener("scroll", () => {
    const navbar = document.querySelector(".navbar");
    if (window.scrollY > 50) {
      navbar.classList.add("scrolled");
    } else {
      navbar.classList.remove("scrolled");
    }
  });

  // Intersection Observer with staggered delay
  const scrollElements = document.querySelectorAll('.scroll-animation');

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const parent = entry.target.parentElement;
        const children = Array.from(parent.children).filter(child => child.classList.contains('scroll-animation'));
        
        children.forEach((child, index) => {
          setTimeout(() => {
            child.classList.add('visible');
          }, index * 150);
        });

        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  scrollElements.forEach(el => observer.observe(el));
</script>

</body>
</html>
