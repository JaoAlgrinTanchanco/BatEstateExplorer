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
    <title>BatEstateExplorer - Find Your Dream Property</title>
    <link rel="stylesheet" href="assets/css/hero.css">
    <link rel="stylesheet" href="assets/css/property_card.css">
    <link rel="stylesheet" href="assets/css/notification.css">
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
                <img src="assets/images/vector 1.png" alt="BatEstateExplorer Logo" class="nav-logo-img">
                <span>BatEstateExplorer</span>
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
    
    <!-- Slideshow Background -->
    <div class="hero-slideshow">
        <div class="slide" style="background-image:url('/BatEstateExplorer/assets/images/hero_image.png')"></div>
        <div class="slide" style="background-image:url('/BatEstateExplorer/assets/images/hero2.png')"></div>
        <div class="slide" style="background-image:url('/BatEstateExplorer/assets/images/hero3.png')"></div>
        <div class="slide" style="background-image:url('/BatEstateExplorer/assets/images/hero4.png')"></div>
        <div class="slide" style="background-image:url('/BatEstateExplorer/assets/images/hero5.png')"></div>
    </div>

    <!-- Hero Content -->
    <div class="hero-content scroll-animation">
        <h1 class="hero-title scroll-animation">Find Your Perfect Property</h1>
        <p class="hero-subtitle scroll-animation">
        Discover amazing properties in your area with our comprehensive real estate platform
        </p>
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
            <h2 class="section-title scroll-animation">Why Choose BatEstateExplorer?</h2>
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
                    <h2 class="section-title scroll-animation">About BatEstateExplorer</h2>
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
                    <img src="assets/images/dd.jpeg" alt="About BatEstateExplorer">
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
                            <p>123 Batangas Province<br>Property City, 4234</p>
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
                            <p>batestate07@gmail.com</p>
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
                    <h3>BatEstateExplorer</h3>
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
                    <p><i class="fas fa-envelope"></i> batestate07@gmail.com</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                </div>
            </div>
            <div class="footer-bottom scroll-animation">
                <p>&copy; 2025 BatEstateExplorer. All rights reserved.</p>
            </div>
        </div>
    </footer>

<script>
    document.addEventListener("DOMContentLoaded", () => {

        // ====== Notification Helper ======
        function notify(type, message) {
            let container = document.querySelector(".notification-container");
            if (!container) {
                container = document.createElement("div");
                container.className = "notification-container";
                document.body.appendChild(container);
            }

            const notif = document.createElement("div");
            notif.className = `notification ${type}`;
            notif.innerHTML = `
                <div class="notification__title">${message}</div>
                <div class="notification__close">&times;</div>
            `;
            container.appendChild(notif);

            notif.querySelector(".notification__close").addEventListener("click", () => notif.remove());
            setTimeout(() => notif.remove(), 5000);
        }

        // ====== NAVIGATION ======
        const navLinks = document.querySelectorAll('.nav-link');
        const sections = document.querySelectorAll('section');
        const navbar = document.querySelector(".navbar");

        function removeActiveNav() {
            navLinks.forEach(link => {
                link.style.color = '';
                link.style.transform = '';
            });
        }

        navLinks.forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const targetId = link.getAttribute('href').slice(1);
                const target = document.getElementById(targetId);
                if (!target) return;

                const scrollTo = target.getBoundingClientRect().top + window.scrollY - window.innerHeight / 2 + target.offsetHeight / 2;

                window.scrollTo({ top: scrollTo, behavior: 'smooth' });

                removeActiveNav();
                link.style.color = '#000';
                link.style.transform = 'scale(1.3)';
            });
        });

        window.addEventListener('scroll', () => {
            const scrollCenter = window.scrollY + window.innerHeight / 2;

            sections.forEach(sec => {
                const secTop = sec.offsetTop;
                const secBottom = secTop + sec.offsetHeight;
                const id = sec.getAttribute('id');

                if (scrollCenter >= secTop && scrollCenter < secBottom) {
                    removeActiveNav();
                    const activeLink = document.querySelector(`.nav-link[href="#${id}"]`);
                    if (activeLink) {
                        activeLink.style.color = '#000';
                        activeLink.style.transform = 'scale(1.3)';
                    }
                }
            });

            if (window.scrollY > 50) {
                navbar.classList.add("scrolled");
            } else {
                navbar.classList.remove("scrolled");
            }
        });

        // ====== SCROLL ANIMATIONS ======
        const scrollElements = document.querySelectorAll('.scroll-animation');
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const parent = entry.target.parentElement;
                    const children = Array.from(parent.children).filter(c => c.classList.contains('scroll-animation'));

                    children.forEach((child, index) => {
                        setTimeout(() => child.classList.add('visible'), index * 150);
                    });

                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        scrollElements.forEach(el => observer.observe(el));

        // ====== CONTACT FORM ======
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', e => {
                e.preventDefault();
                const formData = new FormData(contactForm);
                const name = formData.get('name');
                const email = formData.get('email');
                const message = formData.get('message');

                if (!name || !email || !message) {
                    notify('error', 'Please fill in all fields.');
                    return;
                }

                fetch('public/api/contact_email.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        notify('success', data.message || 'Message sent successfully!');
                        contactForm.reset();
                    } else {
                        notify('error', data.message || 'Error sending message. Please try again.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    notify('error', 'Error sending message. Please try again.');
                });
            });
        }

    });
</script>

</body>

</html>

