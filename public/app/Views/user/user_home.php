<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../../../../config/database.php';

// Include modular property card
require_once __DIR__ . '/../../../../components/user_property_card.php';

// Generate user token if missing
if (isset($_SESSION['user']['id']) && empty($_SESSION['user']['token'])) {
    $_SESSION['user']['token'] = base64_encode(json_encode([
        'user_id' => $_SESSION['user']['id'],
        'email' => $_SESSION['user']['email'] ?? '',
        'user_type' => $_SESSION['user']['user_type'] ?? '',
        'exp' => time() + 3600
    ]));
}

$userId = $_SESSION['user']['id'] ?? 0;

// Fetch latest available properties
$sql = "SELECT p.id, p.title, p.location, p.price, p.bedrooms, p.bathrooms, pi.image_path
        FROM properties p
        LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
        WHERE p.status = 'available'
        ORDER BY p.created_at DESC
        LIMIT 5";
$result = $conn->query($sql);
?>

<!-- Welcome Card -->
<div class="welcome-card">
    <h2>Find Your Dream Property</h2>
    <p>Welcome to BatEstate! We're here to help you find the perfect property in Batangas.</p>
    <div class="action-buttons">
        <a href="user_dashboard.php?view=search" class="btn btn-primary"><i class="fa-solid fa-search"></i> Search Properties</a>
        <a href="user_dashboard.php?view=profile" class="btn btn-secondary"><i class="fa-solid fa-user"></i> View Profile</a>
    </div>
</div>

<section class="properties">
  <div class="container">
    <h3 class="section-title">Latest Properties</h3>
    <div class="properties-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($property = $result->fetch_assoc()): ?>
                <?php
                // Fetch past reviews for this property
                $reviewsSql = "
                    SELECT r.*, u.first_name, u.last_name
                    FROM property_reviews r
                    INNER JOIN users u ON r.user_id = u.id
                    WHERE r.property_id = ?
                    ORDER BY r.created_at DESC
                ";
                $stmt = $conn->prepare($reviewsSql);
                $stmt->bind_param("i", $property['id']);
                $stmt->execute();
                $reviewsResult = $stmt->get_result();
                $pastReviews = $reviewsResult->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Attach reviews
                $property['past_reviews'] = $pastReviews;

                // Render modular card with modal & reviews
                render_property_card($property);
                ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>
  </div>
</section>

<!-- Quick Stats -->
<div class="quick-stats">
    <h3>Quick Overview</h3>
    <div class="stats-grid">
        <div class="stat-item"><i class="fa-solid fa-house"></i><span>Browse Properties</span></div>
        <div class="stat-item"><i class="fa-solid fa-user-tie"></i><span>Connect with Agents</span></div>
        <div class="stat-item"><i class="fa-solid fa-heart"></i><span>Save Favorites</span></div>
    </div>
</div>

<!-- Swiper CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css"/>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script src="/BatEstateExplorer/assets/js/user_home.js"></script>
<script src="/BatEstateExplorer/assets/js/property_card_logic.js"></script>
