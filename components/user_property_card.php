<?php
    if (!function_exists('render_property_card')) {
        function render_property_card(array $property, bool $modalOnly = false) {
            $conn = $GLOBALS['conn'] ?? null;
            if (!$conn) {
                echo "<p style='color:red'>Database connection not found.</p>";
                return;
            }

            // --- Safely get property ID ---
            $propertyId = isset($property['id']) ? (int)$property['id'] : 0;

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

            // --- Fetch agent info ---
            $agent = null;
            $listedAgentId = $property['listed_by_agent_id'] ?? null;

            if ($listedAgentId) {
                // Step 1: Get the user_id from agents table
                $stmtAgentTable = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
                if ($stmtAgentTable) {
                    $stmtAgentTable->bind_param("i", $listedAgentId);
                    $stmtAgentTable->execute();
                    $resAgentTable = $stmtAgentTable->get_result();
                    $agentRow = $resAgentTable->fetch_assoc();
                    $stmtAgentTable->close();

                    $agentUserId = $agentRow['user_id'] ?? null;

                    if ($agentUserId) {
                        // Step 2: Get agent info from users table
                        $stmtUser = $conn->prepare("SELECT id, first_name, last_name, profile_image_path FROM users WHERE id = ? AND user_type IN ('direct_agent','associate_agent')");
                        if ($stmtUser) {
                            $stmtUser->bind_param("i", $agentUserId);
                            $stmtUser->execute();
                            $resUser = $stmtUser->get_result();
                            $agent = $resUser->fetch_assoc();
                            $stmtUser->close();
                        }
                    }
                }
            }

            //message id
            $userId = null;
            $listedByAgentId = $property['listed_by_agent_id'] ?? null;
            if ($listedByAgentId) {
                $stmtAgent = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
                if ($stmtAgent) {
                    $stmtAgent->bind_param("i", $listedByAgentId);
                    $stmtAgent->execute();
                    $resAgent = $stmtAgent->get_result();
                    if ($rowAgent = $resAgent->fetch_assoc()) {
                        $userId = (int)$rowAgent['user_id']; // <-- THIS is the user_id
                    }
                    $stmtAgent->close();
                }
            }

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

            // --- Check if current user can review THIS property ---
            $canReview = false;
            $uid = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
            if ($uid) {
                $uid = (int)$uid;
                $stmtPriv = $conn->prepare("SELECT privileges FROM users WHERE id = ? LIMIT 1");
                if ($stmtPriv) {
                    $stmtPriv->bind_param("i", $uid);
                    $stmtPriv->execute();
                    $resPriv = $stmtPriv->get_result();
                    if ($rowPriv = $resPriv->fetch_assoc()) {
                        $privileges = json_decode($rowPriv['privileges'], true) ?: [];
                        $canReview = in_array($propertyId, $privileges, true) || in_array((string)$propertyId, $privileges, true);
                    }
                    $stmtPriv->close();
                }
            }

            // --- Sanitize fields ---
            $image = htmlspecialchars($property['images'][0] ?? '/BatEstateExplorer/assets/images/bg4.jpg');
            $title = htmlspecialchars($property['title'] ?? '');
            $location = htmlspecialchars($property['location'] ?? '');
            $price = number_format((float)($property['price'] ?? 0), 2);
            $bedrooms = (int)($property['bedrooms'] ?? 0);
            $bathrooms = (int)($property['bathrooms'] ?? 0);
            $createdAt = strtotime($property['created_at'] ?? 'now');

            // --- Render card / modal ---
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
    </div>
</div>
<?php
        endif;

        // --- Include modal only once ---
        if (!defined('PROPERTY_MODAL_INCLUDED')):
            define('PROPERTY_MODAL_INCLUDED', true);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- Combined Modal -->
<div id="propertyModal" class="custom-modal" style="display:none;">
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
      <div class="details">
        <?php if ($agent): 
          $profileImage = !empty($agent['profile_image_path'])
              ? '/BatEstateExplorer/storage/uploads/profile_images/' . basename($agent['profile_image_path'])
              : '/BatEstateExplorer/assets/default-avatar.png';
        ?>
        <section class="agent-info">
          <a href="/BatEstateExplorer/public/agent_page.php?agent_id=<?= (int)$agent['id'] ?>" target="_blank" style="display:flex; align-items:center; gap:12px; text-decoration:none; color:inherit;">
            <img class="agent-avatar" src="<?= htmlspecialchars($profileImage) ?>" alt="Agent Avatar">
            <span class="agent-name"><?= htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']) ?></span>
          </a>
        </section>
        <?php endif; ?>

        <section><span class="label">Price:</span> <span class="value price"><?= $price ?></span></section>
        <section><span class="label">Location:</span> <span class="value location"><?= $location ?></span></section>
        <section><span class="label">Property Type:</span> <span class="value property-type"><?= htmlspecialchars($property['property_type'] ?? '') ?></span></section>
        <section><span class="label">Bedrooms:</span> <span class="value bedrooms"><?= $bedrooms ?></span></section>
        <section><span class="label">Bathrooms:</span> <span class="value bathrooms"><?= $bathrooms ?></span></section>
        <section><span class="label">Lot Size:</span> <span class="value lot_size"><?= htmlspecialchars($property['lot_size'] ?? '') ?></span></section>
        <section><span class="label">Date Uploaded:</span> <span class="value date_uploaded"><?= date('M d, Y', $createdAt) ?></span></section>

        <section class="desc">
          <span class="label">Description:</span>
          <div class="property-description"><?= htmlspecialchars($property['description'] ?? '') ?></div>
        </section>
      </div>

      <!-- Modal Actions -->
      <div class="modal-actions">
        <?php if ($userId): ?>
          <a href="/BatEstateExplorer/public/message.php?user_id=<?= $userId ?>" target="_blank" class="btn btn-primary">
            <i class="fas fa-envelope"></i> Message
          </a>
        <?php endif; ?>

        <button id="saveFavoriteBtn" class="btn btn-outline">
          <i class="fas fa-heart"></i> Save
        </button>

        <button id="leaveReviewBtn" class="btn btn-success" style="display:<?= $canReview ? 'inline-flex' : 'none' ?>;">
          <i class="fas fa-star"></i> Review
        </button>
      </div>

      <!-- Reviews inside the same scrollable column -->
      <div class="modal-review">
        <div class="modal-reviews">
          <h3>Reviews</h3>
          <div id="modalPastReviews">
            <p>Reviews will load here when modal opens.</p>
          </div>
        </div>
      </div>

      <!-- More Properties by this Agent -->
      <div class="agent-other-properties">
        <h3>More from this Agent</h3>
        <div class="agent-properties-list">
          <?php
            if ($listedAgentId) {
              $stmtOther = $conn->prepare("
                SELECT p.id, p.title, COALESCE(pi.image_path, '') AS image_path
                FROM properties p
                LEFT JOIN property_images pi ON p.id = pi.property_id
                WHERE p.listed_by_agent_id = ?
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT 6
              ");
              $stmtOther->bind_param("i", $listedAgentId);
              $stmtOther->execute();
              $resOther = $stmtOther->get_result();

              if ($resOther->num_rows > 0):
                while ($p = $resOther->fetch_assoc()):
                  $img = !empty($p['image_path'])
                    ? '/' . ltrim($p['image_path'], '/')
                    : '/BatEstateExplorer/assets/default-property.jpg';
          ?>
                  <div class="agent-property-card" data-property-id="<?= (int)$p['id'] ?>">
                    <img src="<?= htmlspecialchars($img) ?>" alt="Property Image">
                    <span class="property-title"><?= htmlspecialchars($p['title']) ?></span>
                  </div>
          <?php
                endwhile;
              else:
                echo "<p style='font-size:0.9rem;color:#666;'>No listings yet.</p>";
              endif;
              $stmtOther->close();
            }
          ?>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="modal" hidden>
  <div class="reviewModal-content">
    <!-- Close Button -->
    <button class="modal-close close">&times;</button>

    <!-- Modal Header -->
    <header class="modal-header">
      <h3>Post a Review</h3>
    </header>

    <!-- Review Form -->
    <form id="postReviewForm" class="modal-form">
      <input type="hidden" name="property_id" id="reviewPropertyId" value="">

      <!-- Rating Stars -->
      <div class="rating-stars">
        <span data-value="1">&#9733;</span>
        <span data-value="2">&#9733;</span>
        <span data-value="3">&#9733;</span>
        <span data-value="4">&#9733;</span>
        <span data-value="5">&#9733;</span>
      </div>

      <!-- Review Textarea -->
      <textarea name="review_text" placeholder="Write your review..." rows="4" required></textarea>

      <!-- Submit Button -->
      <button type="submit" class="btn btn-success">Post Review</button>
    </form>
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
