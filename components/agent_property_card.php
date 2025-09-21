<?php
if (!function_exists('render_agent_property_card')) {
    function render_agent_property_card(array $property, bool $modalOnly = false) {
        $conn = $GLOBALS['conn'] ?? null;
        if (!$conn) {
            echo "<p style='color:red'>Database connection not found.</p>";
            return;
        }

        $propertyId = (int)($property['id'] ?? 0);

        // --- Fetch property images ---
        $images = ['/BatEstateExplorer/assets/images/bg4.jpg'];
        $stmtImg = $conn->prepare("SELECT image_path FROM property_images WHERE property_id = ? ORDER BY id ASC");
        if ($stmtImg) {
            $stmtImg->bind_param("i", $propertyId);
            $stmtImg->execute();
            $resImg = $stmtImg->get_result();
            $images = [];
            while ($row = $resImg->fetch_assoc()) {
                $images[] = '/' . ltrim($row['image_path'], '/');
            }
            $stmtImg->close();
        }
        if (empty($images)) $images[] = '/BatEstateExplorer/assets/images/bg4.jpg';
        $property['images'] = $images;

        // --- Fetch past reviews ---
        $property['past_reviews'] = [];
        $reviewsSql = "
            SELECT r.*, u.first_name, u.last_name
            FROM property_reviews r
            INNER JOIN users u ON r.user_id = u.id
            WHERE r.property_id = ?
            ORDER BY r.created_at DESC
        ";
        $stmt = $conn->prepare($reviewsSql);
        if ($stmt) {
            $stmt->bind_param("i", $propertyId);
            $stmt->execute();
            $resReviews = $stmt->get_result();
            $property['past_reviews'] = $resReviews->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        // --- Sanitize fields ---
        $image = htmlspecialchars($property['images'][0]);
        $title = htmlspecialchars($property['title'] ?? '');
        $location = htmlspecialchars($property['location'] ?? '');
        $price = number_format((float)($property['price'] ?? 0), 2);
        $bedrooms = (int)($property['bedrooms'] ?? 0);
        $bathrooms = (int)($property['bathrooms'] ?? 0);
        $createdAt = strtotime($property['created_at'] ?? 'now');

        // --- Render card ---
        if (!$modalOnly):
?>
<div class="property-card"
     data-id="<?= $propertyId ?>"
     data-images='<?= json_encode($property['images']) ?>'
     data-location="<?= strtolower($location) ?>"
     data-type="<?= htmlspecialchars($property['property_type'] ?? '') ?>"
     data-price="<?= (int)($property['price'] ?? 0) ?>"
     data-bedrooms="<?= $bedrooms ?>"
     data-bathrooms="<?= $bathrooms ?>"
     data-size="<?= (int)($property['sqm'] ?? 0) ?>"
     data-date="<?= $createdAt ?>"
     data-image="<?= $image ?>">

    <!-- Image always visible -->
    <div class="property-image">
        <img src="<?= $image ?>" alt="Property Image">
    </div>

    <!-- Overlay slides up on hover -->
    <div class="property-overlay">
        <h3><?= $title ?></h3>
        <p class="property-location"><i class="fas fa-map-marker-alt"></i> <?= $location ?></p>
        <p class="property-price">₱<?= $price ?></p>
        <div class="property-features">
            <span><i class="fas fa-bed"></i> <?= $bedrooms ?> Beds</span>
            <span><i class="fas fa-bath"></i> <?= $bathrooms ?> Baths</span>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="propertyModal" class="modal" style="display:none;">
  <div class="custom-modal-content">
    <button class="close">&times;</button>

    <!-- Left Side -->
    <div class="modal-left">
      <div class="property-main-image" style="background-image: url('');"></div>
      <div class="property-name"></div>
      <div class="property-images"></div>
    </div>

    <!-- Right Side -->
    <div class="modal-right">
      <section><span class="label">Location:</span> <span class="value location"></span></section>
      <section><span class="label">Price:</span> <span class="value price"></span></section>
      <section><span class="label">Property Type:</span> <span class="value property-type"></span></section>
      <section><span class="label">Bedrooms:</span> <span class="value bedrooms"></span></section>
      <section><span class="label">Bathrooms:</span> <span class="value bathrooms"></span></section>
      <section><span class="label">Area:</span> <span class="value sqm"></span></section>
      <section><span class="label">Lot Size:</span> <span class="value lot_size"></span></section>
      <section><span class="label">Status:</span> <span class="value status"></span></section>
      <section><span class="label">Date Uploaded:</span> <span class="value date_uploaded"></span></section>

      <section>
        <span class="label">Description:</span>
        <div class="property-description"></div>
      </section>
    </div>
  </div>
</div>

<?php
        endif;

        // --- Include modal only once ---
        if (!defined('AGENT_PROPERTY_MODAL_INCLUDED')):
            define('AGENT_PROPERTY_MODAL_INCLUDED', true);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
<?php echo file_get_contents($_SERVER['DOCUMENT_ROOT'].'/BatEstateExplorer/assets/js/agent_card_logic.js'); ?>
</script>

<?php
        endif;
    }
}
?>
