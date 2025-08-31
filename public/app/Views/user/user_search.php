<?php
// user_search.php
require_once __DIR__ . '/../../../../config/database.php';

// Decide whether this request is AJAX (fetch from JS) or normal page load
$isAjax = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (!empty($_POST['ajax']) && $_POST['ajax'] == '1');

if ($isAjax) {
    $location = $_POST['location'] ?? '';
    $property_type = $_POST['property_type'] ?? '';
    $price_range = $_POST['price_range'] ?? '';
    $bedrooms = $_POST['bedrooms'] ?? '';
    $bathrooms = $_POST['bathrooms'] ?? '';
    $size = $_POST['size'] ?? '';
} else {
    $location = $_GET['location'] ?? '';
    $property_type = $_GET['property_type'] ?? '';
    $price_range = $_GET['price_range'] ?? '';
    $bedrooms = $_GET['bedrooms'] ?? '';
    $bathrooms = $_GET['bathrooms'] ?? '';
    $size = $_GET['size'] ?? '';
}

// Build query...
$sql = "
    SELECT p.*, pi.image_path
    FROM properties p
    LEFT JOIN property_images pi 
        ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE 1=1
";
$params = [];
$types = "";

// Filters (same as before)...
if ($location !== '') { $sql .= " AND p.location = ?"; $params[] = $location; $types .= "s"; }
if ($property_type !== '') { $sql .= " AND p.property_type = ?"; $params[] = $property_type; $types .= "s"; }
if ($price_range !== '') {
    if ($price_range === '5000000+') { $sql .= " AND p.price >= 5000000"; }
    else {
        $parts = explode('-', $price_range);
        if (count($parts) === 2) {
            $sql .= " AND p.price BETWEEN ? AND ?";
            $params[] = (float)$parts[0];
            $params[] = (float)$parts[1];
            $types .= "dd";
        }
    }
}
if ($bedrooms !== '') { $sql .= " AND p.bedrooms >= ?"; $params[] = (int)$bedrooms; $types .= "i"; }
if ($bathrooms !== '') { $sql .= " AND p.bathrooms >= ?"; $params[] = (int)$bathrooms; $types .= "i"; }
if ($size !== '') {
    if ($size === '200+') { $sql .= " AND p.sqm >= 200"; }
    else {
        $parts = explode('-', $size);
        if (count($parts) === 2) {
            $sql .= " AND p.sqm BETWEEN ? AND ?";
            $params[] = (float)$parts[0];
            $params[] = (float)$parts[1];
            $types .= "dd";
        }
    }
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $bind_names = [];
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}
$stmt->execute();
$result = $stmt->get_result();
$properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
$conn->close();
?>

