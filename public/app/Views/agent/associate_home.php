<?php
// Ensure we have a logged-in user
if (!isset($user) || !is_array($user)) {
    die('Access denied.');
}

require_once __DIR__ . '/../../../../components/notification.php';

// Build a safe first-name for the welcome card
$rawFirst = null;
if (!empty($user['first_name'])) {
    $rawFirst = $user['first_name'];
} elseif (!empty($user['name'])) {
    $parts = preg_split('/\s+/', trim($user['name']));
    $rawFirst = $parts[0] ?? null;
} elseif (!empty($user['username'])) {
    $rawFirst = $user['username'];
} elseif (!empty($user['email'])) {
    $local = strstr($user['email'], '@', true);
    $rawFirst = $local ?: $user['email'];
}
$agentFirst = htmlspecialchars($rawFirst ?: 'Agent', ENT_QUOTES, 'UTF-8');

// --- Properties: ALL active listings for the homepage feed ---
if (!isset($conn)) {
    die('DB connection missing.');
}

$sql = "SELECT id, title, location, price, bedrooms, bathrooms, created_at 
        FROM properties 
        WHERE status IN ('available', 'sold')
        ORDER BY created_at DESC";
$result = $conn->query($sql);

// Include modular card
require_once __DIR__ . '/../../../../components/agent_property_card.php';

?>

<!-- user_home.php -->
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
    <a href="agent_dashboard.php?view=associate_profile" class="btn btn-success">
      <i class="fa-solid fa-plus"></i> Add New Property
    </a>
  </div>
</div>

<section class="properties">
  <div class="container">
    <h3 class="section-title">Latest Properties</h3>

    <div class="properties-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($property = $result->fetch_assoc()): ?>
                <?php render_agent_property_card($property); ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>
  </div>
</section>

<?php
// Render the modal only once
render_agent_property_card([], true);
?>


<!-- Swiper CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css"/>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
