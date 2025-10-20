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
  $address_text = $agent['address'] ?? '';
  $specialization_text = $agent['specialization'] ?? '';

  // Build education string
  $education_parts = [];

  // If 'education' field exists, use it first
  if (!empty($agent['education'])) {
      $education_parts[] = $agent['education'];
  }

  // Add course, school, graduation year if present
  if (!empty($agent['course'])) {
      $education_parts[] = $agent['course'];
  }

  if (!empty($agent['school'])) {
      $education_parts[] = $agent['school'];
  }

  if (!empty($agent['graduation_year'])) {
      $education_parts[] = $agent['graduation_year'];
  }

  // Concatenate all parts with separator
  $education_text = implode(', ', $education_parts);

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
    <span class="close" onclick="closeReportModal()">&times;</span>
    <h2>Report this Agent</h2>

    <form id="reportAgentForm" method="POST" action="report_agent.php" enctype="multipart/form-data">
      <!-- Hidden fields -->
      <input type="hidden" name="agent_id" value="<?= $agent_user_id ?>">
      <input type="hidden" name="reported_by" value="<?= $current_user_id ?>">

      <!-- Reason dropdown -->
      <label for="report-reason">Reason</label>
      <select name="reason" id="report-reason" required onchange="toggleOtherReason()">
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
        required
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
  document.addEventListener('DOMContentLoaded', function() {
    /* ====== Reviews Row Scroll Buttons ====== */
    const leftBtn = document.querySelector('.left-btn');
    const rightBtn = document.querySelector('.right-btn');
    const row = document.querySelector('.reviews-row');

    if (leftBtn && rightBtn && row) {
      leftBtn.addEventListener('click', () =>
        row.scrollBy({ left: -320, behavior: 'smooth' })
      );
      rightBtn.addEventListener('click', () =>
        row.scrollBy({ left: 320, behavior: 'smooth' })
      );
    }

    /* ====== Logout Modal ====== */
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

    /* ====== Report Agent Modal ====== */
    const reportLink = document.querySelector('.report-agent');
    const reportModal = document.getElementById('reportAgentModal');
    const reasonSelect = document.getElementById('report-reason');
    const otherContainer = document.getElementById('otherReasonContainer');
    const otherInput = document.getElementById('other-reason');

    if (reportLink && reportModal) {
      // Open modal
      reportLink.addEventListener('click', e => {
        e.preventDefault();
        reportModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      });

      // Close modal when clicking the X or outside area
      reportModal.addEventListener('click', e => {
        if (e.target.classList.contains('report-modal') || e.target.classList.contains('close')) {
          reportModal.style.display = 'none';
          document.body.style.overflow = 'auto';
        }
      });
    }

    if (reasonSelect) {
      reasonSelect.addEventListener('change', () => {
        if (reasonSelect.value === 'other') {
          otherContainer.style.display = 'block';
          otherInput.required = true;
          otherInput.focus();
        } else {
          otherContainer.style.display = 'none';
          otherInput.required = false;
          otherInput.value = '';
        }
      });
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
