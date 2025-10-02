<?php
  require_once __DIR__ . '/../../../../config/database.php';
  require_once __DIR__ . '/../../../../components/user_property_card.php';
  require_once __DIR__ . '/../../../../components/notification.php';

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

  // Check if user has a pending or submitted application
  $stmt = $conn->prepare("SELECT status FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
  $stmt->bind_param("i", $current_user['id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $lastApplication = $result->fetch_assoc();
  $stmt->close();

  $disableAgentOptions = false;
  if ($lastApplication && $lastApplication['status'] === 'pending') {
      $disableAgentOptions = true;
  }

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
          $_SESSION['notification'] = [
              'type' => 'success',
              'message' => 'Profile updated successfully!'
          ];
      } else {
          $_SESSION['notification'] = [
              'type' => 'error',
              'message' => 'Error updating profile.'
          ];
      }
      $stmt->close();

      // Redirect back to profile tab
      header("Location: /BatEstateExplorer/public/controllers/user_dashboard.php?view=profile");
      exit;
  }
?>
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/user_profile.css">


<div class="profile-container">

<!-- Profile Header -->
<div class="profile-header">
  <div class="profile-info">
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
    <button class="dots-btn" id="profileDotsBtn" title="Options" aria-expanded="false" aria-controls="profileDropdownMenu">
      <i class="fa-solid fa-ellipsis"></i>
    </button>

    <div class="popup-menu" id="profileDropdownMenu">
      
      <div class="menu-item" id="editProfileBtn" role="button" tabindex="0">
        <i class="fa-solid fa-user-pen"></i><span>Edit Profile</span>
      </div>
      
      <a href="/BatEstateExplorer/auth/agent_registration.php?type=direct_agent"
        class="menu-item <?= $disableAgentOptions ? 'disabled' : '' ?>" 
        id="becomeDirectAgent"
        aria-disabled="<?= $disableAgentOptions ? 'true' : 'false' ?>">
        <i class="fa-solid fa-user-tie"></i>
        <span>Become Direct Agent</span>
      </a>

      <a href="/BatEstateExplorer/auth/agent_registration.php?type=associate_agent"
        class="menu-item <?= $disableAgentOptions ? 'disabled' : '' ?>" 
        id="becomeAssociateAgent"
        aria-disabled="<?= $disableAgentOptions ? 'true' : 'false' ?>">
        <i class="fa-solid fa-user-plus"></i>
        <span>Become Associate Agent</span>
      </a>

      <div class="menu-item" id="deleteAccount" role="button" tabindex="0">
        <i class="fa-solid fa-trash"></i><span>Delete Account</span>
      </div>
    </div>
  </div>
</div>

  <!-- Saved Properties -->
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

<!-- Edit Profile Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <button class="modal-close" id="modalCloseBtn" aria-label="Close">&times;</button>
        <h4 id="modalTitle">Edit Profile</h4>
        <form id="profileForm" class="profile-edit" method="POST" action="/BatEstateExplorer/public/api/save_profile.php">
            
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" required
                   value="<?= htmlspecialchars($current_user['first_name']) ?>">

            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" required
                   value="<?= htmlspecialchars($current_user['last_name']) ?>">

            <label for="phone">Phone</label>
            <input type="tel" name="phone" id="phone"
                   value="<?= htmlspecialchars($current_user['phone']) ?>">

            <label for="address">Address</label>
            <textarea name="address" id="address" rows="3"><?= htmlspecialchars($current_user['address']) ?></textarea>

            <div class="button-group">
                <button type="submit">Save Changes</button>
                <button type="button" id="cancelEditBtn">Cancel</button>
            </div>
        </form>
    </div>
</div>


<!-- Delete Account Modal -->
<div id="deleteAccountModal" class="modal-agent">
  <div class="modal-content-agent" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <h4 id="deleteModalTitle">Delete Account</h4>
    <p id="deleteModalMessage">Are you sure you want to delete your account? This action cannot be undone.</p>

    <div class="modal-actions-agent" id="deleteModalActions">
      <button id="cancelDeleteBtn" class="cancel-btn-agent">Cancel</button>
      <button id="confirmDeleteBtn" class="delete-btn-agent">Delete</button>
    </div>

    <div class="spinner-agent" id="deleteLoading">
      <span class="loader"></span>
      <span>Deleting account...</span>
    </div>
  </div>
</div>


<!-- Page-specific JS -->
<script src="/BatEstateExplorer/assets/js/user_profile.js"></script>

<script>
window.addEventListener('pageshow', function(event) {
    if (event.persisted || window.performance && window.performance.getEntriesByType("navigation")[0].type === "back_forward") {
        // Force reload from server
        window.location.reload();
    }
});
document.addEventListener("DOMContentLoaded", function () {
  // Select all close buttons from your modals
  const closeButtons = document.querySelectorAll(
    "#modalCloseBtn, #cancelEditBtn, #cancelDeleteBtn"
  );

  // Add event listener to each
  closeButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      location.reload(); // reload page when modal is closed
    });
  });

  // Optional: If modals can be closed by clicking outside, detect that too
  const modals = document.querySelectorAll("#profileModal, #deleteAccountModal");
  modals.forEach((modal) => {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        location.reload();
      }
    });
  });
});
</script>
