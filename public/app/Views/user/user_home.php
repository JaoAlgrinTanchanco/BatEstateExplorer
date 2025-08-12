<?php
// user_home.php
ob_start();

// Make sure $conn is your active mysqli connection
// If not available here, include or require your DB connection file
// Example:
// require_once __DIR__ . '/../../app/bootstrap.php';

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
          <button class="btn btn-primary"><i class="fas fa-envelope"></i> Message Agent</button>
          <button class="btn btn-outline"><i class="fas fa-heart"></i> Save to Favorites</button>
        </div>
      </div>
    </div>
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
    padding: 10px; /* Prevents touching screen edges */
  }
  .modal-content {
    background: #fff;
    max-width: 900px; /* smaller than before */
    width: 90%;
    border-radius: 12px;
    padding: 20px;
    position: relative;
    display: flex;
    flex-direction: column;
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
    height: auto;
    border-radius: 8px;
    object-fit: cover;
  }
  .modal-details {
    flex: 1 1 55%;
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

<script>
let modalSwiper;

document.querySelectorAll('.view-details-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const id = btn.dataset.id;
    try {
      const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(id)}`);
      const data = await res.json();

      if (data.error) {
        alert(data.error);
        return;
      }

      // Build image slides
      const wrapper = document.getElementById('modalImageWrapper');
      wrapper.innerHTML = '';
      (data.images && data.images.length ? data.images : [data.image_path]).forEach(img => {
        wrapper.innerHTML += `
          <div class="swiper-slide">
            <img src="${img || '/BatEstateExplorer/assets/images/bg4.jpg'}" style="width:100%;border-radius:8px;">
          </div>
        `;
      });

      // Init or update Swiper
      if (modalSwiper) {
        modalSwiper.update();
      } else {
        modalSwiper = new Swiper('.modal-swiper', {
          loop: (data.images && data.images.length > 1),
          navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
          pagination: { el: '.swiper-pagination', clickable: true },
        });
      }

      // Fill details
      document.getElementById('modalTitle').textContent = data.title;
      document.getElementById('modalLocation').textContent = `📍 ${data.location}`;
      document.getElementById('modalPrice').textContent = `₱${parseFloat(data.price).toLocaleString()}`;
      document.getElementById('modalBedrooms').textContent = data.bedrooms;
      document.getElementById('modalBathrooms').textContent = data.bathrooms;
      document.getElementById('modalDescription').textContent = data.description || 'No description available.';

      document.getElementById('propertyModal').style.display = 'flex';
    } catch (err) {
      console.error(err);
      alert('Failed to load property details.');
    }
  });
});

document.querySelector('.modal-close').addEventListener('click', () => {
  document.getElementById('propertyModal').style.display = 'none';
});

document.getElementById('propertyModal').addEventListener('click', (e) => {
  if (e.target === e.currentTarget) {
    e.currentTarget.style.display = 'none';
  }
});
</script>
