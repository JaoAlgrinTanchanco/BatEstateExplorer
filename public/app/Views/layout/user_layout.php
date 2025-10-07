<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/database/cleanup_accounts.php';

if (!isset($page_title)) $page_title = "User Dashboard";
$user = $_SESSION['user'] ?? null; // Logged-in user
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/agent_layout.css">
  <link rel="stylesheet" href="../../assets/css/property_card.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="nav-container">
    <!-- Logo / Left -->
    <div class="nav-logo">
      <img src="/BatEstateExplorer/assets/images/vector 1.png" alt="BatEstate Explorer Logo" class="nav-logo-img">
      <span>BatEstate Explorer</span>
    </div>

    <!-- Mobile burger -->
    <button class="nav-burger" aria-label="Toggle Menu">
      <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Mobile dropdown links -->
    <div class="nav-dropdown">
      <div class="nav-links-mobile">
        <a href="user_dashboard.php?view=home" class="nav-link <?= ($view === 'home') ? 'active' : '' ?>"><i class="fa-solid fa-house"></i> Home</a>
        <a href="user_dashboard.php?view=profile" class="nav-link <?= ($view === 'profile') ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> Profile</a>
        <a href="user_dashboard.php?view=search" class="nav-link <?= ($view === 'search') ? 'active' : '' ?>"><i class="fa-solid fa-search"></i> Search</a>
        <a href="/BatEstateExplorer/public/message.php" class="nav-link" target="_blank"><i class="fa-solid fa-envelope"></i> Messages</a>
        <button type="button" class="nav-link logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

    <!-- Center links -->
    <div class="nav-center">
      <a href="user_dashboard.php?view=home" class="nav-link <?= ($view === 'home') ? 'active' : '' ?>"><i class="fa-solid fa-house"></i> Home</a>
      <a href="user_dashboard.php?view=profile" class="nav-link <?= ($view === 'profile') ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> Profile</a>
      <a href="user_dashboard.php?view=search" class="nav-link <?= ($view === 'search') ? 'active' : '' ?>"><i class="fa-solid fa-search"></i> Search</a>
    </div>

    <!-- Right -->
    <div class="nav-right">
      <a href="/BatEstateExplorer/public/message.php" target="_blank" class="glow-link"><i class="fa-solid fa-envelope"></i> Messages</a>
      <button type="button" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
    </div>
  </div>
</nav>

<main class="dashboard-content">
  <?= $content ?? '' ?>
</main>

<!-- Logout Modal -->
<div id="logoutModal" class="modal-agent">
  <div class="modal-content-agent">
    <h4>Confirm Logout</h4>
    <p>Are you sure you want to log out?</p>
    <form id="logoutForm" method="POST" action="../../auth/logout.php">
      <div class="modal-actions-agent">
        <button type="submit" class="delete-btn-agent">Yes, Logout</button>
        <button type="button" id="cancelLogoutBtn" class="cancel-btn-agent">Cancel</button>
      </div>
      <div id="logoutSpinner" class="spinner-agent">
        <div class="loader"></div>
        <span>Logging out...</span>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const logoutModal = document.getElementById('logoutModal');
  const cancelBtn = document.getElementById('cancelLogoutBtn');
  const logoutForm = document.getElementById('logoutForm');
  const spinner = document.getElementById('logoutSpinner');

  document.querySelectorAll('.logout-btn').forEach(btn => {
    btn.addEventListener('click', () => logoutModal.classList.add('active-agent'));
  });

  cancelBtn.addEventListener('click', () => logoutModal.classList.remove('active-agent'));

  logoutForm.addEventListener('submit', () => spinner.style.display = 'flex');
});
</script>

<script src="/BatEstateExplorer/assets/js/agents.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Footer -->
<footer class="footer scroll-animation">
  <div class="container scroll-animation">
    <div class="footer-content scroll-animation">
      <div class="footer-section scroll-animation">
        <h3>BatEstate Explorer</h3>
        <p>Your trusted partner in finding the perfect property.</p>
      </div>
      <div class="footer-section scroll-animation">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="#home">Home</a></li>
          <li><a href="#properties">Properties</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </div>
      <div class="footer-section scroll-animation">
        <h4>Contact Info</h4>
        <p><i class="fas fa-envelope"></i> info@batestate.com</p>
        <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
      </div>
    </div>
    <div class="footer-bottom scroll-animation">
      <p>&copy; <?= date('Y') ?> BatEstate Explorer. All rights reserved.</p>
    </div>
  </div>
</footer>

</body>
</html>
