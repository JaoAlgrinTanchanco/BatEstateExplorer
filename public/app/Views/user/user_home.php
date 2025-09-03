<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user']['id']) && empty($_SESSION['user']['token'])) {
    $_SESSION['user']['token'] = base64_encode(json_encode([
        'user_id' => $_SESSION['user']['id'],
        'email' => $_SESSION['user']['email'] ?? '',
        'user_type' => $_SESSION['user']['user_type'] ?? '',
        'exp' => time() + 3600 // optional expiration
    ]));
}

ob_start();
$userId = $_SESSION['user']['id'] ?? 0;

$sql = "SELECT p.id, p.title, p.location, p.price, p.bedrooms, p.bathrooms, pi.image_path
        FROM properties p
        LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
        WHERE p.status = 'available'
        ORDER BY p.created_at DESC
        LIMIT 5";

$result = $conn->query($sql);
?>

<!-- user_home.php -->
<div class="welcome-card">
    <h2>Find Your Dream Property</h2>
    <p>Welcome to BatEstate! We're here to help you find the perfect property in Batangas.</p>
    
    <div class="action-buttons">
        <a href="user_dashboard.php?view=search" class="btn btn-primary">
            <i class="fa-solid fa-search"></i> Search Properties
        </a>
        <a href="user_dashboard.php?view=profile" class="btn btn-secondary">
            <i class="fa-solid fa-user"></i> View Profile
        </a>
    </div>
</div>  

<section class="properties">
  <div class="container">
    <h3 class="section-title">Latest Properties</h3>

    <div class="properties-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($property = $result->fetch_assoc()): ?>
                <div class="property-card">
                    <div class="property-image">
                        <?php if ($property['image_path']): ?>
                            <img src="<?= htmlspecialchars($property['image_path']) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                        <?php else: ?>
                            <img src="/BatEstateExplorer/assets/images/bg4.jpg" alt="Default Image">
                        <?php endif; ?>
                    </div>
                    <div class="property-content">
                        <h3><?= htmlspecialchars($property['title']) ?></h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?></p>
                        <p class="property-price">₱<?= number_format($property['price'], 2) ?></p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> <?= (int)$property['bedrooms'] ?> Beds</span>
                            <span><i class="fas fa-bath"></i> <?= (int)$property['bathrooms'] ?> Baths</span>
                        </div>
                        <button class="btn btn-outline view-details-btn" data-id="<?= (int)$property['id'] ?>">View Details</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>
  </div>
</section>

<div class="quick-stats">
    <h3>Quick Overview</h3>
    <div class="stats-grid">
        <div class="stat-item">
            <i class="fa-solid fa-house"></i>
            <span>Browse Properties</span>
        </div>
        <div class="stat-item">
            <i class="fa-solid fa-user-tie"></i>
            <span>Connect with Agents</span>
        </div>
        <div class="stat-item">
            <i class="fa-solid fa-heart"></i>
            <span>Save Favorites</span>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div id="propertyModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="modal-close">&times;</span>
    <div class="modal-body">
      <!-- Image Carousel -->
      <div class="modal-image">
        <div class="swiper modal-swiper">
          <div class="swiper-wrapper" id="modalImageWrapper"></div>
          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
          <div class="swiper-pagination"></div>
        </div>
      </div>

      <!-- Details -->
      <div class="modal-details">
        <h2 id="modalTitle"></h2>
        <p id="modalLocation"></p>
        <p id="modalPrice" class="price"></p>
        <div class="features">
          <span><i class="fas fa-bed"></i> <span id="modalBedrooms"></span> Beds</span>
          <span><i class="fas fa-bath"></i> <span id="modalBathrooms"></span> Baths</span>
        </div>
        <p><strong>Description:</strong></p>
        <p id="modalDescription"></p>

        <div class="modal-actions">
          <button class="btn btn-primary message-agent-btn">
              <i class="fas fa-envelope"></i> Message Agent
          </button>

          <button id="saveFavoriteBtn" class="btn btn-outline"><i class="fas fa-heart"></i> Save to Favorites</button>

          <button id="leaveReviewBtn" class="btn btn-success" style="display:none;">
            <i class="fas fa-star"></i> Leave a Review
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

<div id="reviewModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="modal-close" onclick="document.getElementById('reviewModal').style.display='none'">&times;</span>
    <h3>Leave a Review</h3>
    <form id="reviewForm">
      <label>Rating:</label>
      <select name="rating" required>
        <option value="">Select...</option>
        <option value="5">⭐⭐⭐⭐⭐</option>
        <option value="4">⭐⭐⭐⭐</option>
        <option value="3">⭐⭐⭐</option>
        <option value="2">⭐⭐</option>
        <option value="1">⭐</option>
      </select>
      <label>Your Review:</label>
      <textarea name="review_text" rows="4" required></textarea>
      <input type="hidden" name="property_id" id="reviewPropertyId">
      <button type="submit" class="btn btn-success">Submit</button>
    </form>
  </div>
</div>


<style>
  .modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999999;
    animation: fadeIn 0.3s ease;
    padding: 10px;
    overflow-y: auto; /* Allow scrolling if modal content exceeds viewport */
  }

  .modal-content {
    background: #fff;
    max-width: 900px;
    width: 90%;
    max-height: 90vh; /* Prevent modal from going outside viewport */
    border-radius: 12px;
    padding: 20px;
    position: relative;
    display: flex;
    flex-direction: column;
    overflow-y: auto; /* Scroll inside modal if needed */
  }

  .modal-body {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
  }

  .modal-image {
    flex: 1 1 45%;
  }

  .modal-image img {
    width: 100%;
    max-height: 350px; /* Prevent image from stretching too tall */
    border-radius: 8px;
    object-fit: cover;
  }

  .modal-details {
    flex: 1 1 55%;
    overflow-y: auto;
  }

  .modal-close {
    font-size: 26px;
    cursor: pointer;
    position: absolute;
    top: 12px;
    right: 16px;
    background: none;
    border: none;
    color: #666;
  }
  .modal-close:hover {
    color: #000;
  }

  .features span {
    display: inline-block;
    margin-right: 12px;
    font-size: 14px;
  }

  .price {
    font-size: 20px;
    color: #28a745;
    margin: 8px 0;
    font-weight: bold;
  }

  .modal-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .btn {
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
  }

  .btn-primary {
    background: #007bff;
    color: #fff;
    border: none;
  }

  .btn-outline {
    border: 1px solid #ccc;
    background: white;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
</style>

<!-- Swiper CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css"/>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script src="/BatEstateExplorer/assets/js/user_home.js"></script>
