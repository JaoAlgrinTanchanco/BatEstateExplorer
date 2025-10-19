<?php
session_start();

// --- Bootstrap & Components ---
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/../components/agent_property_card.php';

// --- Determine Current User (if logged in) ---
$current_user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['user_type'] ?? 'guest';

// --- Validate & Fetch Agent Info ---
$user_id = $_GET['agent_id'] ?? null;
if (!$user_id) {
    die("No agent specified.");
}

// --- Fetch Agent Info (from users) ---
$stmt = $conn->prepare("
    SELECT id, first_name, last_name, email, profile_image_path, user_type, bio, specialization, created_at
    FROM users
    WHERE id = ? AND user_type IN ('direct_agent', 'associate_agent')
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$agent = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$agent) {
    die("Agent not found or invalid.");
}

// --- Get Corresponding Agent Record in agents table ---
$stmt = $conn->prepare("SELECT id FROM agents WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$agent_row = $result->fetch_assoc();
$stmt->close();

$real_agent_id = $agent_row['id'] ?? null;

if (!$real_agent_id) {
    echo "<script>console.warn('⚠️ No matching record found in agents table for user_id = " . $user_id . "');</script>";
    $real_agent_id = 0;
}

// --- Agent Info ---
$agent_name = trim($agent['first_name'] . ' ' . $agent['last_name']);
$agent_image = !empty($agent['profile_image_path'])
    ? '/BatEstateExplorer/storage/uploads/profile_images/' . basename($agent['profile_image_path'])
    : '/BatEstateExplorer/assets/img/default-user.png';

// --- Fetch Agent’s Property Listings (owned or sold by them) ---
$properties = [];
$stmt = $conn->prepare("
    SELECT 
        p.id, p.title, p.price, p.location, p.description, p.property_type,
        p.bedrooms, p.bathrooms, p.sqm, p.lot_size, p.status,
        p.images, p.created_at,
        s.first_name AS sold_by_first_name, s.last_name AS sold_by_last_name
    FROM properties p
    LEFT JOIN users s ON p.sold_by_agent_id = s.id
    WHERE p.agent_id = ? OR p.sold_by_agent_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("ii", $real_agent_id, $real_agent_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['images'] = !empty($row['images']) ? json_decode($row['images'], true) : [];
    if (!is_array($row['images'])) $row['images'] = [];

    $row['sold_by'] = !empty($row['sold_by_first_name'])
        ? $row['sold_by_first_name'] . ' ' . $row['sold_by_last_name']
        : null;

    $properties[] = $row;
}
$stmt->close();

// PHP LOGIC: Calculate years hosting
// You can put this logic near the top of your file before the HTML starts.
$created_date = strtotime($agent['created_at']);
$current_date = time();
// Calculate the difference in years. Use floor() to get a whole number.
$years_hosting = floor(($current_date - $created_date) / (365.25 * 24 * 60 * 60));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($agent_name) ?> | Page</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_page.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/property_card.css">
</head>
<body>

<div class="agent-profile-page">

  <!-- Top Section -->
  <div class="agent-top-section">
    
    <!-- Left Column -->
    <div class="host-profile-card">
        <div class="host-info-left">
            <div class="profile-image-container">
                <img src="<?= htmlspecialchars($agent_image) ?>" alt="<?= htmlspecialchars($agent_name) ?>" class="profile-image">
            </div>
            <h2 class="host-name"><?= htmlspecialchars(explode(' ', trim($agent_name))[0]) ?></h2>         
        </div>
        <div class="host-stats-right">            
            <div class="stat-item">
                <div class="stat-value rating-value">
                    <span class="star-icon"><i class="fa-solid fa-star"></i></span> 
                    4.9
                </div>
                <div class="stat-label agent-rating-label">Rating</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">34</div> 
                <div class="stat-label agent-reviews-label">Reviews</div> 
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $years_hosting ?></div> 
                <div class="stat-label">Years hosting</div>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="agent-about">
      <h3>About <?= htmlspecialchars($agent['first_name']) ?></h3>
      <p><?= nl2br(htmlspecialchars($agent['bio'] ?? 'No bio provided.')) ?></p>

      <div class="specialization">
        <strong>Specialization:</strong> <?= htmlspecialchars($agent['specialization'] ?? 'N/A') ?>
      </div>
    </div>

  </div>

  <!-- Reviews Section -->
  <div class="agent-reviews-section">
    <h3>What guests say about <?= htmlspecialchars($agent['first_name']) ?></h3>
    <div class="review-card">
      <p>“<?= htmlspecialchars($agent['first_name']) ?> was extremely helpful and professional!”</p>
      <div class="review-author">— Guest User</div>
    </div>
    <div class="review-card">
      <p>“Very smooth transaction, highly recommended agent.”</p>
      <div class="review-author">— Homebuyer</div>
    </div>
  </div>

  <!-- Listings Section -->
  <div class="properties-grid" id="agentPropertiesGrid">
    <?php if (!empty($properties)): ?>
      <?php
        foreach ($properties as $property):
          render_agent_property_card($property);
        endforeach;
      ?>
    <?php else: ?>
      <p>No properties listed yet.</p>
    <?php endif; ?>
  </div>

</div>

</body>
</html>
