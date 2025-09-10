<?php
require_once __DIR__ . '/../../../../components/agent_property_card.php';

// Ensure we have a logged-in user
if (!isset($user) || !is_array($user)) die('Access denied.');

// Safe first name for welcome card
$rawFirst = null;
if (!empty($user['first_name'])) $rawFirst = $user['first_name'];
elseif (!empty($user['name'])) { $parts = preg_split('/\s+/', trim($user['name'])); $rawFirst = $parts[0] ?? null; }
elseif (!empty($user['username'])) $rawFirst = $user['username'];
elseif (!empty($user['email'])) $rawFirst = strstr($user['email'], '@', true) ?: $user['email'];

$agentFirst = htmlspecialchars($rawFirst ?: 'Agent', ENT_QUOTES, 'UTF-8');

// --- Fetch latest properties for this agent ---
if (!isset($conn)) die('DB connection missing.');

$sql = "SELECT p.*, pi.image_path
        FROM properties p
        LEFT JOIN property_images pi 
          ON p.id = pi.property_id AND pi.is_primary = 1
        WHERE p.status = 'available'
          AND p.agent_id = ?
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/direct_home.css">

<div class="welcome-card">
  <h2>Welcome back, <?= $agentFirst ?>!</h2>
  <p>Here’s a quick overview of your latest listings and tools.</p>
  
  <div class="action-buttons">
    <a href="agent_dashboard.php?view=direct_search" class="btn btn-primary">
      <i class="fa-solid fa-search"></i> Search Properties
    </a>
    <a href="agent_dashboard.php?view=direct_profile" class="btn btn-secondary">
      <i class="fa-solid fa-user"></i> Manage Profile
    </a>
    <a href="agent_dashboard.php?view=direct_profile" class="btn btn-success">
      <i class="fa-solid fa-plus"></i> Add New Property
    </a>
  </div>
</div>

<section class="properties">
  <div class="container">
    <h3 class="section-title">Latest Properties</h3>
    <div class="properties-grid">
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property):
                // Add dataset fields required by the modular card JS
                $property['data_type'] = $property['property_type'];
                $property['data_size'] = $property['sqm'] ?? 0;
                render_agent_property_card($property);
            endforeach; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>
  </div>
</section>

<?php
// Render modal once (empty array for modal-only mode)
render_agent_property_card([], true);
?>

<script src="/BatEstateExplorer/assets/js/agent_property_card_logic.js"></script>

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
