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

    <div class="property-image" style="position:relative; overflow:hidden;">
        <img src="<?= $image ?>" alt="Property Image" style="width:100%; transition:opacity 0.5s ease;">
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

        // --- Include modal only once ---
        if (!defined('AGENT_PROPERTY_MODAL_INCLUDED')):
            define('AGENT_PROPERTY_MODAL_INCLUDED', true);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<div id="propertyModal" class="modal" style="display:none;">
    <div class="modal-content" style="display:flex; gap:20px; max-width:1000px; margin:auto;">
        <span class="modal-close">&times;</span>

        <!-- Left: Property Details -->
        <div class="modal-body" style="flex:2;">
            <div class="modal-image">
                <div class="modal-swiper-container">
                    <div class="swiper-wrapper" id="modalImageWrapper"></div>
                    <div class="modal-swiper-button-next"></div>
                    <div class="modal-swiper-button-prev"></div>
                    <div class="modal-swiper-pagination"></div>
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
                <!-- No action buttons for agents -->
            </div>
        </div>

        <!-- Right: Past Reviews (read-only) -->
        <div id="modalReviewsCard" style="
            flex:1;
            background:#fff;
            border-radius:12px;
            box-shadow:0 4px 12px rgba(0,0,0,0.15);
            padding:15px;
            max-height:600px;
            overflow-y:auto;">
            <h3 style="margin-top:0;">Past Reviews</h3>
            <div id="modalPastReviews">
                <p>Reviews will load here when modal opens.</p>
            </div>
        </div>

    </div>
</div>
<?php
        endif;
    }
}
?>
