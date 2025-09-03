<?php
require_once __DIR__ . '/../../../../config/database.php';

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

<div class="profile-container" style="margin-top: 80px;">
  
  <!-- Profile Header -->
  <div class="profile-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div class="profile-info" style="display: flex; align-items: center; gap: 20px;">
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
              <div class="property-card">
                <div class="property-image">
                  <img src="<?= htmlspecialchars($property['image_path'] ?: '/BatEstateExplorer/assets/images/bg4.jpg') ?>" alt="Property Image">
                </div>
                <div class="property-info">
                  <h3><?= htmlspecialchars($property['title']) ?></h3>
                  <p><?= htmlspecialchars($property['location']) ?></p>
                  <p>₱<?= number_format($property['price']) ?></p>
                  <button class="view-details-btn" data-id="<?= $property['id'] ?>">View Details</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Reviews -->
      <div class="tab-content" id="reviews">
        <p>Your reviews will appear here.</p>
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

<script>
  // Tabs
  const tabButtons = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');
  const sortSelect = document.getElementById('sortSelect');

  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      tabButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const tab = btn.dataset.tab;
      tabContents.forEach(tc => tc.classList.toggle('active', tc.id === tab));
      sortSelect.value = 'date'; // reset on switch
    });
  });

  // Sorting stub
  sortSelect.addEventListener('change', () => {
    const currentTab = document.querySelector('.tab-content.active').id;
    const sortBy = sortSelect.value;
    console.log(`Sort ${currentTab} by ${sortBy}`);
  });

  // Profile dropdown
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

  // Modal open/close
  editProfileBtn.addEventListener('click', () => {
    profileDropdownMenu.classList.remove('active');
    profileModal.classList.add('active');
  });

  modalCloseBtn.addEventListener('click', () => {
    profileModal.classList.remove('active');
  });

  profileModal.addEventListener('click', e => {
    if (e.target === profileModal) profileModal.classList.remove('active');
  });

  // Logout
  function logout() {
    if (confirm('Are you sure you want to logout?')) {
      window.location.href = 'logout.php';
    }
  }
</script>
