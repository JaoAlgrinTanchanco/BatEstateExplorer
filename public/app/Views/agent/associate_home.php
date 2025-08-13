<?php
ob_start();
$agent_id = $agent['id'];

$sql = "SELECT p.id, p.title, p.location, p.price, p.bedrooms, p.bathrooms, pi.image_path
        FROM properties p
        LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
        WHERE p.agent_id = ?
        ORDER BY p.created_at DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="welcome-card">
  <h2>Welcome back, <?= htmlspecialchars($agent['first_name']) ?>!</h2>
  <p>Here’s a quick overview of your latest listings and tools.</p>
  
  <div class="action-buttons">
    <a href="agent_dashboard.php?view=associate_search" class="btn btn-primary">
      <i class="fa-solid fa-search"></i> Search Properties
    </a>
    <a href="agent_dashboard.php?view=associate_profile" class="btn btn-secondary">
      <i class="fa-solid fa-user"></i> Manage Profile
    </a>
    <a href="agent_add_property.php" class="btn btn-success">
      <i class="fa-solid fa-plus"></i> Add New Property
    </a>
  </div>
</div>

<section class="properties">
  <div class="container">
    <h3 class="section-title">Your Latest Properties</h3>

    <div class="properties-grid">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($property = $result->fetch_assoc()): ?>
          <div class="property-card">
            <div class="property-image">
              <?php if ($property['image_path']): ?>
                <img src="<?= htmlspecialchars($property['image_path']) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
              <?php else: ?>
                <img src="/BatEstateExplorer/assets/images/bg4.jpg" alt="Default Image">
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
              <button class="btn btn-outline view-details-btn" data-id="<?= (int)$property['id'] ?>">View Details</button>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>You haven’t listed any properties yet.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../partials/agent_property_modal.php'; ?>
