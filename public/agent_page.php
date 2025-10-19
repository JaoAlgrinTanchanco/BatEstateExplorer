<?php
  session_start();

  // --- Bootstrap & Components ---
  require_once __DIR__ . '/app/bootstrap.php';
  // --- Current User ---
  $current_user_id = $_SESSION['user_id'] ?? null;
  $current_user_role = 'guest'; // default

  // Fetch user role from DB if logged in
  if ($current_user_id) {
      $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
      $stmt->bind_param("i", $current_user_id);
      $stmt->execute();
      $user_row = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if ($user_row && !empty($user_row['user_type'])) {
          $current_user_role = $user_row['user_type']; // 'user', 'direct_agent', 'associate_agent', 'admin'
      }
  }

  // --- Validate & Fetch Agent Info ---
  $agent_user_id = $_GET['agent_id'] ?? null;
  if (!$agent_user_id) die("No agent specified.");

  // Fetch agent info from users table
  $stmt = $conn->prepare("
      SELECT *
      FROM users
      WHERE id = ? AND user_type IN ('direct_agent', 'associate_agent')
      LIMIT 1
  ");
  $stmt->bind_param("i", $agent_user_id);
  $stmt->execute();
  $agent = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$agent) die("Agent not found or invalid.");

  // Fetch agent's internal ID from agents table
  $stmt = $conn->prepare("SELECT id FROM agents WHERE user_id = ? LIMIT 1");
  $stmt->bind_param("i", $agent_user_id);
  $stmt->execute();
  $agent_row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $real_agent_id = $agent_row['id'] ?? 0;
  if ($real_agent_id === 0) {
      echo "<script>console.warn('⚠️ No matching record found in agents table for user_id = $agent_user_id');</script>";
  }

  // --- Prepare Agent Display Info ---
  $agent_name = trim($agent['first_name'] . ' ' . $agent['last_name']);
  $agent_image = !empty($agent['profile_image_path'])
      ? '/BatEstateExplorer/storage/uploads/profile_images/' . basename($agent['profile_image_path'])
      : '/BatEstateExplorer/assets/img/default-user.png';

  // --- Fetch Agent's Property Listings ---
  $properties = [];
  $stmt = $conn->prepare("
      SELECT 
          p.*, 
          s.first_name AS sold_by_first_name, 
          s.last_name AS sold_by_last_name
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
      $row['sold_by'] = !empty($row['sold_by_first_name'])
          ? $row['sold_by_first_name'] . ' ' . $row['sold_by_last_name']
          : null;
      $properties[] = $row;
  }
  $stmt->close();

  // --- Calculate Years Hosting ---
  $created_date = strtotime($agent['created_at']);
  $years_hosting = floor((time() - $created_date) / (365.25 * 24 * 60 * 60));

  // --- Fetch Ratings & Review Count ---
  $avg_rating = 0;
  $review_count = 0;

  $stmt = $conn->prepare("SELECT id FROM properties WHERE agent_id = ?");
  $stmt->bind_param("i", $real_agent_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $property_ids = [];
  while ($row = $result->fetch_assoc()) {
      $property_ids[] = $row['id'];
  }
  $stmt->close();

  if (!empty($property_ids)) {
      $in_placeholders = implode(',', array_fill(0, count($property_ids), '?'));
      $types = str_repeat('i', count($property_ids));
      
      $sql = "
          SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total_reviews
          FROM property_reviews
          WHERE property_id IN ($in_placeholders)
      ";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$property_ids);
      $stmt->execute();
      $result = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      $avg_rating = $result['avg_rating'] ?? 0;
      $review_count = $result['total_reviews'] ?? 0;
  }

  // --- Fetch Education, Address, and Specialization for About Section ---
  $education_text = '';
  $address_text = $agent['address'] ?? '';
  $specialization_text = $agent['specialization'] ?? '';

  if (!empty($agent['education'])) {
      $education_text = $agent['education'];
  } elseif (!empty($agent['school'])) {
      $education_text = trim(
          ($agent['course'] ?? '') .
          ($agent['school'] ? ', ' . $agent['school'] : '') .
          ($agent['graduation_year'] ? ' - ' . $agent['graduation_year'] : '')
      );
  }

  // --- Fetch Reviews for Agent's Properties ---
  $reviews = [];
  if (!empty($property_ids)) {
      $in_placeholders = implode(',', array_fill(0, count($property_ids), '?'));
      $types = str_repeat('i', count($property_ids));

      $sql = "
          SELECT pr.rating, pr.review_text, pr.created_at, u.first_name, u.profile_image_path
          FROM property_reviews pr
          JOIN users u ON pr.user_id = u.id
          WHERE pr.property_id IN ($in_placeholders)
          ORDER BY pr.created_at DESC
      ";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$property_ids);
      $stmt->execute();
      $result = $stmt->get_result();

      while ($row = $result->fetch_assoc()) {
          $row['profile_image'] = !empty($row['profile_image_path'])
              ? '/BatEstateExplorer/storage/uploads/profile_images/' . basename($row['profile_image_path'])
              : '/BatEstateExplorer/assets/img/default-user.png';
          $reviews[] = $row;
      }
      $stmt->close();
  }

  // --- $current_user_role now contains the logged-in user's role ---
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
            
            <h2 class="host-name">
                <?= htmlspecialchars(explode(' ', trim($agent_name))[0]) ?>
            </h2>
            
            <div class="agent-type">
                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($agent['user_type'] ?? 'Agent'))) ?>
            </div>
        </div>

        <div class="host-stats-right">            
            <div class="stat-item">
                <div class="stat-value rating-value">
                    <?= $avg_rating > 0 ? htmlspecialchars($avg_rating) : 0 ?>
                    <span class="star-icon"><i class="fa-solid fa-star"></i></span> 
                </div>
                <div class="stat-label agent-rating-label">Rating</div>
            </div>

            <div class="stat-item">
                <div class="stat-value">
                    <?= htmlspecialchars($review_count) ?>
                </div> 
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

        <div class="agent-details">
            <!-- Education -->
            <?php if(!empty($agent['education'])): ?>
                <div class="agent-detail-item">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <?= htmlspecialchars($agent['education']) ?>
                </div>
            <?php elseif(!empty($agent['school'])): ?>
                <div class="agent-detail-item">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <?= htmlspecialchars(trim(
                        ($agent['course'] ?? '') .
                        ($agent['school'] ? ', ' . $agent['school'] : '') .
                        ($agent['graduation_year'] ? ' - ' . $agent['graduation_year'] : '')
                    )) ?>
                </div>
            <?php endif; ?>

            <!-- Address -->
            <?php if(!empty($agent['address'])): ?>
                <div class="agent-detail-item">
                    <i class="fa-solid fa-globe"></i>
                    <?= htmlspecialchars($agent['address']) ?>
                </div>
            <?php endif; ?>

            <!-- Specialization -->
            <?php 
            $specializations = [];
            if(!empty($agent['specialization'])) {
                $decoded = json_decode($agent['specialization'], true);
                if(is_array($decoded)) {
                    $specializations = $decoded;
                } else {
                    $specializations = [$agent['specialization']];
                }
            }
            ?>
            <?php if(!empty($specializations)): ?>
                <div class="agent-detail-item">
                    <i class="fa-solid fa-briefcase"></i>
                    <?= htmlspecialchars(implode(', ', $specializations)) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

  </div>

  <!-- Reviews Section -->
  <div class="agent-reviews-section">
    <h3>What guests say about <?= htmlspecialchars($agent['first_name']) ?></h3>

    <div class="reviews-wrapper">
      <button class="scroll-btn left-btn">&#10094;</button>
      
      <div class="reviews-row">
        <?php if (!empty($reviews)): ?>
          <?php foreach ($reviews as $review): ?>
            <div class="review-card">
              <div class="review-header">
                <img src="<?= htmlspecialchars($review['profile_image']) ?>" 
                    alt="<?= htmlspecialchars($review['first_name']) ?>" 
                    class="review-avatar">
                <div>
                  <div class="reviewer-name-rating">
                    <?= htmlspecialchars($review['first_name']) ?>
                    <span class="review-stars">
                      <?php for ($i=1; $i<=5; $i++): ?>
                        <i class="fa-solid fa-star" style="color:<?= $i <= $review['rating'] ? '#111' : '#ddd' ?>;"></i>
                      <?php endfor; ?>
                    </span>
                  </div>
                  <div class="review-date">
                    <?= date('F j, Y', strtotime($review['created_at'])) ?>
                  </div>
                </div>
              </div>
              <p class="review-text">“<?= htmlspecialchars($review['review_text']) ?>”</p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No reviews yet.</p>
        <?php endif; ?>
      </div>

      <button class="scroll-btn right-btn">&#10095;</button>
    </div>
  </div>

  <!-- Listings Section -->
  <h3 style="font-size: 28px; margin: 0;">Property Listings <?= htmlspecialchars($agent['first_name']) ?></h3>
  <div class="properties-grid" id="userPropertiesGrid">
      <?php if (!empty($properties)): ?>
          <?php
          // Determine which render function to use based on current user's role
          // 'direct_agent' or 'associate_agent' can use agent_property_card
          // other users use user_property_card
          if (in_array($current_user_role, ['direct_agent', 'associate_agent'])) {
              require_once __DIR__ . '/../components/agent_property_card.php';
              $renderCardFunction = 'render_agent_property_card';
          } else {
              require_once __DIR__ . '/../components/user_property_card.php';
              $renderCardFunction = 'render_property_card';
          }

          foreach ($properties as $property) {
              $renderCardFunction($property);
          }
          ?>
      <?php else: ?>
          <p>No properties listed yet.</p>
      <?php endif; ?>
  </div>

</div>

<script>
  const leftBtn = document.querySelector('.left-btn');
  const rightBtn = document.querySelector('.right-btn');
  const row = document.querySelector('.reviews-row');

  leftBtn.addEventListener('click', () => {
    row.scrollBy({ left: -320, behavior: 'smooth' });
  });

  rightBtn.addEventListener('click', () => {
    row.scrollBy({ left: 320, behavior: 'smooth' });
  });
</script>

</body>
</html>
