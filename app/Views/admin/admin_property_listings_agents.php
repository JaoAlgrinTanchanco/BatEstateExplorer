<?php
// =====================================
// admin_property_listings_agents.php
// =====================================

// Session and DB setup
session_start();
require_once 'config/database.php';
require_once 'helpers/auth.php'; // Assuming you have an auth helper for is_logged_in() etc.

// Check authentication and admin access
$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

if (!$is_logged_in || !$is_admin) {
    header('Location: admin_login.php');
    exit;
}

// Fetch property listings grouped by associate agents
$query = "
    SELECT 
        u.id AS user_id,
        u.first_name, 
        u.last_name, 
        u.email, 
        u.user_type, 
        c.name AS company_name,
        p.id AS property_id,
        p.name AS property_name,
        p.type AS property_type,
        p.price,
        p.date_uploaded,
        p.image_url
    FROM users u 
    LEFT JOIN agents a ON u.id = a.user_id 
    LEFT JOIN companies c ON a.company_id = c.id 
    LEFT JOIN properties p ON a.id = p.agent_id 
    WHERE u.user_type = 'associate_agent'
    ORDER BY p.date_uploaded DESC
";
$result = mysqli_query($conn, $query);

$agent_properties = [];
while ($row = mysqli_fetch_assoc($result)) {
    $agent_properties[] = $row;
}
?>

<header class="content-header">
  <h1>Property Listings</h1>
  <div class="user-info">
    <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
  </div>
</header>

<div class="content-body">

  <!-- Sort Controls -->
  <div class="sort-row">
    <div class="sort-by">
      <label for="sort">Sort By:</label>
      <select id="sort">
        <option value="name">Name</option>
        <option value="type">Type</option>
        <option value="price">Price</option>
        <option value="date">Date Uploaded</option>
      </select>
    </div>
  </div>

  <!-- Tab Bar -->
  <div class="tab-bar">
    <a href="admin_property_listings.php" class="tab">Direct Agent</a>
    <span class="tab active">Associate Agent</span>
  </div>

  <!-- Property List -->
  <div class="property-list" id="realEstateAgentsList">
    <?php if (empty($agent_properties)): ?>
      <div class="no-properties">No property listings found.</div>
    <?php else: ?>
      <?php foreach ($agent_properties as $property): ?>
        <div class="property-card"
             data-name="<?php echo htmlspecialchars($property['property_name'] ?? ''); ?>"
             data-type="<?php echo htmlspecialchars($property['property_type'] ?? ''); ?>"
             data-price="<?php echo (int)($property['price'] ?? 0); ?>"
             data-date="<?php echo htmlspecialchars($property['date_uploaded'] ?? ''); ?>">

          <div class="property-image"
               style="background-image: url('<?php echo htmlspecialchars($property['image_url'] ?? 'Pictures/bg4.jpg'); ?>')">
          </div>

          <div class="property-info">
            <div class="property-name"><?php echo htmlspecialchars($property['property_name'] ?? ''); ?></div>
            <div class="property-meta">
              <span>Type: <?php echo htmlspecialchars($property['property_type'] ?? 'Unknown'); ?></span>
              <span>₱<?php echo number_format((float)($property['price'] ?? 0), 2); ?></span>
              <span>Date: <?php echo htmlspecialchars($property['date_uploaded'] ?? ''); ?></span>
            </div>
          </div>

          <div class="property-actions">
            <button class="btn-view" data-id="<?php echo $property['property_id'] ?? ''; ?>">View Post</button>
            <button class="btn-doc" data-id="<?php echo $property['property_id'] ?? ''; ?>">View Property Document</button>
            <button class="btn-remove" data-id="<?php echo $property['property_id'] ?? ''; ?>">Remove Post</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>
