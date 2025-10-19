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
  $userId = $current_user['id'];

  // -------------------------
  // Fetch saved properties
  // -------------------------
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

  // -------------------------
  // Check last application status
  // -------------------------
  $stmt = $conn->prepare("
      SELECT status 
      FROM applications 
      WHERE user_id = ? 
      ORDER BY created_at DESC 
      LIMIT 1
  ");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();
  $lastApplication = $result->fetch_assoc();
  $stmt->close();

  $disableAgentOptions = ($lastApplication && $lastApplication['status'] === 'pending');

  // -------------------------
  // Handle profile update
  // -------------------------
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
      $stmt->bind_param('ssssi', $first_name, $last_name, $phone, $address, $userId);

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

      header("Location: /BatEstateExplorer/public/controllers/user_dashboard.php?view=profile");
      exit;
  }

  // -------------------------
  // Fetch profile image URL
  // -------------------------
  $profileImage = '/BatEstateExplorer/assets/images/default-avatar.png'; // default fallback

  if (!empty($current_user['profile_image_path'])) {
      // Extract the file name from DB path
      $fileName = basename($current_user['profile_image_path']);
      
      // Build public URL
      $possiblePath = '/BatEstateExplorer/storage/uploads/profile_images/' . $fileName;
      
      // Optional: verify file exists on server
      if (file_exists($_SERVER['DOCUMENT_ROOT'] . $possiblePath)) {
          $profileImage = $possiblePath;
      }
  }
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/user_profile.css">

<div class="profile-container">
  <!-- Profile Header -->
  <div class="profile-header">
      <div class="profile-info">

          <!-- Avatar -->
          <div class="profile-avatar">
              <img 
                  src="<?= htmlspecialchars($profileImage) ?>" 
                  alt="<?= htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']) ?>" 
                  class="avatar-img"
              >
          </div>

          <!-- User Details -->
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

      <!-- Profile Actions -->
      <div class="profile-actions">
          <button class="dots-btn" id="profileDotsBtn" title="Options" aria-expanded="false" aria-controls="profileDropdownMenu">
              <i class="fa-solid fa-ellipsis"></i>
          </button>

          <div class="popup-menu" id="profileDropdownMenu">

              <div class="menu-item" id="editProfileBtn" role="button" tabindex="0">
                  <i class="fa-solid fa-user-pen"></i>
                  <span>Edit Profile</span>
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
                  <i class="fa-solid fa-trash"></i>
                  <span>Delete Account</span>
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
        <form id="profileForm" class="profile-edit" method="POST" 
              action="/BatEstateExplorer/public/api/save_profile.php" 
              enctype="multipart/form-data">
              
            <div class="edit-pfp-group">
                <div class="edit-pfp-wrapper">
                    <input 
                    type="file" 
                    id="edit_profile_picture" 
                    name="profile_picture" 
                    accept="image/*"
                    >
                    <input type="hidden" name="remove_picture" id="remove_picture" value="0">
                    <div class="edit-pfp-preview" id="editProfilePicPreview">
                    <?php if (!empty($current_user['profile_image_path'])): ?>
                        <img 
                        src="<?= htmlspecialchars('/BatEstateExplorer/storage/uploads/profile_images/' . basename($current_user['profile_image_path'])) ?>" 
                        alt="Profile Picture"
                        >
                    <?php else: ?>
                        <span class="edit-upload-text">Upload Here</span>
                    <?php endif; ?>
                    </div>

                    <!-- Trash Icon Button -->
                    <button type="button" id="removeProfilePicBtn" class="remove-pfp-btn" title="Remove Picture" style="display: none;">
                    <!-- Inline SVG Trash Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6L17.7 20.4A2 2 0 0 1 15.7 22H8.3A2 2 0 0 1 6.3 20.4L5 6"></path>
                        <path d="M10 11v6"></path>
                        <path d="M14 11v6"></path>
                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                    </svg>
                    </button>
                </div>
            </div>

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
  // Force reload when navigating back (to prevent showing outdated data)
  window.addEventListener('pageshow', function (event) {
    const navType = window.performance?.getEntriesByType("navigation")[0]?.type;
    if (event.persisted || navType === "back_forward") {
      window.location.reload();
    }
  });

  document.addEventListener("DOMContentLoaded", function () {
    // --- Modal Close Buttons: Reload page when closing ---
    const closeButtons = document.querySelectorAll("#modalCloseBtn, #cancelEditBtn, #cancelDeleteBtn");
    closeButtons.forEach(btn => {
      btn.addEventListener("click", () => location.reload());
    });

    // --- Profile Picture Upload and Remove Logic ---
    const profileInput = document.getElementById('edit_profile_picture');
    const profilePreview = document.getElementById('editProfilePicPreview');
    const removeBtn = document.getElementById('removeProfilePicBtn');
    const removeHidden = document.getElementById('remove_picture'); // optional hidden field

    if (profileInput && profilePreview) {
      // Show remove button if an image is already present
      if (profilePreview.querySelector('img') && removeBtn) {
        removeBtn.style.display = 'flex';
      }

      // Open file selector when clicking the preview area
      profilePreview.addEventListener('click', () => profileInput.click());

      // Preview the selected image before uploading
      profileInput.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) {
          profilePreview.innerHTML = '<span class="edit-upload-text">Upload Here</span>';
          if (removeBtn) removeBtn.style.display = 'none';
          if (removeHidden) removeHidden.value = '0';
          return;
        }

        const reader = new FileReader();
        reader.onload = event => {
          profilePreview.innerHTML = `<img src="${event.target.result}" alt="Profile Picture">`;
          if (removeBtn) removeBtn.style.display = 'flex';
          if (removeHidden) removeHidden.value = '0';
        };
        reader.readAsDataURL(file);
      });

      // Handle image removal action
      if (removeBtn) {
        removeBtn.addEventListener('click', e => {
          e.stopPropagation(); // Prevent triggering file input
          profileInput.value = ''; // Clear selected file
          profilePreview.innerHTML = '<span class="edit-upload-text">Upload Here</span>';
          removeBtn.style.display = 'none';
          if (removeHidden) removeHidden.value = '1';
        });
      }
    }
  });
</script>

