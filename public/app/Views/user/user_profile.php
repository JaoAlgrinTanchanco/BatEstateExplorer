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

    <!-- 3-dot menu in profile header -->
    <div class="profile-actions">
      <button class="dots-btn" id="profileDotsBtn" title="Options">
        <i class="fa-solid fa-ellipsis"></i>
      </button>

      <!-- Popup Menu -->
      <div class="popup-menu" id="profileDropdownMenu">
        <div class="menu-item" id="editProfileBtn">
          <i class="fa-solid fa-user-pen"></i><span>Edit Profile</span>
        </div>
        <div class="menu-item" id="becomeDirectAgent">
          <i class="fa-solid fa-user-tie"></i><span>Become Direct Agent</span>
        </div>
        <div class="menu-item" id="becomeAssociateAgent">
          <i class="fa-solid fa-user-plus"></i><span>Become Associate Agent</span>
        </div>
        <div class="menu-item" id="deleteAccount">
          <i class="fa-solid fa-trash"></i><span>Delete Account</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Saved Properties List -->
  <div class="saved-properties-section">
    <h2>Saved Properties</h2>
    <?php if (empty($savedProperties)): ?>
      <p>You have no saved properties yet.</p>
    <?php else: ?>
      <div class="property-grid">
        <?php foreach ($savedProperties as $property): ?>
          <?php render_property_card($property); ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
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
<script src="/BatEstateExplorer/assets/js/property_card_logic.js"></script>
