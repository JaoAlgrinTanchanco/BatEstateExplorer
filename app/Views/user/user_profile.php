<?php

require_once __DIR__ . '/../../../config/database.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}

$current_user = get_logged_in_user($conn);

$message = '';
$error = '';

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($conn, $_POST['first_name']);
    $last_name = sanitize_input($conn, $_POST['last_name']);
    $phone = sanitize_input($conn, $_POST['phone']);
    $address = sanitize_input($conn, $_POST['address']);

    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->bind_param('ssssi', $first_name, $last_name, $phone, $address, $current_user['id']);

    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
        $current_user = get_logged_in_user($conn); // Refresh
    } else {
        $error = "Error updating profile.";
    }
}
?>

<div class="profile-container">
  <div class="profile-header">
    <div class="profile-info">
      <div class="profile-avatar">
        <i class="fa-solid fa-user"></i>
      </div>
      <div class="profile-details">
        <span class="profile-name"><?= htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']) ?></span>
        <span class="profile-email"><?= htmlspecialchars($current_user['email']) ?></span>
        <span class="profile-location"><?= htmlspecialchars($current_user['address'] ?: 'Location not set') ?></span>
      </div>
    </div>
    <div class="profile-actions">
      <button class="dots-btn" id="profileDotsBtn" title="Options">
        <i class="fa-solid fa-ellipsis"></i>
      </button>
      <div class="popup-menu" id="profileDropdownMenu">
        <!-- Other menu items -->
        <div class="menu-item" id="editProfileBtn"><i class="fa-solid fa-user-pen"></i><span>Edit Profile</span></div>
        <!-- ... -->
      </div>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="message-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="message-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Modal for Update Profile -->
  <div id="profileModal" class="modal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <button class="modal-close" id="modalCloseBtn" aria-label="Close">&times;</button>
      <h2 id="modalTitle">Update Profile</h2>
      <form class="profile-update" method="POST" action="">
        <label for="first_name">First Name</label>
        <input type="text" name="first_name" id="first_name" required value="<?= htmlspecialchars($current_user['first_name']) ?>" />

        <label for="last_name">Last Name</label>
        <input type="text" name="last_name" id="last_name" required value="<?= htmlspecialchars($current_user['last_name']) ?>" />

        <label for="phone">Phone Number</label>
        <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($current_user['phone']) ?>" />

        <label for="address">Address</label>
        <textarea name="address" id="address" rows="3"><?= htmlspecialchars($current_user['address']) ?></textarea>

        <button type="submit">Update Profile</button>
      </form>
    </div>
  </div>
</div>


<script>
  const profileDotsBtn = document.getElementById('profileDotsBtn');
  const profileDropdownMenu = document.getElementById('profileDropdownMenu');
  const editProfileBtn = document.getElementById('editProfileBtn');
  const profileModal = document.getElementById('profileModal');
  const modalCloseBtn = document.getElementById('modalCloseBtn');

  profileDotsBtn.addEventListener('click', e => {
    e.stopPropagation();
    profileDropdownMenu.classList.toggle('active');
  });

  document.addEventListener('click', e => {
    if (!profileDropdownMenu.contains(e.target) && e.target !== profileDotsBtn) {
      profileDropdownMenu.classList.remove('active');
    }
  });

  // Open modal on Edit Profile click
  editProfileBtn.addEventListener('click', () => {
    profileDropdownMenu.classList.remove('active');
    profileModal.classList.add('active');
  });

  // Close modal on close button click
  modalCloseBtn.addEventListener('click', () => {
    profileModal.classList.remove('active');
  });

  // Close modal on click outside modal content
  profileModal.addEventListener('click', (e) => {
    if (e.target === profileModal) {
      profileModal.classList.remove('active');
    }
  });

  function logout() {
    if (confirm('Are you sure you want to logout?')) {
      window.location.href = 'logout.php';
    }
  }
</script>
