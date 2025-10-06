<?php
    if (!isset($user)) die('Access denied.');

    require_once __DIR__ . '/../../../../components/notification.php';

    // Show session flash messages (fallback)
    foreach (['success', 'error'] as $type) {
        if (!empty($_SESSION['flash_' . $type])): ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : 'danger' ?>">
                <?= $_SESSION['flash_' . $type]; unset($_SESSION['flash_' . $type]); ?>
            </div>
        <?php endif;
    }

    // Detect active tab
    $tab = $_GET['tab'] ?? 'overview';

    // Initialize defaults
    $listings = $reviews = $company_listings = [];
    $agent_id = $company_id = $avg_rating = $total_reviews = 0;
    $company_name = 'Unknown Company';

    // Fetch agent info
    $stmt = $conn->prepare("SELECT id, company_id FROM agents WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $agent = $stmt->get_result()->fetch_assoc() ?? null;
    $stmt->close();

    if ($agent) {
        $agent_id = (int)$agent['id'];
        $company_id = (int)$agent['company_id'];

        // Fetch company name
        if ($company_id > 0) {
            $stmt = $conn->prepare("SELECT name FROM companies WHERE id = ?");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $company = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $company_name = $company['name'] ?? $company_name;
        }

        // Fetch all properties for this agent
        $stmt = $conn->prepare("
            SELECT 
                p.*,
                sa.id AS sold_by_agent_id,
                su.email AS sold_by_email
            FROM properties p
            LEFT JOIN agents sa ON p.sold_by_agent_id = sa.id
            LEFT JOIN users su ON sa.user_id = su.id
            WHERE p.agent_id = ? OR p.sold_by_agent_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("ii", $agent_id, $agent_id);
        $stmt->execute();
        $listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch all images at once
        if (!empty($listings)) {
            $propertyIds = array_column($listings, 'id');
            $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
            $types = str_repeat('i', count($propertyIds));

            $stmtImg = $conn->prepare("
                SELECT property_id, image_path, is_primary
                FROM property_images
                WHERE property_id IN ($placeholders)
                ORDER BY is_primary DESC, id ASC
            ");
            $stmtImg->bind_param($types, ...$propertyIds);
            $stmtImg->execute();
            $allImages = $stmtImg->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtImg->close();

            // Group images by property_id
            $imagesGrouped = [];
            foreach ($allImages as $img) {
                $imagesGrouped[$img['property_id']][] = [
                    'image_path' => $img['image_path'],
                    'is_primary' => $img['is_primary']
                ];
            }

            // Assign images to listings
            foreach ($listings as &$property) {
                $property['images'] = $imagesGrouped[$property['id']] ?? [];
            }
        }

        // Average rating and total reviews
        $stmt = $conn->prepare("
            SELECT AVG(pr.rating) AS avg_rating, COUNT(*) AS total_reviews
            FROM property_reviews pr
            JOIN properties p ON pr.property_id = p.id
            WHERE p.agent_id = ?
        ");
        $stmt->bind_param("i", $agent_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $avg_rating = $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
        $total_reviews = $row['total_reviews'] ?? 0;
        $stmt->close();

        // Fetch reviews with user info and primary image
        $stmt = $conn->prepare("
            SELECT 
                pr.rating, pr.review_text,
                u.first_name, u.last_name, u.email,
                p.title, pi.image_path
            FROM property_reviews pr
            JOIN users u ON pr.user_id = u.id
            JOIN properties p ON pr.property_id = p.id
            LEFT JOIN property_images pi ON pi.property_id = p.id AND pi.is_primary = 1
            WHERE p.agent_id = ?
            ORDER BY pr.created_at DESC
        ");
        $stmt->bind_param("i", $agent_id);
        $stmt->execute();
        $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Debug log
    echo "<script>console.log('Company ID: {$company_id}, Company Name: " . addslashes($company_name) . "');</script>";

    // Fetch all properties for company
    if ($company_id > 0) {
        $stmt = $conn->prepare("
            SELECT 
                p.id, p.title, p.location, p.price, p.bedrooms, p.bathrooms, p.sqm, p.status, p.created_at,
                a.id AS agent_id, CONCAT(u.first_name, ' ', u.last_name) AS created_by,
                sa.id AS sold_agent_id, CONCAT(su.first_name, ' ', su.last_name) AS sold_by
            FROM properties p
            LEFT JOIN agents a ON p.agent_id = a.id
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN agents sa ON p.sold_by_agent_id = sa.id
            LEFT JOIN users su ON sa.user_id = su.id
            WHERE a.company_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $company_listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Fetch wallet balance
    $walletBalance = 0.00;
    if (isset($user['id'])) {
        $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $walletBalance = (float)($stmt->get_result()->fetch_assoc()['wallet_balance'] ?? 0.00);
        $stmt->close();
    }
    $walletBalanceFormatted = number_format($walletBalance, 2, '.', ',');

    // Last 10 transactions
    $transactions = [];
    $stmt = $conn->prepare("
        SELECT id, property, amount, status, method, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS date
        FROM transactions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) $transactions = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Agent info for wallet tab
    $agentInfo = [
        'name'  => $user['first_name'] . ' ' . $user['last_name'],
        'phone' => $user['phone'] ?? 'N/A'
    ];
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_sidebar.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_overview.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_listing.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_add_listing.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_analytics.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/company_listing.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_review.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_wallet.css">
<script src="https://www.paypal.com/sdk/js?client-id=AS2IFQyy2dcIowcsn3TnY5rSfvzbQbx3KrcGxSeaVBr9XoqYVqNrDR_hPHDXt3gUzhIr1vuUx1m4J1Yt&currency=PHP"></script>

<div class="dashboard-container">
    <!-- Arrow Button for Mobile -->
    <button id="sidebarToggle" class="sidebar-toggle">
    <i class="fa-solid fa-arrow-right"></i>
    </button>

    <div class="left-side">
        <div class="agent-container">
            <!-- Sidebar -->
            <div class="agent-sidebar-wrapper collapsed">
                
                <div class="role">
                    <p>Associate Agent</p>
                </div>

                <div class="agent-sidebar">
                <!-- Sidebar Header -->
                <div class="sidebar-header">
                    <i class="fa-solid fa-user-tie sidebar-icon"></i>
                    <h2>Profile</h2>
                </div>

                <!-- Sidebar Nav -->
                <nav class="sidebar-nav">
                    <ul>
                    <li><a href="?view=associate_profile&tab=overview" class="<?= ($tab === 'overview') ? 'active' : '' ?>"><i class="fa-solid fa-user"></i><span>Overview</span></a></li>
                    <li><a href="?view=associate_profile&tab=my_listings" class="<?= ($tab === 'my_listings') ? 'active' : '' ?>"><i class="fa-solid fa-building"></i><span>My Listings</span></a></li>
                    <li><a href="?view=associate_profile&tab=add_listing" class="<?= ($tab === 'add_listing') ? 'active' : '' ?>"><i class="fa-solid fa-circle-plus"></i><span>Add Listing</span></a></li>
                    <li><a href="?view=associate_profile&tab=analytics" class="<?= ($tab === 'analytics') ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a></li>
                    <li><a href="?view=associate_profile&tab=company_listings" class="<?= ($tab === 'company_listings') ? 'active' : '' ?>"><i class="fa-solid fa-briefcase"></i><span>Company Listings</span></a></li>
                    <li><a href="?view=associate_profile&tab=review_privileges" class="<?= ($tab === 'review_privileges') ? 'active' : '' ?>"><i class="fa-solid fa-star"></i><span>Review Privileges</span></a></li>
                    <li><a href="?view=associate_profile&tab=wallet" class="<?= ($tab === 'wallet') ? 'active' : '' ?>"><i class="fa-solid fa-wallet"></i><span>Wallet</span></a></li>
                    </ul>
                </nav>
                </div>
            </div>
        </div>
    </div>
        
    <div class="right-side">
        <!-- Content Section -->
        <section class="dashboard-content">
            <?php switch ($tab): case 'my_listings': ?>
                <header class="content-header">
                    <h2>My Listings</h2>
                </header>

                <div class="listing-container">
                <?php if (!empty($listings)): ?>
                    <?php foreach ($listings as $property): 
                        // Determine ownership type
                        $isOwnedByAgent = ($property['agent_id'] == $agent_id);
                        $ownership = $isOwnedByAgent ? 'Owned' : 'Shared';

                        // Determine listing type (Owned or Sold by another agent)
                        $listingTypeSelected = !empty($property['sold_by_agent_id']) ? 'sold_by' : 'owned';
                        $ownershipLabel = ($listingTypeSelected === 'sold_by' && !empty($property['sold_by_email'])) 
                            ? "Sold by: " . htmlspecialchars($property['sold_by_email']) 
                            : "Owned";

                        // Grab first uploaded image if available
                        $first_img_src = '';
                        if (!empty($property['images']) && isset($property['images'][0]['image_path'])) {
                            $first_img_src = "/BatEstateExplorer/" . ltrim($property['images'][0]['image_path'], '/');
                        }
                    ?>
                        <div class="listing-card">
                            <div class="listing-thumb">
                                <?php if ($first_img_src): ?>
                                    <img src="<?= htmlspecialchars($first_img_src) ?>" alt="Property Image">
                                <?php else: ?>
                                    <img src="/BatEstateExplorer/assets/images/no-image.png" alt="No Image Available">
                                <?php endif; ?>

                                <!-- Overlay buttons -->
                                <div class="overlay">
                                    <span onclick="openModal(<?= (int)$property['id'] ?>)">Edit</span>
                                    <form id="deleteForm-<?= (int)$property['id'] ?>" 
                                        action="/BatEstateExplorer/public/api/delete_listing.php" 
                                        method="POST" 
                                        style="display:inline;">
                                        <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                                        <button type="button" onclick="deleteListing(<?= (int)$property['id'] ?>)" class="delete-listing-btn">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="details">
                                <div class="info-row"><strong>Title:</strong> <span><?= htmlspecialchars($property['title'] ?? 'N/A') ?></span></div>
                                <div class="info-row"><strong>Location:</strong> <span><?= htmlspecialchars($property['location'] ?? 'N/A') ?></span></div>
                                <div class="info-row"><strong>Price:</strong> <span>₱<?= number_format((float)($property['price'] ?? 0), 2) ?></span></div>
                                <div class="info-row"><strong>Bedrooms:</strong> <span><?= htmlspecialchars($property['bedrooms'] ?? 0) ?></span></div>
                                <div class="info-row"><strong>Bathrooms:</strong> <span><?= htmlspecialchars($property['bathrooms'] ?? 0) ?></span></div>
                                <div class="info-row"><strong>Status:</strong> <span><?= htmlspecialchars($property['status'] ?? 'pending') ?></span></div>
                                <div class="info-row"><strong>Listing Type:</strong> <span><?= $ownershipLabel ?></span></div>
                            </div>
                        </div>

                        <!-- Edit Modal -->
                        <div id="editModal-<?= (int)$property['id'] ?>" class="edit-modal-wrapper2">
                            <div class="edit-modal-content2">
                                <!-- Close Button -->
                                <span class="close2" onclick="closeModal(<?= (int)$property['id'] ?>)">&times;</span>

                                <!-- Title -->
                                <h2>Edit Listing: <?= htmlspecialchars($property['title'] ?? 'Untitled') ?></h2>

                                <form id="editForm-<?= (int)$property['id'] ?>" 
                                    method="POST" 
                                    action="/BatEstateExplorer/public/api/update_property.php" 
                                    enctype="multipart/form-data">
                                    
                                    <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">

                                    <!-- Images on top -->
                                    <div class="form-group2">
                                        <div class="image-gallery2">
                                            <?php if (!empty($property['images'])): ?>
                                                <?php foreach ($property['images'] as $img): ?>
                                                    <?php 
                                                        $imgPath = "/BatEstateExplorer/" . ltrim($img['image_path'], '/'); 
                                                        $isPrimary = isset($img['is_primary']) && $img['is_primary'];
                                                    ?>
                                                    <div class="image-item2">
                                                        <img src="<?= htmlspecialchars($imgPath) ?>" alt="Property Image">
                                                        <input type="hidden" name="existing_images[]" value="<?= htmlspecialchars($img['image_path']) ?>">
                                                        <label class="primary-label2">
                                                            <input type="radio" 
                                                                name="primary_image" 
                                                                value="<?= htmlspecialchars($img['image_path']) ?>" 
                                                                <?= $isPrimary ? 'checked' : '' ?>> Primary
                                                        </label>
                                                        <button type="button" class="remove-img-btn2" 
                                                            onclick="markImageForRemoval(this, '<?= htmlspecialchars($img['image_path']) ?>')">×</button>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p>No images uploaded yet.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-group2">
                                        <label class="newImage" for="newImages-<?= (int)$property['id'] ?>">Add New Images</label>
                                        <input id="newImages-<?= (int)$property['id'] ?>" type="file" name="new_images[]" multiple accept="image/*">
                                    </div>

                                    <!-- Form fields in 2-column grid -->
                                    <div class="form-grid2">
                                        <div class="form-group2">
                                            <label>Property Name</label>
                                            <input type="text" name="title" value="<?= htmlspecialchars($property['title']) ?>" required>
                                        </div>

                                        <div class="form-group2">
                                            <label>Property Type</label>
                                            <select name="property_type" required>
                                                <option value="Property" <?= ($property['property_type'] ?? '') === 'Property' ? 'selected' : '' ?>>Property</option>
                                                <option value="Lot" <?= ($property['property_type'] ?? '') === 'Lot' ? 'selected' : '' ?>>Lot</option>
                                            </select>
                                        </div>

                                        <div class="form-group2">
                                            <label>Location</label>
                                            <select name="location" required>
                                                <option value="">Select Location</option>
                                                <?php 
                                                $locations = [
                                                    "Agoncillo","Alitagtag","Balayan","Balete","Batangas City","Bauan","Calaca","Calatagan","Cuenca",
                                                    "Ibaan","Laurel","Lemery","Lian","Lipa City","Lobo","Mabini","Malvar","Mataasnakahoy","Nasugbu",
                                                    "Padre Garcia","Rosario","San Jose","San Juan","San Luis","San Nicolas","San Pascual",
                                                    "Santa Teresita","Santo Tomas","Taal","Talisay","Tanauan City","Taysan","Tingloy","Tuy"
                                                ];
                                                foreach ($locations as $loc): ?>
                                                    <option value="<?= htmlspecialchars($loc) ?>" <?= ($property['location'] ?? '') === $loc ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($loc) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group2">
                                            <label>Price</label>
                                            <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($property['price']) ?>" required>
                                        </div>

                                        <div class="form-group2">
                                            <label>Bedrooms</label>
                                            <input type="number" name="bedrooms" value="<?= htmlspecialchars($property['bedrooms']) ?>">
                                        </div>

                                        <div class="form-group2">
                                            <label>Bathrooms</label>
                                            <input type="number" name="bathrooms" value="<?= htmlspecialchars($property['bathrooms']) ?>">
                                        </div>

                                        <div class="form-group2">
                                            <label>Lot Size (sqm)</label>
                                            <input type="number" step="0.01" name="lot_size" value="<?= htmlspecialchars($property['lot_size']) ?>">
                                        </div>

                                        <div class="form-group2">
                                            <label>Listing Type</label>
                                            <select name="listing_type" onchange="toggleSoldBy(this, <?= (int)$property['id'] ?>)">
                                                <option value="owned" <?= ($listingTypeSelected === 'owned') ? 'selected' : '' ?>>Owned</option>
                                                <option value="sold_by" <?= ($listingTypeSelected === 'sold_by') ? 'selected' : '' ?>>Sold By</option>
                                            </select>
                                        </div>

                                        <div class="form-group2" id="soldByContainer-<?= (int)$property['id'] ?>" style="display: <?= ($listingTypeSelected === 'sold_by') ? 'block' : 'none' ?>;">
                                            <label>Agent Email</label>
                                            <input type="email" name="sold_by_email" placeholder="Enter agent email" 
                                                value="<?= ($listingTypeSelected === 'sold_by') ? htmlspecialchars($property['sold_by_email'] ?? '') : '' ?>">
                                        </div>

                                        <div class="form-group2 form-full2">
                                            <label>Description</label>
                                            <textarea name="description"><?= htmlspecialchars($property['description'] ?? '') ?></textarea>
                                        </div>

                                        <div class="form-group2 form-full2">
                                            <button type="button" onclick="confirmEdit(<?= (int)$property['id'] ?>)">Update Listing</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="overview-card">
                        <p>No listings found.</p>
                    </div>
                <?php endif; ?>
                </div>
            <?php break; ?>

            <?php case 'add_listing': ?>
                <header class="content-header">
                    <h2>Add Listings</h2>
                </header>
                <p>Drafts:</p>
                <div id="draftContainer" class="draft-container">
                    <?php
                    // Example: fetch drafts from DB
                    $stmt = $conn->prepare("SELECT id, title FROM property_drafts WHERE user_id = ?");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($draft = $result->fetch_assoc()):
                    ?>
                    <div class="draft-card" data-id="<?= $draft['id'] ?>">
                        <span class="delete-draft">&times;</span>
                        <?= htmlspecialchars($draft['title']) ?>
                    </div>
                    <?php endwhile; ?>
                </div>

                <form id="addListingForm" enctype="multipart/form-data">
                <div class="addListing-container">

                    <!-- Card: Basic Info (with property images) -->
                    <div class="form-card">
                    <h3 class="form-card-title">Basic Information</h3>
                    
                    <label for="title"><strong>Property Name</strong></label>
                    <input type="text" id="title" name="title" required>

                    <label for="location"><strong>Location</strong></label>
                    <select id="location" name="location" required>
                        <option value="">Select Location</option>
                        <?php
                        $locations = [
                            "Agoncillo","Alitagtag","Balayan","Balete","Batangas City","Bauan","Calaca","Calatagan","Cuenca",
                            "Ibaan","Laurel","Lemery","Lian","Lipa City","Lobo","Mabini","Malvar","Mataasnakahoy","Nasugbu",
                            "Padre Garcia","Rosario","San Jose","San Juan","San Luis","San Nicolas","San Pascual",
                            "Santa Teresita","Santo Tomas","Taal","Talisay","Tanauan City","Taysan","Tingloy","Tuy"
                        ];

                        foreach ($locations as $loc): ?>
                            <option value="<?= $loc ?>"><?= $loc ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Property Images inside Basic Info -->
                    <label for="images"><strong>Upload Images</strong></label>
                    <div id="imageUploadArea" class="drag-drop-area" tabindex="0">
                        <p>Drag & drop images here or click to browse</p>
                        <input type="file" id="images" accept="image/*" multiple style="display:none;"> 
                    </div>
                    <div id="imagePreview" class="image-preview" aria-live="polite"></div>
                    </div>

                    <!-- Card: Property Details -->
                    <div class="form-card">
                    <h3 class="form-card-title">Property Details</h3>

                    <label for="price"><strong>Price (₱)</strong></label>
                    <input type="number" id="price" name="price" min="0" step="0.01" required>

                    <label for="lot_size"><strong>Lot Size (sqm)</strong></label>
                    <input type="number" id="lot_size" name="lot_size" min="0" step="0.01" required>

                    <label for="property_type"><strong>Property Type</strong></label>
                    <select id="property_type" name="property_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="Property">Property</option>
                        <option value="Lot">Lot</option>
                    </select>

                    <label for="bedrooms"><strong>Bedrooms</strong></label>
                    <input type="number" id="bedrooms" name="bedrooms" min="0" step="1">

                    <label for="bathrooms"><strong>Bathrooms</strong></label>
                    <input type="number" id="bathrooms" name="bathrooms" min="0" step="1">
                    </div>

                    <!-- Card: Description (with submit button) -->
                    <div class="form-card">
                    <h3 class="form-card-title">Description</h3>
                    <label for="description"><strong>Description</strong></label>
                    <textarea id="description" name="description" rows="4" required></textarea>

                    <!-- Submit Button moved here -->
                    <div class="add_listing_actions">
                        <button type="button" id="saveDraftBtn" class="btn-draft" data-url="/BatEstateExplorer/public/api/save_draft.php">
                            Save Draft
                        </button>
                        <button type="button" id="openListingModalBtn" class="btn-submit">Save Listing (Balance: ₱<?= $walletBalanceFormatted ?? '0.00' ?>)</button>
                    </div>
                    </div>

                </div>
                </form>

                <!-- Listing Fee Modal -->
                <div id="listingFeeModal" class="deposit-modal" onclick="closeListingFeeModal(event)">
                    <div class="modal-content3" onclick="event.stopPropagation()">
                        <span class="close" onclick="closeListingFeeModal()">&times;</span>
                        <h2 class="modal-title">Listing Fee Payment</h2>
                        
                        <div class="modal-body">
                            <div class="wallet-info">
                                <p><strong>Wallet Balance:</strong> PHP <span id="agentWalletBalance"><?= $walletBalance ?></span></p>
                                <p><strong>Listing Fee:</strong> PHP 20</p>
                            </div>

                            <div class="note">
                                <small>
                                    Please note: If the property is rejected by the admin, the listing fee of PHP 20 will be refunded to your wallet, minus a 2% processing fee.
                                </small>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button id="payListingFeeBtn" class="btn btn-success">
                                <span class="btn-text">Pay Listing Fee & Submit</span>
                                <span class="spinner" style="display:none;">
                                    <svg width="20" height="20" viewBox="0 0 50 50">
                                        <circle cx="25" cy="25" r="20" stroke="#fff" stroke-width="5" fill="none" stroke-linecap="round">
                                            <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" from="0 25 25" to="360 25 25"/>
                                        </circle>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

            <?php break; ?>

            <?php case 'analytics': ?>
                <header class="content-header">
                    <h2>Analytics</h2>
                </header>

                <div class="analytics-wrapper">

                    <!-- Analytics Summary Card Wrapper (centered) -->
                    <div class="analytics-summary-wrapper">
                        <div class="card analytics-summary">
                            <h2>Performance Analytics</h2>
                            <h1><?= $avg_rating ?></h1>
                            <div class="stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= round($avg_rating) ? 'filled' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <p><?= $total_reviews ?> Review<?= $total_reviews != 1 ? 's' : '' ?></p>
                        </div>
                    </div>

                    <!-- Property Reviews Card -->
                    <h3>Property Reviews</h3>
                    <div class="card analytics-reviews review-cards">
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $r): 
                                $user_name = trim($r['first_name'] . ' ' . $r['last_name']);
                                $image_path = !empty($r['image_path']) 
                                    ? "/BatEstateExplorer/" . $r['image_path'] 
                                    : '/assets/images/default.jpg';
                            ?>
                            <div class="review-card">
                                <div class="review-left">
                                    <div class="review-header">
                                        <h4><?= htmlspecialchars($user_name) ?></h4>
                                        <p class="review-email"><?= htmlspecialchars($r['email']) ?>:</p>
                                        <span class="review-title"><?= htmlspecialchars($r['title']) ?></span>
                                    </div>
                                    <div class="review-stars">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?= $i <= $r['rating'] ? 'filled' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="review-text"><?= nl2br(htmlspecialchars($r['review_text'])) ?></div>
                                </div>
                                <div class="review-right">
                                    <img src="<?= htmlspecialchars($image_path) ?>" alt="<?= htmlspecialchars($r['title']) ?>">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-reviews">No reviews found for your properties.</p>
                        <?php endif; ?>
                    </div>

                </div>
            <?php break; ?>

            <?php case 'company_listings': ?>
                <header class="content-header">
                    <h2>Company Listings</h2>
                </header>
                <p class="company">Company: <?= htmlspecialchars($company_name) ?></p>

                <div class="company-listings-container">
                    <?php foreach ($company_listings as $row): ?>
                        <div class="company-listing-card">
                            <p class="listing-title"><?= htmlspecialchars($row['title']); ?></p>
                            <p><?= htmlspecialchars($row['location']); ?></p>
                            <p>Price: ₱<?= number_format($row['price'], 2); ?></p>
                            <p>Bedrooms: <?= (int)$row['bedrooms']; ?></p>
                            <p>Bathrooms: <?= (int)$row['bathrooms']; ?></p>
                            <p>Size (sqm): <?= number_format($row['sqm'], 2); ?></p>
                            <p><?= ucfirst($row['status']); ?></p>
                            <p>By: <?= !empty($row['created_by']) ? htmlspecialchars($row['created_by']) : 'N/A'; ?></p>
                            <p>Sold By: <?= !empty($row['sold_by']) ? htmlspecialchars($row['sold_by']) : 'N/A'; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php break; ?>

            <?php case 'review_privileges': ?>
                <header class="content-header">
                    <h2>Review Privileges</h2>
                </header>

                <div class="privilege-container">
                    <div class="privilege-card">
                        <label for="searchEmail">Search Client by Email:</label>
                        <input type="email" id="searchEmail" placeholder="Enter email..." />
                        <button id="searchBtn" onclick="searchClient()">Search</button>
                    </div>
                </div>

                <!-- Grant Privilege Section (disabled by default) -->
                <div id="grantPrivilegeSection" class="grant-privilege" style="opacity:0.5; pointer-events:none;">
                    <h2>Grant Review Privilege</h2>
                    <p id="userNameEmail"></p>

                    <h3>Select a Property</h3>
                    <div id="propertyList" class="property-list">
                        <?php if (!empty($listings)): ?>
                            <?php foreach ($listings as $property): 
                                // Check if property has images
                                $first_img_src = !empty($property['images']) && !empty($property['images'][0]['image_path'])
                                    ? "/BatEstateExplorer/" . $property['images'][0]['image_path'] 
                                    : "/BatEstateExplorer/assets/images/no-image.png"; // fallback placeholder
                            ?>
                                <div class="property-card" onclick="selectProperty(this, <?= $property['id'] ?>)">
                                    <img src="<?= $first_img_src ?>" alt="Property Image">
                                    <div class="overlay">
                                        <h4><?= htmlspecialchars($property['title']) ?></h4>
                                        <p><?= htmlspecialchars($property['location']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No properties found.</p>
                        <?php endif; ?>
                    </div>

                    <button id="givePrivilegeBtn" onclick="givePrivilege()" disabled>Give Privilege</button>
                </div>
            <?php break; ?>

            <?php case 'wallet': ?>
                <header class="content-header">
                    <h2>Agent Wallet</h2>
                </header>

                <!-- Wallet Balance -->
                <div class="wallet-balance-section mb-4 d-flex align-items-center justify-content-between" style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem;">
                    <h3>PHP <span id="walletBalance"><?= number_format($walletBalance, 2) ?></span></h3>
                    <button class="btn btn-success btn-circle" onclick="openDepositModal()">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>

                <!-- Agent Info -->
                <div class="agent-info p-3 mb-4" style="border: 1px solid #ddd; border-radius: 8px;">
                    <p><strong>Name:</strong> <?= htmlspecialchars($agentInfo['name']) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($agentInfo['phone']) ?></p>
                    <p><strong>Email:</strong> agent@personal.example.com</p>
                </div>

                <!-- Transaction History -->
                <h4 class="mt-5">Transaction History</h4>
                <div class="transaction-cards-container">
                    <?php if(!empty($transactions)): ?>
                        <?php foreach($transactions as $tx): ?>
                            <div class="transaction-card">
                                <p class="transaction-property"><?= htmlspecialchars($tx['property']); ?></p>
                                <p><strong>Date:</strong> <?= htmlspecialchars($tx['date']); ?></p>
                                <p><strong>Status:</strong> <?= ucfirst($tx['status']); ?></p>
                                <p><strong>Payment Method:</strong> <?= htmlspecialchars($tx['method']); ?></p>
                                <p class="transaction-amount <?= htmlspecialchars($tx['property']) === 'Deposit' ? 'positive' : 'negative'; ?>">
                                    ₱<?= number_format($tx['amount'], 2); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-transactions text-center">No transactions yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Deposit Modal -->
                <div id="depositModal" class="deposit-modal" style="display:none;" onclick="this.style.display='none'">
                    <div class="modal-content" onclick="event.stopPropagation()">
                        <span class="close" onclick="this.closest('#depositModal').style.display='none'">&times;</span>
                        <h2>Deposit Funds</h2>
                        <input type="text" id="selectedAmount" class="form-control mb-3" placeholder="Selected amount" disabled>
                        <div class="deposit-amounts mb-3">
                            <?php foreach ([50, 100, 200, 400, 600, 1000] as $amt): ?>
                                <button type="button" class="deposit-amount-btn" data-amount="<?= $amt ?>">PHP <?= $amt ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div id="paypal-button-container"></div>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const modal = document.getElementById('depositModal');
                    const balanceEl = document.getElementById('walletBalance');
                    const selectedInput = document.getElementById('selectedAmount');
                    const walletLimit = 10000;
                    const tableBody = document.querySelector('.transaction-cards-container');

                    window.openDepositModal = () => modal.style.display='flex';
                    window.closeDepositModal = (e) => { if(!e || e.target === modal) modal.style.display='none'; }

                    document.querySelectorAll('.deposit-amount-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const amount = parseFloat(btn.dataset.amount);
                            const current = parseFloat(balanceEl.innerText.replace(/,/g,'')) || 0;
                            if(current + amount > walletLimit){
                                alert(`Deposit exceeds wallet limit of PHP ${walletLimit}.`);
                                selectedInput.value = '';
                                return;
                            }
                            selectedInput.value = amount;
                        });
                    });

                    paypal.Buttons({
                        style: { layout:'vertical', color:'blue', shape:'pill', label:'pay' },

                        createOrder: function(data, actions) {
                            const amount = parseFloat(selectedInput.value);
                            if(!amount){ alert('Select an amount first'); return; }
                            const current = parseFloat(balanceEl.innerText.replace(/,/g,'')) || 0;
                            if(current + amount > walletLimit){ alert('Deposit exceeds wallet limit'); return; }
                            return actions.order.create({ purchase_units:[{ amount:{ value: amount.toFixed(2) } }] });
                        },

                        onApprove: function(data, actions){
                            return actions.order.capture().then(details => {
                                const amount = parseFloat(selectedInput.value);
                                let current = parseFloat(balanceEl.innerText.replace(/,/g,'')) || 0;
                                let newBalance = current + amount;
                                if(newBalance > walletLimit){ alert('Deposit exceeds wallet limit'); return; }

                                balanceEl.innerText = newBalance.toLocaleString('en-PH',{minimumFractionDigits:2});

                                fetch('/BatEstateExplorer/public/api/deposit.php',{
                                    method:'POST',
                                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                                    body:`amount=${encodeURIComponent(amount)}`
                                }).then(res=>res.json())
                                .then(data=>{
                                    if(data.success){
                                        alert('Deposit successful! Paid by: '+details.payer.name.given_name);
                                        selectedInput.value=''; closeDepositModal();

                                        // Add transaction card
                                        const now = new Date();
                                        const formatted = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+
                                            String(now.getDate()).padStart(2,'0')+' '+String(now.getHours()).padStart(2,'0')+':' +
                                            String(now.getMinutes()).padStart(2,'0')+':'+String(now.getSeconds()).padStart(2,'0');

                                        const div = document.createElement('div');
                                        div.className = 'transaction-card';
                                        div.innerHTML = `
                                            <p class="transaction-property">Deposit</p>
                                            <p><strong>Date:</strong> ${formatted}</p>
                                            <p><strong>Status:</strong> Completed</p>
                                            <p><strong>Payment Method:</strong> PayPal</p>
                                            <p class="transaction-amount positive">₱${amount.toLocaleString('en-PH',{minimumFractionDigits:2})}</p>
                                        `;
                                        const placeholder = tableBody.querySelector('.no-transactions');
                                        if(placeholder) tableBody.innerHTML='';
                                        tableBody.prepend(div);

                                        // Keep last 10
                                        while(tableBody.children.length>10) tableBody.removeChild(tableBody.lastChild);
                                    } else alert('Deposit saved to PayPal but failed to update wallet: '+(data.error||'Unknown'));
                                }).catch(err=>{ console.error(err); alert('Deposit saved to PayPal but failed to update wallet.'); });
                            });
                        },

                        onError: function(err){ console.error(err); alert('PayPal transaction error.'); }

                    }).render('#paypal-button-container');
                });
                </script>
            <?php break; ?>

            <?php default:
                // Overview Tab
                $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                if ($fullName === '') $fullName = 'Agent';?>

                <header class="content-header">
                    <h2>Profile Overview</h2>
                </header>

                <button id="editProfileBtn">Edit Profile</button>
                <div class="overview-container">
                    <div class="profile-view">

                        <!-- Basic Info -->
                        <div class="overview-card">
                            <h3>Basic Information</h3>
                            <div class="info-row"><strong>Name:</strong> <span><?= htmlspecialchars($fullName) ?></span></div>
                            <div class="info-row"><strong>Email:</strong> <span><?= htmlspecialchars($user['email'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>Phone:</strong> <span><?= htmlspecialchars($user['phone'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>Address:</strong> <span><?= htmlspecialchars($user['address'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>User Type:</strong> <span><?= htmlspecialchars($user['user_type'] ?? '-') ?></span></div>
                        </div>

                        <!-- Education & Training -->
                        <div class="overview-card">
                            <h3>Education & Training</h3>
                            <div class="info-row"><strong>Education:</strong> <span><?= htmlspecialchars($user['education'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>School:</strong> <span><?= htmlspecialchars($user['school'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>Course:</strong> <span><?= htmlspecialchars($user['course'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>Graduation Year:</strong> <span><?= htmlspecialchars($user['graduation_year'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>Certifications:</strong> <span><?= htmlspecialchars($user['certifications'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>Training:</strong> <span><?= htmlspecialchars($user['training'] ?? '-') ?></span></div>
                        </div>

                        <!-- Account Status -->
                        <div class="overview-card">
                            <h3>Account Status</h3>
                            <div class="info-row"><strong>Status:</strong> <span><?= htmlspecialchars($user['status'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>Admin Notes:</strong> <span><?= nl2br(htmlspecialchars($user['admin_notes'] ?? '-')) ?></span></div>
                            <div class="info-row"><strong>Created At:</strong> <span><?= htmlspecialchars($user['created_at'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>Last Updated:</strong> <span><?= htmlspecialchars($user['updated_at'] ?? '-') ?></span></div>
                        </div>

                        <!-- Professional Details -->
                        <div class="overview-card">
                            <h3>Professional Details</h3>
                            <div class="info-row"><strong>Agent Type:</strong> <span><?= ($user['user_type'] ?? '') === 'associate_agent' ? 'Associate Agent' : (($user['user_type'] ?? '') === 'direct_agent' ? 'Direct Agent' : '-') ?></span></div>
                            <div class="info-row"><strong>Broker ID:</strong> <span><?= htmlspecialchars($user['broker_id'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>License Number:</strong> <span><?= htmlspecialchars($user['license_number'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>Years of Experience:</strong> <span><?= htmlspecialchars($user['experience_years'] ?? '-') ?></span></div>
                            <div class="info-row"><strong>Specialization:</strong> <span><?= !empty($user['specialization']) ? htmlspecialchars(is_array($tmp = json_decode($user['specialization'], true)) ? implode(', ', $tmp) : $user['specialization']) : '-' ?></span></div>
                            <div class="info-row"><strong>Bio:</strong> <span><?= nl2br(htmlspecialchars($user['bio'] ?? '-')) ?></span></div>
                        </div>

                        <!-- Danger Zone in new grid row -->
                        <div class="overview-card danger-zone">
                            <h3>Danger Zone</h3>
                            <p class="danger-note">⚠️ Once deleted, this account <strong>cannot be recovered</strong>. Please proceed with caution.</p>
                            <button type="button" id="openDeleteModal" class="delete-btn-overview">Delete Account</button>
                        </div>
                    </div>  

                    <div id="profileMessage"></div>
                </div>
                <!-- Edit Form (full width) -->
                <div id="editModal" class="modal">
                <div class="modal-content">
                    <h4>Edit Profile</h4>
                    <form id="profileForm" class="profile-edit" method="POST" action="/BatEstateExplorer/public/api/save_profile.php">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>

                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

                    <label for="address">Address</label>
                    <textarea name="address" id="address" rows="3"><?= htmlspecialchars($user['address']) ?></textarea>

                    <div style="display:flex; justify-content:center; gap:0.5rem; flex-wrap:wrap;">
                        <button type="submit">Save Changes</button>
                        <button type="button" id="cancelEditBtn">Cancel</button>
                    </div>
                    </form>
                </div>
                </div>

                <!-- Modal (outside container so it overlays everything) -->
                <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <h4>Confirm Account Deletion</h4>
                    <p>Are you sure you want to delete this agent account? This action cannot be undone.</p>
                    <form id="deleteAgentForm" method="POST" action="/BatEstateExplorer/public/api/delete_agent.php">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                    <div style="display:flex; justify-content:center; gap:0.5rem; flex-wrap:wrap;">
                        <button type="submit" id="confirmDeleteBtn" class="delete-btn">Yes, Delete</button>
                        <button type="button" id="cancelDeleteBtn" class="cancel-btn">Cancel</button>
                    </div>
                    <div id="deleteSpinner" class="spinner" style="display:none;">
                        <div class="loader"></div>
                        <span>Deleting account...</span>
                    </div>
                    </form>
                </div>
                </div>

            <?php endswitch; ?>
        </section>
    </div>
        
</div>

<script src="/BatEstateExplorer/assets/js/agent_profile.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
    // Close buttons for modals
    const closeButtons = document.querySelectorAll(
        "#cancelEditBtn, #cancelDeleteBtn"
    );

    // Add reload on close
    closeButtons.forEach((btn) => {
        btn.addEventListener("click", function () {
        location.reload();
        });
    });

    // If user clicks outside of the modal, close & reload
    const modals = document.querySelectorAll("#editModal, #deleteModal");
    modals.forEach((modal) => {
        modal.addEventListener("click", function (e) {
        if (e.target === modal) {
            location.reload();
        }
        });
    });
    });
</script>