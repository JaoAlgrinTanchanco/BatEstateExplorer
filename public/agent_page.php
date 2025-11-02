<?php
  session_start();

  // --- Bootstrap & Notification Component ---
  require_once __DIR__ . '/app/bootstrap.php';
  include __DIR__ . '/../components/notification.php';

  // --- Get Current Logged-in User ---
  $current_user_id   = $_SESSION['user_id'] ?? null;
  $current_user_role = 'guest';

  if ($current_user_id) {
      $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
      $stmt->bind_param("i", $current_user_id);
      $stmt->execute();
      $user_row = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if (!empty($user_row['user_type'])) $current_user_role = $user_row['user_type'];
  }

  // --- Validate Agent ID and Map to User ID ---
  $agent_id = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : 0;
  if ($agent_id <= 0) die("No agent specified.");

  // --- Fetch user_id from agents table ---
  $stmt = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
  $stmt->bind_param("i", $agent_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $agent_row = $res->fetch_assoc();
  $stmt->close();

  if (!$agent_row) die("Agent not found.");

  // Use the mapped user_id for all subsequent queries
  $agent_user_id = (int)$agent_row['user_id'];
  

  // --- Fetch Agent Basic Info ---
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

  // --- Agent Display Info ---
  $agent_name     = trim($agent['first_name'] . ' ' . $agent['last_name']);
  $default_avatar = '/BatEstateExplorer/assets/img/default-user.png';
  $agent_image    = $default_avatar;

  if (!empty($agent['profile_image_path'])) {
      $image_path = __DIR__ . '/../storage/uploads/profile_images/' . basename($agent['profile_image_path']);
      if (file_exists($image_path)) $agent_image = '/BatEstateExplorer/storage/uploads/profile_images/' . basename($agent['profile_image_path']);
  }

  // --- Fetch Agent's Properties (available or ongoing_inquiry) ---
  $properties = [];
  $stmt = $conn->prepare("
      SELECT * FROM properties 
      WHERE listed_by_agent_id = ? AND status IN ('available','ongoing_inquiry','sold')
      ORDER BY created_at DESC
  ");
  // FIX: Using listed_by_agent_id to correctly show only properties originally 
  // posted by the agent, based on the $agent_id (agents.id) from the URL.
  $stmt->bind_param("i", $agent_id); 
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
      $row['images'] = !empty($row['images']) ? json_decode($row['images'], true) : [];
      $properties[] = $row;
  }
  $stmt->close();

  // --- Calculate Years Hosting ---
  $created_date  = strtotime($agent['created_at']);
  $years_hosting = floor((time() - $created_date) / (365.25 * 24 * 60 * 60));

  // --- Fetch Property IDs for Reviews / Stats ---
  $property_ids = [];
  $stmt = $conn->prepare("SELECT id FROM properties WHERE agent_id = ?");
  $stmt->bind_param("i", $agent_id); // <-- agent_id here too
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $property_ids[] = $r['id'];
  $stmt->close();

  // --- Average Rating and Review Count ---
  $avg_rating   = 0;
  $review_count = 0;

  if (!empty($property_ids)) {
      $placeholders = implode(',', array_fill(0, count($property_ids), '?'));
      $types        = str_repeat('i', count($property_ids));

      $sql  = "SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total_reviews 
              FROM property_reviews 
              WHERE property_id IN ($placeholders)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$property_ids);
      $stmt->execute();
      $res = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      $avg_rating   = $res['avg_rating'] ?? 0;
      $review_count = $res['total_reviews'] ?? 0;
  }

  // --- About Section Info ---
  $address_text        = $agent['address'] ?? '';
  $specialization_text = $agent['specialization'] ?? '';

  // --- Education Summary ---
  $education_parts = array_filter([
      $agent['education'] ?? '',
      $agent['course'] ?? '',
      $agent['school'] ?? '',
      $agent['graduation_year'] ?? ''
  ]);
  $education_text = implode(', ', $education_parts);

  // --- Fetch Property Reviews ---
  $reviews = [];
  if (!empty($property_ids)) {
      $placeholders = implode(',', array_fill(0, count($property_ids), '?'));
      $types        = str_repeat('i', count($property_ids));

      $sql  = "
          SELECT pr.rating, pr.review_text, pr.created_at, u.first_name, u.profile_image_path
          FROM property_reviews pr
          JOIN users u ON pr.user_id = u.id
          WHERE pr.property_id IN ($placeholders)
          ORDER BY pr.created_at DESC
      ";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$property_ids);
      $stmt->execute();
      $res = $stmt->get_result();

      while ($row = $res->fetch_assoc()) {
          $row['profile_image'] = !empty($row['profile_image_path'])
              ? '/BatEstateExplorer/storage/uploads/profile_images/' . basename($row['profile_image_path'])
              : '/BatEstateExplorer/assets/img/default-avatar.png';
          $reviews[] = $row;
      }
      $stmt->close();
  }

?>

