<?php
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../components/user_property_card.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}

$current_user = get_logged_in_user($conn);

// Fetch saved properties
$userId = $current_user['id'];
$sql = "
    SELECT p.*, pi.image_path, sp.created_at
    FROM saved_properties sp
    INNER JOIN properties p ON sp.property_id = p.id
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE sp.user_id = ?
    ORDER BY sp.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$savedResult = $stmt->get_result();
$savedProperties = $savedResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$message = '';
$error = '';

// Fetch user reviews
$reviewsSql = "
    SELECT r.*, p.title, pi.image_path 
    FROM property_reviews r
    INNER JOIN properties p ON r.property_id = p.id
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
";
$stmt = $conn->prepare($reviewsSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$reviewsResult = $stmt->get_result();
$userReviews = $reviewsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($conn, $_POST['first_name']);
    $last_name  = sanitize_input($conn, $_POST['last_name']);
    $phone      = sanitize_input($conn, $_POST['phone']);
    $address    = sanitize_input($conn, $_POST['address']);

    $stmt = $conn->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, phone = ?, address = ? 
        WHERE id = ?
    ");
    $stmt->bind_param('ssssi', $first_name, $last_name, $phone, $address, $current_user['id']);

    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
        $current_user = get_logged_in_user($conn); // Refresh
    } else {
        $error = "Error updating profile.";
    }
    $stmt->close();
}
?>

<div class="profile-container" style="margin-top: 80px;">
  
  <!-- Profile Header -->
  <div class="profile-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div class="profile-info" style="display: flex; align-items: center; gap: 20px;">
      <div class="profile-avatar">
        <i class="fa-solid fa-user"></i>
      </div>
      <div class="profile-details">
        <span class="profile-name">
          <?= htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']) ?>
        </span>
        <span class="profile-email"><?= htmlspecialchars($current_user['email']) ?></span>
        <span class="profile-location">
          <?= htmlspecialchars($current_user['address'] ?: 'Location not set') ?>
        </span>
      </div>
    </div>

    <div class="profile-actions">
      <button class="dots-btn" id="profileDotsBtn" title="Options">
        <i class="fa-solid fa-ellipsis"></i>
      </button>
      <div class="popup-menu" id="profileDropdownMenu">
        <div class="menu-item" id="editProfileBtn">
          <i class="fa-solid fa-user-pen"></i><span>Edit Profile</span>
        </div>
        <div class="menu-item" onclick="logout()">
          <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs-container">
    <div class="tabs-header">
      <button class="tab-btn active" data-tab="saved-list">Saved List</button>
      <button class="tab-btn" data-tab="reviews">Reviews</button>
    </div>

    <div class="tab-controls">
      <label for="sortSelect">Sort by:</label>
      <select id="sortSelect">
        <option value="date">Date</option>
        <option value="price">Price</option>
      </select>
    </div>

    <div class="tabs-content">
      
      <!-- Saved Properties -->
      <div class="tab-content active" id="saved-list">
        <?php if (empty($savedProperties)): ?>
          <p>You have no saved properties yet.</p>
        <?php else: ?>
          <div class="property-grid">
            <?php foreach ($savedProperties as $property): ?>
              <?php render_property_card($property); ?> <!-- ✅ render modular card -->
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="tab-content" id="reviews">
          <?php if (empty($userReviews)): ?>
              <p>You haven't left any reviews yet.</p>
          <?php else: ?>
              <div class="reviews-grid">
                  <?php foreach ($userReviews as $review): ?>
                      <div class="user-review-card" 
                          data-property-id="<?= (int)$review['property_id'] ?>" 
                          style="
                              display:flex;
                              gap:15px;
                              align-items:flex-start;
                              border:1px solid #eee;
                              border-radius:8px;
                              padding:10px;
                              margin-bottom:10px;
                              background:#f9f9f9;
                              cursor:pointer;
                          ">
                          
                          <!-- Property Thumbnail -->
                          <div class="thumbnail" style="flex-shrink:0;">
                              <img src="<?= htmlspecialchars($review['image_path'] ?: '/BatEstateExplorer/assets/images/bg4.jpg') ?>" 
                                  alt="Property Thumbnail" 
                                  style="width:100px;height:70px;object-fit:cover;border-radius:6px;">
                          </div>
                          
                          <!-- Review Info -->
                          <div class="review-info" style="flex:1;">
                              <strong><?= htmlspecialchars($review['title']) ?></strong>
                              <div class="review-stars" style="color:#f5a623;">
                                  <?= str_repeat('⭐', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']) ?>
                              </div>
                              <p style="margin:5px 0;"><?= htmlspecialchars($review['review_text']) ?></p>
                              <small style="color:#666;"><?= date('M d, Y', strtotime($review['created_at'])) ?></small>
                          </div>
                      </div>
                  <?php endforeach; ?>
              </div>
          <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Flash Messages -->
<?php if ($message): ?>
  <div class="message-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="message-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Profile Update Modal -->
<div id="profileModal" class="modal">
  <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <button class="modal-close" id="modalCloseBtn" aria-label="Close">&times;</button>
    <h2 id="modalTitle">Update Profile</h2>
    <form class="profile-update" method="POST" action="">
      <label for="first_name">First Name</label>
      <input type="text" name="first_name" id="first_name" required 
             value="<?= htmlspecialchars($current_user['first_name']) ?>" />

      <label for="last_name">Last Name</label>
      <input type="text" name="last_name" id="last_name" required 
             value="<?= htmlspecialchars($current_user['last_name']) ?>" />

      <label for="phone">Phone Number</label>
      <input type="tel" name="phone" id="phone" 
             value="<?= htmlspecialchars($current_user['phone']) ?>" />

      <label for="address">Address</label>
      <textarea name="address" id="address" rows="3"><?= htmlspecialchars($current_user['address']) ?></textarea>

      <button type="submit">Update Profile</button>
    </form>
  </div>
</div>

<!-- Page-specific JS -->
<script src="/BatEstateExplorer/assets/js/user_profile.js"></script>
