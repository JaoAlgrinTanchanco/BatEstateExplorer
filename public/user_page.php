<?php
  session_start();

  // --- Bootstrap & Notification Component ---
  require_once __DIR__ . '/app/bootstrap.php';
  include __DIR__ . '/../components/notification.php';

  // --- Get Current Logged-in User ---
  $current_user_id   = $_SESSION['user_id'] ?? null;
  $current_user_role = 'user';

  if ($current_user_id) {
      $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
      $stmt->bind_param("i", $current_user_id);
      $stmt->execute();
      $user_row = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if (!empty($user_row['user_type'])) {
          $current_user_role = $user_row['user_type'];
      }
  }

  // --- Validate Profile User ID ---
  $profile_user_id = $_GET['user_id'] ?? null;
  if (!$profile_user_id) {
      die("User not found or invalid.");
  }

  // --- Fetch Profile User ---
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
  $stmt->bind_param("i", $profile_user_id);
  $stmt->execute();
  $profile_user = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$profile_user) {
      die("User not found.");
  }

  // --- Profile Display Info ---
  $profile_name = trim($profile_user['first_name'] . ' ' . $profile_user['last_name']);
  $default_avatar = '/BatEstateExplorer/assets/images/default-avatar.png';
  $profile_image = $default_avatar;
  if (!empty($profile_user['profile_image_path'])) {
      $image_path = __DIR__ . '/../storage/uploads/profile_images/' . basename($profile_user['profile_image_path']);
      if (file_exists($image_path)) {
          $profile_image = '/BatEstateExplorer/storage/uploads/profile_images/' . basename($profile_user['profile_image_path']);
      }
  }

  // --- Hardcoded Profile Type ---
  $profile_type_text = 'User';

  // --- Account Join Date ---
  $joined_text = !empty($profile_user['created_at'])
      ? 'Joined ' . date('F j, Y', strtotime($profile_user['created_at']))
      : 'Joined date unknown';

  // --- Calculate Months Hosting ---
  $created_at = new DateTime($profile_user['created_at'] ?? 'now');
  $now = new DateTime();
  $months_hosting = $created_at->diff($now)->m + ($created_at->diff($now)->y * 12);

  // --- Fetch Reviews and Unique Trips ---
  $stmt = $conn->prepare("
      SELECT COUNT(*) AS total_reviews,
            COUNT(DISTINCT property_id) AS trips
      FROM property_reviews
      WHERE user_id = ?
  ");
  $stmt->bind_param("i", $profile_user_id);
  $stmt->execute();
  $review_stats = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  $review_count = (int) ($review_stats['total_reviews'] ?? 0);
  $trips = (int) ($review_stats['trips'] ?? 0);

  // --- Education Summary ---
  $education_parts = array_filter([
      $profile_user['education'] ?? '',
      $profile_user['course'] ?? '',
      $profile_user['school'] ?? '',
      $profile_user['graduation_year'] ?? ''
  ]);
  $education_text = implode(', ', $education_parts);

  // --- Fetch Reviews Made By This User ---
  $reviews = [];
  $stmt = $conn->prepare("
      SELECT 
          r.id AS review_id,
          r.rating,
          r.review_text,
          r.created_at,
          p.id AS property_id,
          p.title AS property_title,
          u.first_name,
          u.profile_image_path
      FROM property_reviews r
      INNER JOIN properties p ON r.property_id = p.id
      INNER JOIN users u ON r.user_id = u.id
      WHERE r.user_id = ?
      ORDER BY r.created_at DESC
  ");
  $stmt->bind_param("i", $profile_user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
      // Set default avatar if none
      $row['profile_image'] = !empty($row['profile_image_path']) && file_exists(__DIR__ . '/../storage/uploads/profile_images/' . basename($row['profile_image_path']))
          ? '/BatEstateExplorer/storage/uploads/profile_images/' . basename($row['profile_image_path'])
          : '/BatEstateExplorer/assets/images/default-avatar.png';

      $reviews[] = $row;
  }

  $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($profile_name) ?> | Page</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/user_page.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/property_card.css">
<style>
  /* =========================
    Navbar
  ========================= */
  .navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    padding: 0.8rem 1%;
    background: #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    font-family: 'Satoshi', 'Inter', sans-serif;
    box-sizing: border-box;
    overflow-x: hidden;
  }

  .nav-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    margin: 0 auto;
    gap: 1rem;
  }

  /* Left: Logo + Role */
  .nav-logo {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 600;
    font-size: 1.1rem;
    color: #111;
  }
  .nav-logo-img {
    width: 34px;
    height: auto;
  }
  .agent-role {
    font-size: 0.85rem;
    font-weight: 500;
    color: #666;
  }

  /* Center Links (Desktop) */
  .nav-center {
    flex: 1;
    display: flex;
    justify-content: center;
    gap: 3rem;
  }
  .nav-center .nav-link {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
    color: #444;
    font-weight: 500;
    font-size: 0.95rem;
    transition: color 0.3s ease, transform 0.2s ease;
  }
  .nav-center .nav-link:hover {
    color: #111;
    transform: translateY(-2px);
  }
  .nav-center .nav-link.active {
    color: #111;
    font-weight: 600;
    border-bottom: 2px solid #111;
    padding-bottom: 2px;
  }

  /* Right: Messages + Logout (Always Visible) */
  .nav-right {
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  .nav-right .glow-link,
  .nav-right .logout-btn {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background: transparent;
    border: none;
    font-weight: 500;
    font-size: 0.95rem;
    color: #111;
    cursor: pointer;
    padding: 0.4rem 0.6rem;
    border-radius: 8px;
    transition: color 0.3s ease, transform 0.2s ease, background 0.2s ease;
  }
  .nav-right .glow-link:hover {
    color: #111;
    transform: translateY(-2px);
  }
  .nav-right .logout-btn:hover {
    color: #c0392b;
    transform: translateY(-2px);
  }

  /* ---------------- Responsive: Hide Center Links on Mobile ---------------- */
  @media (max-width: 992px) {
    .nav-center {
      display: none;
    }
  }

  /* =========================
    Footer
  ========================= */
  .footer {
    font-family: "Inter", Arial, sans-serif;
    background: #111;
    color: #fff;
    padding: 3rem 20% 1rem;
    box-sizing: border-box;
  }

  .footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    margin-bottom: 2rem;
    justify-items: center;
  }

  .footer h3,
  .footer h4 {
    margin-bottom: 1rem;
    font-weight: 600;
    color: #fff;
  }

  .footer h3 { font-size: 18px; }
  .footer h4 { font-size: 16px; }

  .footer a {
    font-size: 0.9rem;
    color: #fff;
    text-decoration: none;
  }

  .footer a:hover { color: #999; }

  .footer-bottom {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #333;
    font-size: 0.85rem;
    color: #999;
  }

  /* ---------------- Mobile / Tablet Footer ---------------- */
  @media (max-width: 768px) {
    .footer {
      padding: 2rem 4%;
    }
    .footer-content {
      display: block;
      gap: 1rem;
    }
    .footer h3 { font-size: 15px; }
    .footer h4 { font-size: 14px; }
    .footer a { font-size: 0.8rem; }
    .footer-bottom { font-size: 0.8rem; padding-top: 0.8rem; }
  }
</style>
</head>
<body>

<nav class="navbar">
  <div class="nav-container">
    <!-- Left -->
    <div class="nav-logo">
      <img src="/BatEstateExplorer/assets/images/vector 1.png" alt="BatEstate Explorer Logo" class="nav-logo-img">
      <span>BatEstate Explorer</span>
      <?php if (in_array($current_user_role, ['direct_agent','associate_agent'])): ?>
        <div class="agent-role">(<?= $current_user_role === 'direct_agent' ? 'Direct' : 'Associate' ?>)</div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="agent-profile-page">
  <div class="agent-top-section">
    <!-- Left Column -->
    <div class="host-profile-card">
      <div class="host-info-left">
        <div class="profile-image-container">
            <img src="<?= htmlspecialchars($profile_image) ?>" alt="<?= htmlspecialchars($profile_name) ?>" class="profile-image">
        </div>
        
        <h2 class="host-name">
            <?= htmlspecialchars(explode(' ', trim($profile_name))[0]) ?>
        </h2>
        
        <div class="agent-type">
            <?= $profile_type_text ?>
        </div>
      </div>

      <div class="host-stats-right">
          <div class="stat-item">
              <div class="stat-value"><?= htmlspecialchars($trips) ?></div>
              <div class="stat-label">Trips</div>
          </div>

          <div class="stat-item">
              <div class="stat-value"><?= htmlspecialchars($review_count) ?></div>
              <div class="stat-label">Reviews</div>
          </div>

          <div class="stat-item">
              <div class="stat-value"><?= htmlspecialchars($months_hosting) ?></div>
              <div class="stat-label">Months Joined</div>
          </div>
      </div>
    </div>
    <!-- Right Column -->
    <div class="agent-about">
        <h3>About <?= htmlspecialchars($profile_user['first_name']) ?></h3>

        <div class="agent-details">

            <!-- Address -->
            <?php if (!empty($profile_user['address'])): ?>
                <div class="agent-detail-item">
                    <i class="fa-solid fa-globe"></i>
                    <?= htmlspecialchars($profile_user['address']) ?>
                </div>
            <?php endif; ?>

            <!-- Email -->
            <?php if (!empty($profile_user['email'])): ?>
                <div class="agent-detail-item">
                    <i class="fa-solid fa-envelope"></i>
                    <?= htmlspecialchars($profile_user['email']) ?>
                </div>
            <?php endif; ?>

            <!-- Contact Number -->
            <?php if (!empty($profile_user['phone'])): ?>
                <div class="agent-detail-item">
                    <i class="fa-solid fa-phone"></i>
                    <?= htmlspecialchars($profile_user['phone']) ?>
                </div>
            <?php endif; ?>

            <!-- Join Date -->
            <?php if (!empty($profile_user['created_at'])): ?>
                <div class="agent-detail-item">
                    <i class="fa-solid fa-calendar"></i>
                    Joined on <?= date('F j, Y', strtotime($profile_user['created_at'])) ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
  </div>

  <!-- Reviews Section -->
  <div class="agent-reviews-section">
    <h3>Reviews by <?= htmlspecialchars($profile_user['first_name']) ?></h3>

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

  <a href="#" class="nav-link report-user">
      <i class="fa-solid fa-flag"></i>
      <span>Report User</span>
  </a>

</div>

<!-- ===== Report User Modal ===== -->
<div id="reportUserModal" class="report-modal">
  <div class="report-modal-content">
    <span class="close">&times;</span>
    <h2>Report <?= htmlspecialchars($profile_user['first_name']) ?></h2>
    <form id="reportUserForm" method="POST" action="/BatEstateExplorer/public/api/report_user.php" enctype="multipart/form-data">
      <!-- Hidden field: match API -->
      <input type="hidden" name="reported_user_id" value="<?= $profile_user_id ?>">

      <!-- Reason dropdown -->
      <label for="report-reason">Reason</label>
      <select name="reason" id="report-reason" required>
        <option value="">Select a reason</option>
        <option value="harassment">Harassment or inappropriate behavior</option>
        <option value="spam">Spam or irrelevant contact</option>
        <option value="spam review">Spam reviews</option>
        <option value="fraudulent activity">Fraudulent activity</option>
        <option value="misinformation">False or misleading information</option>
        <option value="other">Other</option>
      </select>

      <!-- Other text input -->
      <div id="otherReasonContainer" style="display: none; margin-top: 0.5rem;">
        <input type="text" name="other_reason" id="other-reason" placeholder="Please specify your reason...">
      </div>

      <!-- Details textarea -->
      <label for="details">Additional Details</label>
      <textarea name="details" id="details" rows="4" placeholder="Describe what happened..."></textarea>

      <button type="submit" class="btn-report">Submit Report</button>
    </form>
  </div>
</div>

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
      <p>&copy; 2025 BatEstate Explorer. All rights reserved.</p>
    </div>
  </div>
</footer>

<script>
  document.addEventListener('DOMContentLoaded', () => {

    /* ================================
      Reviews Row Scroll Buttons
    ================================ */
    const reviewsRow = document.querySelector('.reviews-row');
    document.querySelector('.left-btn')?.addEventListener('click', () => {
      reviewsRow?.scrollBy({ left: -320, behavior: 'smooth' });
    });
    document.querySelector('.right-btn')?.addEventListener('click', () => {
      reviewsRow?.scrollBy({ left: 320, behavior: 'smooth' });
    });

    /* ================================
      Logout Modal
    ================================ */
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal) {
      document.querySelectorAll('.logout-btn').forEach(btn => {
        btn.addEventListener('click', () => logoutModal.classList.add('active-agent'));
      });
      document.getElementById('cancelLogoutBtn')?.addEventListener('click', () =>
        logoutModal.classList.remove('active-agent')
      );
      document.getElementById('logoutForm')?.addEventListener('submit', () => {
        document.getElementById('logoutSpinner').style.display = 'flex';
      });
    }

    /* ================================
      Report User Modal
    ================================ */
    const reportModal = document.getElementById('reportUserModal');
    const reportLink = document.querySelector('.report-user');
    const reportForm = document.getElementById('reportUserForm');
    const reasonSelect = document.getElementById('report-reason');
    const otherContainer = document.getElementById('otherReasonContainer');
    const otherInput = document.getElementById('other-reason');

    // --- Notification helper ---
    const showNotification = (type, message) => {
      const notif = document.createElement('div');
      notif.className = 'notification-container';
      notif.innerHTML = `
        <div class="notification ${type}">
          <div class="notification__icon"></div>
          <div class="notification__title">${message}</div>
          <div class="notification__close">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
              <path d="m15.8333 5.34166-1.175-1.175-4.6583 4.65834-4.65833-4.65834-1.175 1.175 4.65833 4.65834-4.65833 4.6583 1.175 1.175 4.65833-4.6583 4.6583 4.6583 1.175-1.175-4.6583-4.6583z"/>
            </svg>
          </div>
        </div>`;
      document.body.appendChild(notif);

      const notifBox = notif.querySelector('.notification');
      const closeBtn = notif.querySelector('.notification__close');
      if (closeBtn) closeBtn.addEventListener('click', () => fadeOut(notifBox));
      setTimeout(() => fadeOut(notifBox), 5000);

      function fadeOut(elem) {
        elem.classList.add('fade-out');
        setTimeout(() => elem.remove(), 500);
      }
    };

    // --- Open/close modal ---
    reportLink?.addEventListener('click', e => {
      e.preventDefault();
      reportModal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    });

    const closeReportModal = () => {
      reportModal.style.display = 'none';
      document.body.style.overflow = 'auto';
      reportForm?.reset();
      if (otherContainer) {
        otherContainer.style.display = 'none';
        if (otherInput) otherInput.required = false;
      }
    };

    reportModal?.addEventListener('click', e => {
      if (e.target.classList.contains('report-modal') || e.target.classList.contains('close')) {
        closeReportModal();
      }
    });

    // --- Toggle "Other" field ---
    reasonSelect?.addEventListener('change', () => {
      const showOther = reasonSelect.value === 'other';
      otherContainer.style.display = showOther ? 'block' : 'none';
      otherInput.required = showOther;
      if (showOther) otherInput.focus();
    });

    // --- Submit report form via AJAX ---
    reportForm?.addEventListener('submit', async e => {
      e.preventDefault();
      const formData = new FormData(reportForm);

      try {
        const res = await fetch('/BatEstateExplorer/public/api/report_user.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data.status === 'success') {
          showNotification('success', data.message || 'Report submitted successfully.');
          closeReportModal();
        } else {
          showNotification('error', data.message || 'Failed to submit report.');
        }
      } catch (err) {
        console.error('Error submitting report:', err);
        showNotification('error', 'Something went wrong. Please try again later.');
      }
    });

  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