<!-- Keep this part inside the same file for initial load -->
<div class="search-container">
    <div class="search">
        <div class="search-form">
            <!-- Location -->
            <button type="button" class="search-field" data-field="location">
                <span class="label">Location</span>
                <span class="value" data-default="All Locations"><?= $location !== '' ? htmlspecialchars($location) : 'All Locations' ?></span>
                <select name="location" id="location">
                    <option value="" <?= $location === '' ? 'selected' : '' ?>>All Locations</option>
                    <option value="Batangas City" <?= $location === 'Batangas City' ? 'selected' : '' ?>>Batangas City</option>
                    <option value="Lipa City" <?= $location === 'Lipa City' ? 'selected' : '' ?>>Lipa City</option>
                    <option value="Tanauan City" <?= $location === 'Tanauan City' ? 'selected' : '' ?>>Tanauan City</option>
                </select>
            </button>

            <!-- Property Type -->
            <button type="button" class="search-field" data-field="property_type">
                <span class="label">Property Type</span>
                <span class="value" data-default="All Types"><?= $property_type !== '' ? htmlspecialchars($property_type) : 'All Types' ?></span>
                <select name="property_type" id="property_type">
                    <option value="" <?= $property_type === '' ? 'selected' : '' ?>>All Types</option>
                    <option value="house" <?= $property_type === 'house' ? 'selected' : '' ?>>House</option>
                    <option value="condo" <?= $property_type === 'condo' ? 'selected' : '' ?>>Condominium</option>
                    <option value="land" <?= $property_type === 'land' ? 'selected' : '' ?>>Land</option>
                </select>
            </button>

            <!-- Price Range -->
            <button type="button" class="search-field" data-field="price_range">
                <span class="label">Price Range</span>
                <span class="value" data-default="Any Price"><?= $price_range !== '' ? htmlspecialchars($price_range) : 'Any Price' ?></span>
                <select name="price_range" id="price_range">
                    <option value="" <?= $price_range === '' ? 'selected' : '' ?>>Any Price</option>
                    <option value="0-1000000" <?= $price_range === '0-1000000' ? 'selected' : '' ?>>Under ₱1M</option>
                    <option value="1000000-5000000" <?= $price_range === '1000000-5000000' ? 'selected' : '' ?>>₱1M - ₱5M</option>
                    <option value="5000000+" <?= $price_range === '5000000+' ? 'selected' : '' ?>>₱5M+</option>
                </select>
            </button>

            <!-- Bedrooms -->
            <button type="button" class="search-field" data-field="bedrooms">
                <span class="label">Bedrooms</span>
                <span class="value" data-default="Any"><?= $bedrooms !== '' ? htmlspecialchars($bedrooms) . '+' : 'Any' ?></span>
                <select name="bedrooms" id="bedrooms">
                    <option value="" <?= $bedrooms === '' ? 'selected' : '' ?>>Any</option>
                    <option value="1" <?= $bedrooms === '1' ? 'selected' : '' ?>>1+</option>
                    <option value="2" <?= $bedrooms === '2' ? 'selected' : '' ?>>2+</option>
                    <option value="3" <?= $bedrooms === '3' ? 'selected' : '' ?>>3+</option>
                    <option value="4" <?= $bedrooms === '4' ? 'selected' : '' ?>>4+</option>
                </select>
            </button>

            <!-- Bathrooms -->
            <button type="button" class="search-field" data-field="bathrooms">
                <span class="label">Bathrooms</span>
                <span class="value" data-default="Any"><?= $bathrooms !== '' ? htmlspecialchars($bathrooms) . '+' : 'Any' ?></span>
                <select name="bathrooms" id="bathrooms">
                    <option value="" <?= $bathrooms === '' ? 'selected' : '' ?>>Any</option>
                    <option value="1" <?= $bathrooms === '1' ? 'selected' : '' ?>>1+</option>
                    <option value="2" <?= $bathrooms === '2' ? 'selected' : '' ?>>2+</option>
                    <option value="3" <?= $bathrooms === '3' ? 'selected' : '' ?>>3+</option>
                    <option value="4" <?= $bathrooms === '4' ? 'selected' : '' ?>>4+</option>
                </select>
            </button>

            <!-- Size -->
            <button type="button" class="search-field" data-field="size">
                <span class="label">Size (sqm)</span>
                <span class="value" data-default="Any Size"><?= $size !== '' ? htmlspecialchars($size) : 'Any Size' ?></span>
                <select name="size" id="size">
                    <option value="" <?= $size === '' ? 'selected' : '' ?>>Any Size</option>
                    <option value="0-50" <?= $size === '0-50' ? 'selected' : '' ?>>Up to 50 sqm</option>
                    <option value="50-100" <?= $size === '50-100' ? 'selected' : '' ?>>50 - 100 sqm</option>
                    <option value="100-200" <?= $size === '100-200' ? 'selected' : '' ?>>100 - 200 sqm</option>
                    <option value="200+" <?= $size === '200+' ? 'selected' : '' ?>>200+ sqm</option>
                </select>
            </button>

            <!-- Submit -->
            <button id="searchForm1" class="user-search-submit" aria-label="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
    </div>


    <div class="properties-grid" id="propertiesGrid">
        <?php if (!empty($properties)): ?>
          <?php foreach ($properties as $property): ?>
            <div class="property-card">
              <div class="property-image">
                <?php if (!empty($property['image_path'])): ?>
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
                <!-- ✅ Changed to button for modal -->
                <button class="btn btn-outline view-details-btn" data-id="<?= (int)$property['id'] ?>">View Details</button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No properties available at the moment.</p>
        <?php endif; ?>
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
              <button class="btn btn-primary messageAgentBtn">
                  <i class="fas fa-envelope"></i> Message Agent
              </button>

              <button class="btn btn-outline"><i class="fas fa-heart"></i> Save to Favorites</button>

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
    .search-container {
    position: relative;
    width: 100%;
    min-height: calc(100vh - 120px); /* subtract header/footer if any */
    display: grid;
    grid-template-rows: auto 1fr; /* search bar then results */
    align-items: start;
    padding: 20px;
    box-sizing: border-box;
  }
  .search {
    padding-top: 60px;
    padding-bottom: 60px;
    display: flex;
    justify-content: center;
    align-items: center;
  }
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
document.addEventListener('DOMContentLoaded', () => {
  /** ------------------------------- 
 * 🔍 SEARCH FORM (AJAX)
 * ------------------------------- */
  const searchBtn = document.getElementById('searchForm1');
  if (searchBtn) {
    searchBtn.addEventListener('click', async (e) => {
      e.preventDefault();

      // Collect filters from all dropdowns
      const params = ['location', 'property_type', 'price_range', 'bedrooms', 'bathrooms', 'size']
        .reduce((obj, id) => {
          const el = document.getElementById(id);
          obj[id] = el ? el.value : '';
          return obj;
        }, {});

      const query = new URLSearchParams(params).toString();

      try {
        const res = await fetch(`/BatEstateExplorer/public/api/get_properties.php?${query}`);
        if (!res.ok) throw new Error('Network response was not OK');
        const data = await res.json();

        const grid = document.getElementById('propertiesGrid');
        if (!grid) {
          console.error("❌ propertiesGrid element not found in DOM");
          return;
        }

        if (!data.properties || data.properties.length === 0) {
          grid.innerHTML = '<p>No properties available at the moment.</p>';
          return;
        }

        // Render property cards
        grid.innerHTML = data.properties.map(p => `
          <div class="property-card">
            <div class="property-image">
              <img src="${p.image_path || '/BatEstateExplorer/assets/images/bg4.jpg'}" alt="${p.title}">
            </div>
            <div class="property-content">
              <h3>${p.title}</h3>
              <p class="property-location"><i class="fas fa-map-marker-alt"></i> ${p.location}</p>
              <p class="property-price">₱${Number(p.price).toLocaleString()}</p>
              <div class="property-features">
                <span><i class="fas fa-bed"></i> ${p.bedrooms} Beds</span>
                <span><i class="fas fa-bath"></i> ${p.bathrooms} Baths</span>
              </div>
              <button class="btn btn-outline view-details-btn" data-id="${p.id}">View Details</button>
            </div>
          </div>
        `).join('');

        // Re-bind modal handlers since cards got replaced
        if (typeof attachViewDetailHandlers === 'function') {
          attachViewDetailHandlers();
        }

      } catch (err) {
        console.error('❌ Fetch error:', err);
      }
    });
  }

  /** -------------------------------
   * 🎛️ DROPDOWN LABEL UPDATER
   * ------------------------------- */
  document.querySelectorAll('.search-field select').forEach(selectEl => {
    const valueSpan = selectEl.closest('.search-field').querySelector('.value');
    const updateValue = () => {
      valueSpan.textContent = selectEl.value === ""
        ? valueSpan.dataset.default
        : selectEl.options[selectEl.selectedIndex].text;
    };
    updateValue();
    selectEl.addEventListener('change', updateValue);
  });

  /** -------------------------------
   * 🏠 MODAL HANDLING
   * ------------------------------- */
  let modalSwiper;

  function attachViewDetailHandlers() {
    document.querySelectorAll('.view-details-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        try {
          const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(id)}`);
          const data = await res.json();

          if (!data.success) {
            alert(data.error || 'Failed to fetch property.');
            return;
          }

          const prop = data.property;

          // Build image slides
          const wrapper = document.getElementById('modalImageWrapper');
          wrapper.innerHTML = '';
          const images = (prop.images && prop.images.length) ? prop.images : ['/BatEstateExplorer/assets/images/bg4.jpg'];
          images.forEach(img => {
            wrapper.innerHTML += `
              <div class="swiper-slide">
                <img src="${img}" style="width:100%;border-radius:8px;">
              </div>
            `;
          });

          // Init/update Swiper
          if (modalSwiper) {
            modalSwiper.update();
          } else {
            modalSwiper = new Swiper('.modal-swiper', {
              loop: images.length > 1,
              navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
              pagination: { el: '.swiper-pagination', clickable: true },
            });
          }

          // Fill details
          document.getElementById('modalTitle').textContent = prop.title;
          document.getElementById('modalLocation').textContent = `📍 ${prop.location}`;
          document.getElementById('modalPrice').textContent = `₱${parseFloat(prop.price).toLocaleString()}`;
          document.getElementById('modalBedrooms').textContent = prop.bedrooms;
          document.getElementById('modalBathrooms').textContent = prop.bathrooms;
          document.getElementById('modalDescription').textContent = prop.description || 'No description available.';

          // Review button
          const reviewBtn = document.getElementById('leaveReviewBtn');
          if (data.has_privilege) {
            reviewBtn.style.display = 'inline-block';
            reviewBtn.onclick = () => openReviewModal(prop.id);
          } else {
            reviewBtn.style.display = 'none';
            reviewBtn.onclick = null;
          }

          // Save agent id for messaging
          const messageBtn = document.querySelector('.messageAgentBtn');
          if (messageBtn) {
            messageBtn.dataset.agentId = prop.agent_id;
          }

          // Show modal
          document.getElementById('propertyModal').style.display = 'flex';

        } catch (err) {
          console.error(err);
          alert('Failed to load property details.');
        }
      });
    });
  }

  // Close property modal
  document.querySelector('.modal-close').addEventListener('click', () => {
    document.getElementById('propertyModal').style.display = 'none';
  });
  document.getElementById('propertyModal').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) {
      e.currentTarget.style.display = 'none';
    }
  });

  /** -------------------------------
   * ⭐ REVIEW MODAL
   * ------------------------------- */
  function openReviewModal(propertyId) {
    document.getElementById('reviewPropertyId').value = propertyId;
    document.getElementById('reviewModal').style.display = 'flex';
  }

  document.getElementById('reviewForm').addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(e.target);

    try {
      const res = await fetch('/BatEstateExplorer/public/api/submit_review.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        alert('Review submitted successfully!');
        document.getElementById('reviewModal').style.display = 'none';
        e.target.reset();
      } else {
        alert(data.error || 'Failed to submit review.');
      }
    } catch (err) {
      console.error(err);
      alert('Error submitting review.');
    }
  });

  /** -------------------------------
 * ✉️ MESSAGE AGENT
 * ------------------------------- */
  document.querySelectorAll('.messageAgentBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      const agentId = btn.dataset.agentId;
      if (!agentId) { 
        alert("Agent not found."); 
        return; 
      }
      window.open(
        `/BatEstateExplorer/public/message.php?agent_id=${encodeURIComponent(agentId)}`, 
        '_blank'
      );
    });
  });

  // Initial binding
  attachViewDetailHandlers();
});
</script>
