<?php
// Ensure we have a logged-in user
if (!isset($user) || !is_array($user)) {
    die('Access denied.');
}

// Build a safe first-name for the welcome card
$rawFirst = null;
if (!empty($user['first_name'])) {
    $rawFirst = $user['first_name'];
} elseif (!empty($user['name'])) {
    $parts = preg_split('/\s+/', trim($user['name']));
    $rawFirst = $parts[0] ?? null;
} elseif (!empty($user['username'])) {
    $rawFirst = $user['username'];
} elseif (!empty($user['email'])) {
    $local = strstr($user['email'], '@', true);
    $rawFirst = $local ?: $user['email'];
}
$agentFirst = htmlspecialchars($rawFirst ?: 'Agent', ENT_QUOTES, 'UTF-8');

// --- Properties: ALL active listings for the homepage feed ---
if (!isset($conn)) {
    die('DB connection missing.');
}

$sql = "SELECT id, title, location, price, bedrooms, bathrooms, created_at 
        FROM properties 
        WHERE status = 'available'
        ORDER BY created_at DESC";
$result = $conn->query($sql);

// Include modular card
require_once __DIR__ . '/../../../../components/agent_property_card.php';

?>

<!-- user_home.php -->
<div class="welcome-card">
  <h2>Welcome back, <?= $agentFirst ?>!</h2>
  <p>Here’s a quick overview of your latest listings and tools.</p>
  
  <div class="action-buttons">
    <a href="agent_dashboard.php?view=associate_search" class="btn btn-primary">
      <i class="fa-solid fa-search"></i> Search Properties
    </a>
    <a href="agent_dashboard.php?view=associate_profile" class="btn btn-secondary">
      <i class="fa-solid fa-user"></i> Manage Profile
    </a>
    <a href="agent_dashboard.php?view=associate_profile" class="btn btn-success">
      <i class="fa-solid fa-plus"></i> Add New Property
    </a>
  </div>
</div>

<section class="properties">
  <div class="container">
    <h3 class="section-title">Latest Properties</h3>

    <div class="properties-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($property = $result->fetch_assoc()): ?>
                <?php render_agent_property_card($property); ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>
  </div>
</section>

<?php
// Render the modal only once
render_agent_property_card([], true);
?>


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

      if (!data.success || !data.property) {
        alert(data.error || "Failed to fetch property details.");
        return;
      }

      const property = data.property; // ✅ Extract property object

      // Build image slides
      const wrapper = document.getElementById('modalImageWrapper');
      wrapper.innerHTML = '';
      (property.images && property.images.length ? property.images : ["/BatEstateExplorer/assets/images/bg4.jpg"])
        .forEach(img => {
          wrapper.innerHTML += `
            <div class="swiper-slide">
              <img src="${img}" style="width:100%;border-radius:8px;">
            </div>
          `;
        });

      // Init or update Swiper
      if (modalSwiper) {
        modalSwiper.update();
      } else {
        modalSwiper = new Swiper('.modal-swiper', {
          loop: (property.images && property.images.length > 1),
          navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
          pagination: { el: '.swiper-pagination', clickable: true },
        });
      }

      // ✅ Fill modal details with safe fallbacks
      document.getElementById('modalTitle').textContent       = property.title || "Untitled";
      document.getElementById('modalLocation').textContent    = `📍 ${property.location || "Unknown"}`;
      document.getElementById('modalPrice').textContent       = property.price ? `₱${Number(property.price).toLocaleString()}` : "₱0";
      document.getElementById('modalBedrooms').textContent    = property.bedrooms ?? 0;
      document.getElementById('modalBathrooms').textContent   = property.bathrooms ?? 0;
      document.getElementById('modalDescription').textContent = property.description || "No description available.";

      // Show modal
      document.getElementById('propertyModal').style.display = 'flex';
    } catch (err) {
      console.error(err);
      alert('Failed to load property details.');
    }
  });
});

// Close modal
document.querySelector('.modal-close').addEventListener('click', () => {
  document.getElementById('propertyModal').style.display = 'none';
});

// Close when clicking outside
document.getElementById('propertyModal').addEventListener('click', (e) => {
  if (e.target === e.currentTarget) {
    e.currentTarget.style.display = 'none';
  }
});
</script>
