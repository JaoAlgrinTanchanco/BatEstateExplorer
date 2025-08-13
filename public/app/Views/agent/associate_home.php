<?php
if (!isset($agent) || !is_array($agent)) {
    $agent = isset($user) && is_array($user) ? $user : (function() use ($conn) {
        $u = function_exists('current_user') ? current_user($conn) : null;
        return is_array($u) ? $u : [];
    })();
}

$agentFirst = htmlspecialchars($agent['first_name'] ?? 'Agent');
$agentFull  = htmlspecialchars(
    trim(($agent['first_name'] ?? '') . ' ' . ($agent['last_name'] ?? '')) ?: 'Agent'
);

ob_start();
$agent_id = $agent['id'] ?? 0;

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
  <h2>Welcome back, <?= $agentFirst ?>!</h2>
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

<?php include __DIR__ . '/partials/agent_notifications.php'; ?>
<?php include __DIR__ . '/partials/agent_modals.php'; ?>
<?php include __DIR__ . '/partials/agent_property_scripts.php'; ?>

<style>
  .welcome-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }
  .welcome-card h2 {
    margin: 0 0 10px;
    font-size: 1.5rem;
    color: #333;
  }
  .welcome-card p {
    margin: 0 0 15px;
    color: #666;
  }

  .action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }
  .btn {
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: background 0.2s ease;
  }
  .btn-primary {
    background: #007bff;
    color: #fff;
  }
  .btn-secondary {
    background: #6c757d;
    color: #fff;
  }
  .btn-success {
    background: #28a745;
    color: #fff;
  }
  .btn-outline {
    border: 1px solid #ccc;
    background: white;
    color: #333;
  }
  .btn:hover {
    opacity: 0.9;
  }

  .properties {
    margin-top: 30px;
  }
  .section-title {
    font-size: 1.3rem;
    margin-bottom: 15px;
    color: #333;
  }

  .properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
  }
  .property-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
  }
  .property-image img {
    width: 100%;
    height: 180px;
    object-fit: cover;
  }
  .property-content {
    padding: 15px;
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  .property-content h3 {
    font-size: 1.1rem;
    margin-bottom: 6px;
    color: #333;
  }
  .property-location {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 8px;
  }
  .property-price {
    font-size: 1rem;
    color: #28a745;
    font-weight: bold;
    margin-bottom: 10px;
  }
  .property-features {
    font-size: 0.85rem;
    color: #555;
    display: flex;
    gap: 12px;
    margin-bottom: 10px;
  }
  .view-details-btn {
    align-self: flex-start;
  }
</style>