<!-- <script>
  console.group("Agent Page Debug");

  console.log("Agent Info:", <?php echo json_encode($agent ?? null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
  console.log("Agent Properties:", <?php echo json_encode($properties ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
  console.log("Agent Property IDs:", <?php echo json_encode($property_ids ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
  console.log("Agent Ratings:", { avg_rating: <?php echo $avg_rating ?? 0; ?>, review_count: <?php echo $review_count ?? 0; ?> });
  console.log("Agent Property Reviews:", <?php echo json_encode($reviews ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);

  console.groupEnd();
</script> -->

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($agent_name) ?> | Page</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_page.css">
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
            <?php if (!empty($education_text)): ?>
                <div class="agent-detail-item">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <?= htmlspecialchars($education_text) ?>
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
  <h3 style="font-size: 28px; margin: 0;">Property Listings of <?= htmlspecialchars($agent['first_name']) ?></h3>
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

  <a href="#" class="nav-link report-agent">
    <i class="fa-solid fa-flag"></i>
    <span>Report Agent</span>
  </a>

</div>

<!-- ===== Report Agent Modal ===== -->
<div id="reportAgentModal" class="report-modal">
  <div class="report-modal-content">
    <span class="close">&times;</span>
    <h2>Report this Agent</h2>

    <form id="reportAgentForm" method="POST" action="/BatEstateExplorer/public/api/report_agent.php" enctype="multipart/form-data">
      <!-- Hidden fields -->
      <input type="hidden" name="agent_id" value="<?= $agent_user_id ?>">
      <input type="hidden" name="reported_by" value="<?= $current_user_id ?>">

      <!-- Reason dropdown -->
      <label for="report-reason">Reason</label>
      <select name="reason" id="report-reason" required>
        <option value="">Select a reason</option>
        <option value="fraudulent_listing">Fraudulent or fake listing</option>
        <option value="harassment">Harassment or inappropriate behavior</option>
        <option value="misinformation">False or misleading information</option>
        <option value="spam">Spam or irrelevant contact</option>
        <option value="other">Other</option>
      </select>

      <!-- Other text input -->
      <div id="otherReasonContainer" style="display: none; margin-top: 0.5rem;">
        <input 
          type="text" 
          name="other_reason" 
          id="other-reason" 
          placeholder="Please specify your reason..."
        >
      </div>

      <!-- Details textarea -->
      <label for="details">Additional Details</label>
      <textarea 
        name="details" 
        id="details" 
        rows="4" 
        placeholder="Describe what happened..."
      ></textarea>

      <!-- Submit -->
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
    const row = document.querySelector('.reviews-row');
    document.querySelector('.left-btn')?.addEventListener('click', () =>
      row?.scrollBy({ left: -320, behavior: 'smooth' })
    );
    document.querySelector('.right-btn')?.addEventListener('click', () =>
      row?.scrollBy({ left: 320, behavior: 'smooth' })
    );

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
      Report Agent Modal
    ================================ */
    const reportLinks = document.querySelectorAll('.report-agent');
    const reportModal = document.getElementById('reportAgentModal');
    const reportForm = document.getElementById('reportAgentForm');
    const reasonSelect = document.getElementById('report-reason');
    const otherContainer = document.getElementById('otherReasonContainer');
    const otherInput = document.getElementById('other-reason');

    if (!reportLinks.length || !reportModal || !reportForm) return;

    // Helper: show notification
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
      closeBtn?.addEventListener('click', () => fadeOutNotif(notifBox));

      setTimeout(() => fadeOutNotif(notifBox), 5000);

      function fadeOutNotif(elem) {
        elem.classList.add('fade-out');
        setTimeout(() => elem.remove(), 500);
      }
    };

    // Open Modal
    reportLinks.forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        const agentId = link.dataset.agentId || reportForm.querySelector('input[name="agent_id"]').value;
        if (!agentId) return console.error('Agent ID not found.');
        reportForm.querySelector('input[name="agent_id"]').value = agentId;

        reportModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      });
    });

    // Close Modal
    const closeReportModal = () => {
      reportModal.style.display = 'none';
      document.body.style.overflow = '';
      reportForm.reset();
      if (otherContainer) {
        otherContainer.style.display = 'none';
        if (otherInput) otherInput.required = false;
      }
    };

    // Close on outside click or close button
    reportModal.addEventListener('click', e => {
      if (e.target === reportModal || e.target.classList.contains('close')) {
        closeReportModal();
      }
    });

    // Toggle "Other" reason field
    reasonSelect?.addEventListener('change', () => {
      const isOther = reasonSelect.value === 'other';
      if (otherContainer) otherContainer.style.display = isOther ? 'block' : 'none';
      if (otherInput) otherInput.required = isOther;
      if (isOther) otherInput.focus();
    });

    // Submit Report Form (AJAX)
    reportForm.addEventListener('submit', async e => {
      e.preventDefault();
      const formData = new FormData(reportForm);

      try {
        const res = await fetch('/BatEstateExplorer/public/api/report_agent.php', {
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
