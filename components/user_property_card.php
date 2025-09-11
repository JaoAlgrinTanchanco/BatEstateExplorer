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
        $reviewsSql = "SELECT r.*, u.first_name, u.last_name
                       FROM property_reviews r
                       INNER JOIN users u ON r.user_id = u.id
                       WHERE r.property_id = ?
                       ORDER BY r.created_at DESC";
        $stmt = $conn->prepare($reviewsSql);
        if ($stmt) {
            $stmt->bind_param("i", $propertyId);
            $stmt->execute();
            $resReviews = $stmt->get_result();
            $property['past_reviews'] = $resReviews->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        $image = htmlspecialchars($property['images'][0]);
        $title = htmlspecialchars($property['title'] ?? '');
        $location = htmlspecialchars($property['location'] ?? '');
        $price = number_format((float)($property['price'] ?? 0), 2);
        $bedrooms = (int)($property['bedrooms'] ?? 0);
        $bathrooms = (int)($property['bathrooms'] ?? 0);
        $createdAt = strtotime($property['created_at'] ?? 'now');

        // --- Render card if not modal-only ---
        if (!$modalOnly):
?>
<div class="property-card"
     data-id="<?= $propertyId ?>"
     data-location="<?= strtolower($location) ?>"
     data-type="<?= htmlspecialchars($property['property_type'] ?? '') ?>"
     data-price="<?= (int)($property['price'] ?? 0) ?>"
     data-bedrooms="<?= $bedrooms ?>"
     data-bathrooms="<?= $bathrooms ?>"
     data-size="<?= (int)($property['sqm'] ?? 0) ?>"
     data-date="<?= $createdAt ?>"
     data-image="<?= $image ?>">
    <div class="property-image">
        <img src="<?= $image ?>" alt="Property Image">
    </div>
    <div class="property-content">
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

        // --- Modal only once per page ---
        if (!defined('PROPERTY_MODAL_INCLUDED')):
            define('PROPERTY_MODAL_INCLUDED', true);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<div id="propertyModal" class="modal" style="display:none;">
    <div class="modal-content" style="display:flex; gap:20px; max-width:1000px; margin:auto;">
        <span class="modal-close">&times;</span>
        <div class="modal-body" style="flex:2;">
            <div class="modal-image">
                <div class="swiper modal-swiper">
                    <div class="swiper-wrapper" id="modalImageWrapper"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
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
                    <button class="btn btn-primary message-agent-btn"><i class="fas fa-envelope"></i> Message Agent</button>
                    <button id="saveFavoriteBtn" class="btn btn-outline"><i class="fas fa-heart"></i> Save to Favorites</button>
                    <button id="leaveReviewBtn" class="btn btn-success" style="display:none;"><i class="fas fa-star"></i> Leave a Review</button>
                </div>
            </div>
        </div>
        <div id="modalReviewsCard" style="flex:1; background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.15); padding:15px; max-height:600px; overflow-y:auto;">
            <h3 style="margin-top:0;">Past Reviews</h3>
            <div id="modalPastReviews"><p>Reviews will load here when modal opens.</p></div>
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

<script>
<?php echo file_get_contents($_SERVER['DOCUMENT_ROOT'].'/BatEstateExplorer/assets/js/property_card_logic.js'); ?>
</script>
<?php
        endif;
    }
}
?>
