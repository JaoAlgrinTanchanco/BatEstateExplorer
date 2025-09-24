<?php
if (!function_exists('render_property_card')) {
    function render_property_card(array $property, bool $modalOnly = false) {
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

    <!-- Agent-style overlay -->
    <div class="property-image">
        <img src="<?= $image ?>" alt="Property Image">
    </div>
    <div class="property-overlay">
        <h3><?= $title ?></h3>
        <p class="property-location"><i class="fas fa-map-marker-alt"></i> <?= $location ?></p>
        <p class="property-price">₱<?= $price ?></p>
        <div class="property-features">
            <span><i class="fas fa-bed"></i> <?= $bedrooms ?> Beds</span>
            <span><i class="fas fa-bath"></i> <?= $bathrooms ?> Baths</span>
        </div>
        <button class="btn btn-outline view-details-btn" data-id="<?= $propertyId ?>">View Details</button>
    </div>
</div>
<?php
        endif;

        // --- Include modal only once ---
        if (!defined('PROPERTY_MODAL_INCLUDED')):
            define('PROPERTY_MODAL_INCLUDED', true);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- Combined Modal -->
<div id="propertyModal" class="modal" style="display:none;">
    <div class="custom-modal-content">
        <button class="close">&times;</button>

        <!-- Left side: images -->
        <div class="modal-left">
            <div class="property-main-image" style="background-image: url('');"></div>
            <div class="property-name"></div>
            <div class="property-images"></div>
        </div>

        <!-- Right side: details + actions + reviews -->
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
            <section><span class="label">Description:</span><div class="property-description"></div></section>

            <!-- User actions -->
            <div class="modal-actions" style="margin-top:15px;">
                <button class="btn btn-primary message-agent-btn"><i class="fas fa-envelope"></i> Message Agent</button>
                <button id="saveFavoriteBtn" class="btn btn-outline"><i class="fas fa-heart"></i> Save to Favorites</button>
                <button id="leaveReviewBtn" class="btn btn-success"><i class="fas fa-star"></i> Leave a Review</button>
            </div>

            <!-- Past reviews -->
            <div id="modalReviewsCard" style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.15); padding:15px; max-height:400px; overflow-y:auto; margin-top:15px;">
                <h3>Past Reviews</h3>
                <div id="modalPastReviews">
                    <p>Reviews will load here when modal opens.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
<?php echo file_get_contents($_SERVER['DOCUMENT_ROOT'].'/BatEstateExplorer/assets/js/agent_card_logic.js'); ?>
</script>

<?php
        endif;
    }
}
?>
