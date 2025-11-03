<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/config/database.php';
    require_once __DIR__ . '/components/agent_property_card.php';

    // ================================
    // Fetch Top Performing Properties (Industry Standard)
    // ================================
    $featuredProperties = [];
    $allowedStatuses = ["available", "sold", "ongoing_inquiry"];
    $statusList = "'" . implode("','", $allowedStatuses) . "'";

    // 🏆 1️⃣ Top Rated Properties
    $topRatedSql = "
        SELECT 
            p.*,
            ROUND(AVG(r.rating), 2) AS avg_rating,
            COUNT(r.id) AS total_reviews
        FROM properties p
        INNER JOIN property_reviews r ON p.id = r.property_id
        WHERE 
            p.status IN ($statusList)
        GROUP BY p.id
        HAVING 
            avg_rating >= 4.0
            AND total_reviews >= 5
        ORDER BY 
            avg_rating DESC, 
            total_reviews DESC, 
            p.created_at DESC
        LIMIT 3
    ";

    if ($stmt = $conn->prepare($topRatedSql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $featuredProperties = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }

    // -------------------------------
    // 2️⃣ Fetch Paid Featured Properties if top-rated < 3
    // -------------------------------
    $needed = 3 - count($featuredProperties);
    if ($needed > 0) {
        $excludeIds = array_column($featuredProperties, 'id');
        $excludeStr = !empty($excludeIds)
            ? "AND p.id NOT IN (" . implode(',', array_map('intval', $excludeIds)) . ")"
            : "";

        $paidFeaturedSql = "
            SELECT 
                p.*,
                TIMESTAMPDIFF(DAY, NOW(), p.featured_until) AS feature_duration
            FROM properties p
            WHERE 
                p.is_featured = 1
                AND p.featured_until > NOW()
                AND p.status IN ($statusList)
                $excludeStr
            ORDER BY 
                feature_duration DESC,
                p.featured_until DESC,
                p.created_at DESC
            LIMIT $needed
        ";

        $paidFeatured = [];
        if ($paidStmt = $conn->prepare($paidFeaturedSql)) {
            $paidStmt->execute();
            $paidResult = $paidStmt->get_result();
            if ($paidResult && $paidResult->num_rows > 0) {
                $paidFeatured = $paidResult->fetch_all(MYSQLI_ASSOC);
            }
            $paidStmt->close();
        }

        // Merge top-rated + paid featured
        $featuredProperties = array_merge($featuredProperties, $paidFeatured);
    }

    // Optional: Decode JSON images for each property
    foreach ($featuredProperties as &$prop) {
        if (!empty($prop['images'])) {
            $prop['images'] = json_decode($prop['images'], true);
        }
    }
    unset($prop); // break reference

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BatEstate Explorer - Find Your Dream Property</title>
    <link rel="stylesheet" href="assets/css/hero.css">
    <link rel="stylesheet" href="assets/css/property_card.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php
        // Determine if there are any featured properties
        $hasFeaturedProperties = !empty($featuredProperties);
    ?>

    <!-- Navigation -->
    <nav class="navbar scroll-animation">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="assets/images/vector 1.png" alt="BatEstate Explorer Logo" class="nav-logo-img">
                <span>BatEstate Explorer</span>
            </div>

            <div class="nav-menu scroll-animation">
                <a href="#home" class="nav-link">Home</a>
                
                <?php if ($hasFeaturedProperties): ?>
                    <a href="#properties" class="nav-link">Properties</a>
                <?php endif; ?>
                
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
    <?php if (!empty($featuredProperties)): ?>
        <section id="properties" class="properties scroll-animation">
            <div class="container scroll-animation">
                <h2 class="section-title scroll-animation">Featured Properties</h2>
                <p class="section-subtitle scroll-animation">Discover our handpicked selection of premium properties</p>
                <div class="properties-grid">
                    <?php
                    // Limit to 3 properties
                    $displayProperties = array_slice($featuredProperties, 0, 3);

                    // Render each property card
                    foreach ($displayProperties as $property):
                        // Add extra metadata if needed
                        $property['data_type'] = $property['property_type'] ?? '';
                        $property['data_size'] = $property['sqm'] ?? 0;

                        // Render card
                        render_agent_property_card($property, false);
                    endforeach;

                    // If fewer than 3 properties, fill remaining slots with hidden placeholders
                    $missing = 3 - count($displayProperties);
                    for ($i = 0; $i < $missing; $i++): ?>
                        <div class="property-card scroll-animation" style="visibility:hidden;"></div>
                    <?php endfor; ?>
                </div>

                <div class="properties-cta scroll-animation">
                    <a href="auth/login.php" class="btn btn-primary btn-large">
                        <i class="fas fa-search"></i>
                        Browse All Properties
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

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

