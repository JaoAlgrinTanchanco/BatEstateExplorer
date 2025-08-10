<?php
// user_home.php
ob_start();

// Make sure $conn is your active mysqli connection
// If not available here, include or require your DB connection file
// Example:
// require_once __DIR__ . '/../../app/bootstrap.php';

$sql = "SELECT p.id, p.title, p.location, p.price, p.bedrooms, p.bathrooms, pi.image_path
        FROM properties p
        LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
        WHERE p.status = 'available'
        ORDER BY p.created_at DESC
        LIMIT 5";

$result = $conn->query($sql);
?>

<!-- user_home.php -->
<div class="welcome-card">
    <h2>Find Your Dream Property</h2>
    <p>Welcome to BatEstate! We're here to help you find the perfect property in Batangas.</p>
    
    <div class="action-buttons">
        <a href="user_dashboard.php?view=search" class="btn btn-primary">
            <i class="fa-solid fa-search"></i> Search Properties
        </a>
        <a href="user_dashboard.php?view=profile" class="btn btn-secondary">
            <i class="fa-solid fa-user"></i> View Profile
        </a>
    </div>
</div>  

<section class="properties">
  <div class="container">
    <h3 class="section-title">Latest Properties</h3>

    <div class="properties-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($property = $result->fetch_assoc()): ?>
                <div class="property-card">
                    <div class="property-image">
                        <?php if ($property['image_path']): ?>
                            <img src="<?= htmlspecialchars($property['image_path']) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                        <?php else: ?>
                            <img src="assets/images/default-property.jpg" alt="No image available">
                        <?php endif; ?>
                    </div>
                    <div class="property-content">
                        <h3><?= htmlspecialchars($property['title']) ?></h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?></p>
                        <p class="property-price">₱<?= number_format($property['price'], 2) ?></p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> <?= (int)$property['bedrooms'] ?> Beds</span>
                            <span><i class="fas fa-bath"></i> <?= (int)$property['bathrooms'] ?> Baths</span>
                        </div>
                        <a href="property_details.php?id=<?= (int)$property['id'] ?>" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>
  </div>
</section>

<div class="quick-stats">
    <h3>Quick Overview</h3>
    <div class="stats-grid">
        <div class="stat-item">
            <i class="fa-solid fa-house"></i>
            <span>Browse Properties</span>
        </div>
        <div class="stat-item">
            <i class="fa-solid fa-user-tie"></i>
            <span>Connect with Agents</span>
        </div>
        <div class="stat-item">
            <i class="fa-solid fa-heart"></i>
            <span>Save Favorites</span>
        </div>
    </div>
</div>
