<?php
    if (!isset($user)) {die('Access denied.');}

    require_once __DIR__ . '/../../../../components/notification.php';

    // 🔹 Show session flash messages (deprecated since you're moving to centralized notifications, 
    // but leaving here for fallback)
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
    $listings      = [];
    $reviews       = [];
    $agent_id      = 0;
    $company_id    = 0;
    $company_name  = 'Unknown Company';
    $avg_rating    = 0;
    $total_reviews = 0;

    // 🔹 Fetch agent (id + company_id in one go)
    $stmt = $conn->prepare("SELECT id, company_id FROM agents WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $res   = $stmt->get_result();
    $agent = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($agent) {
        $agent_id   = (int)$agent['id'];
        $company_id = (int)$agent['company_id'];

        // 🔹 Fetch company info
        if ($company_id > 0) {
            $companyStmt = $conn->prepare("SELECT name FROM companies WHERE id = ?");
            $companyStmt->bind_param("i", $company_id);
            $companyStmt->execute();
            $companyRes = $companyStmt->get_result();
            $company    = $companyRes->fetch_assoc();
            $companyStmt->close();

            if ($company) {
                $company_name = $company['name'];
            }
        }

        // 🔹 Fetch all properties for this agent
        $propsStmt = $conn->prepare("
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
        $propsStmt->bind_param("ii", $agent_id, $agent_id);
        $propsStmt->execute();
        $res      = $propsStmt->get_result();
        $listings = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $propsStmt->close();

        // 🔹 Attach ALL images (not just primary) to each property
        $stmtImg = $conn->prepare("
            SELECT image_path, is_primary
            FROM property_images 
            WHERE property_id = ? 
            ORDER BY is_primary DESC, id ASC
        ");
        foreach ($listings as &$property) {
            $stmtImg->bind_param("i", $property['id']);
            $stmtImg->execute();
            $resImg = $stmtImg->get_result();

            $property['images'] = $resImg && $resImg->num_rows 
                ? $resImg->fetch_all(MYSQLI_ASSOC) 
                : [];
        }
        $stmtImg->close();

        // 🔹 Fetch average rating + total reviews
        $stmt = $conn->prepare("
            SELECT AVG(pr.rating) AS avg_rating, COUNT(*) AS total_reviews
            FROM property_reviews pr
            JOIN properties p ON pr.property_id = p.id
            WHERE p.agent_id = ?
        ");
        $stmt->bind_param("i", $agent_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $avg_rating    = $row && $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
        $total_reviews = $row['total_reviews'] ?? 0;
        $stmt->close();

        // 🔹 Fetch reviews with user info and primary image
        $stmt = $conn->prepare("
            SELECT 
                pr.rating, 
                pr.review_text, 
                u.first_name, 
                u.last_name, 
                u.email, 
                p.title, 
                pi.image_path
            FROM property_reviews pr
            JOIN users u ON pr.user_id = u.id
            JOIN properties p ON pr.property_id = p.id
            LEFT JOIN property_images pi 
                ON pi.property_id = p.id AND pi.is_primary = 1
            WHERE p.agent_id = ?
            ORDER BY pr.created_at DESC
        ");
        $stmt->bind_param("i", $agent_id);
        $stmt->execute();
        $res     = $stmt->get_result();
        $reviews = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }

    // 🔹 Debug log
    echo "<script>console.log('Company ID: {$company_id}, Company Name: " . addslashes($company_name) . "');</script>";

    // 🔹 Fetch all properties from the same company
    $company_listings = [];
    if ($company_id > 0) {
        $propsStmt = $conn->prepare("
            SELECT 
            p.id, 
            p.title, 
            p.location, 
            p.price, 
            p.bedrooms, 
            p.bathrooms, 
            p.sqm, 
            p.status, 
            p.created_at,

            -- Creator
            a.id AS agent_id, 
            CONCAT(u.first_name, ' ', u.last_name) AS created_by,

            -- Sold by
            sa.id AS sold_agent_id, 
            CONCAT(su.first_name, ' ', su.last_name) AS sold_by

        FROM properties p
        LEFT JOIN agents a 
            ON p.agent_id = a.id
        LEFT JOIN users u 
            ON a.user_id = u.id

        LEFT JOIN agents sa 
            ON p.sold_by_agent_id = sa.id
        LEFT JOIN users su 
            ON sa.user_id = su.id

        WHERE a.company_id = ?
        ORDER BY p.created_at DESC
        ");
        $propsStmt->bind_param("i", $company_id);
        $propsStmt->execute();
        $res              = $propsStmt->get_result();
        $company_listings = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $propsStmt->close();
    }

    // 🔹 Fetch wallet balance for the logged-in user
    $walletBalance = 0.00;
    if (isset($user['id'])) {
        $stmtWallet = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmtWallet->bind_param("i", $user['id']);
        $stmtWallet->execute();
        $resWallet = $stmtWallet->get_result();
        $rowWallet = $resWallet ? $resWallet->fetch_assoc() : null;
        $walletBalance = $rowWallet ? (float)$rowWallet['wallet_balance'] : 0.00;
        $stmtWallet->close();
    }

    // Format for display
    $walletBalanceFormatted = number_format($walletBalance, 2, '.', ',');

    // 🔹 Fetch last 10 transaction history for the logged-in user
    $transactions = [];
    $stmt = $conn->prepare("
        SELECT 
            id,
            property,
            amount,
            status,
            method,
            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS date
        FROM transactions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $transactions = $res->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();

    // 🔹 Fetch agent info for wallet tab
    $agentInfo = [
        'name'  => $user['first_name'] . ' ' . $user['last_name'],
        'phone' => $user['phone'] ?? 'N/A'
    ];
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/associate_profile.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_sidebar.css">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_overview.css">
<script src="https://www.paypal.com/sdk/js?client-id=AS2IFQyy2dcIowcsn3TnY5rSfvzbQbx3KrcGxSeaVBr9XoqYVqNrDR_hPHDXt3gUzhIr1vuUx1m4J1Yt&currency=PHP"></script>
<div class="dashboard-container">

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
                    <i class="fa-solid fa-user sidebar-icon"></i>
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
                <h2>My Listings</h2>

                <div class="overview-container">
                <?php if (!empty($listings)): ?>
                    <?php foreach ($listings as $property): 
                        $ownership = ($property['agent_id'] == $agent_id) ? 'Owned' : 'Shared';

                        // ✅ Grab first uploaded image if available
                        $first_img_src = '';
                        if (!empty($property['images'])) {
                            $first_img_src = "/BatEstateExplorer/" . $property['images'][0]['image_path'];
                        }
                    ?>
                        <div class="overview-card listing-card">
                            <?php if ($first_img_src): ?>
                                <div class="listing-thumb">
                                    <img src="<?= $first_img_src ?>" alt="Property Image">
                                </div>
                            <?php endif; ?>

                            <div class="info-row"><strong>Title:</strong> <span><?= htmlspecialchars($property['title']) ?></span></div>
                            <div class="info-row"><strong>Location:</strong> <span><?= htmlspecialchars($property['location']) ?></span></div>
                            <div class="info-row"><strong>Price:</strong> <span>₱<?= number_format($property['price'], 2) ?></span></div>
                            <div class="info-row"><strong>Bedrooms:</strong> <span><?= htmlspecialchars($property['bedrooms']) ?></span></div>
                            <div class="info-row"><strong>Bathrooms:</strong> <span><?= htmlspecialchars($property['bathrooms']) ?></span></div>
                            <div class="info-row"><strong>Status:</strong> <span><?= htmlspecialchars($property['status']) ?></span></div>
                            <?php
                            $listingTypeSelected = !empty($property['sold_by_agent_id']) ? 'sold_by' : 'owned';
                            $ownershipLabel = $listingTypeSelected === 'sold_by' ? "Sold by: {$property['sold_by_email']}" : "Owned";
                            ?>
                            <div class="info-row"><strong>Listing Type:</strong> <span><?= $ownershipLabel ?></span></div>

                            
                            <div class="info-row actions">
                                <!-- Associates can edit -->
                                <a href="javascript:void(0)" class="btn-edit" onclick="openModal(<?= $property['id'] ?>)">Edit</a>
                                
                                <!-- Delete -->
                                <form method="POST" action="/BatEstateExplorer/public/api/delete_listing.php" style="display:inline;">
                                    <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                                    <button type="submit" class="btn-delete" 
                                            onclick="return confirm('Are you sure you want to delete this listing?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Edit Modal -->
                        <div id="editModal-<?= $property['id'] ?>" class="edit-modal">
                            <div class="modal-content">
                                <span class="close" onclick="closeModal(<?= $property['id'] ?>)">&times;</span>
                                
                                <h2>Edit Listing: <?= htmlspecialchars($property['title']) ?></h2>
                                
                                <form id="editForm-<?= $property['id'] ?>" method="POST" action="/BatEstateExplorer/public/api/update_property.php" enctype="multipart/form-data">
                                    <input type="hidden" name="property_id" value="<?= $property['id'] ?>">

                                    <label>Property Name</label>
                                    <input type="text" name="title" value="<?= htmlspecialchars($property['title']) ?>" required>

                                    <label>Description</label>
                                    <textarea name="description"><?= htmlspecialchars($property['description']) ?></textarea>

                                    <label>Property Type</label>
                                    <select name="property_type" class="property-type" required>
                                        <option value="Property" <?= $property['property_type']=='Property'?'selected':'' ?>>Property</option>
                                        <option value="Lot" <?= $property['property_type']=='Lot'?'selected':'' ?>>Lot</option>
                                    </select>

                                    <label>Location</label>
                                    <select name="location" class="location" required>
                                        <option value="">Select Location</option>
                                        <?php
                                        $locations = [
                                            "Agoncillo","Alitagtag","Balayan","Balete","Batangas City","Bauan","Calaca","Calatagan","Cuenca",
                                            "Ibaan","Laurel","Lemery","Lian","Lipa City","Lobo","Mabini","Malvar","Mataasnakahoy","Nasugbu",
                                            "Padre Garcia","Rosario","San Jose","San Juan","San Luis","San Nicolas","San Pascual",
                                            "Santa Teresita","Santo Tomas","Taal","Talisay","Tanauan City","Taysan","Tingloy","Tuy"
                                        ];

                                        foreach ($locations as $loc): ?>
                                            <option value="<?= $loc ?>" <?= $property['location'] == $loc ? 'selected' : '' ?>>
                                                <?= $loc ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label>Price</label>
                                    <input type="number" step="0.01" name="price" value="<?= $property['price'] ?>" required>

                                    <label>Bedrooms</label>
                                    <input type="number" name="bedrooms" class="bedrooms" value="<?= $property['bedrooms'] ?>">

                                    <label>Bathrooms</label>
                                    <input type="number" name="bathrooms" class="bathrooms" value="<?= $property['bathrooms'] ?>">

                                    <label>Lot Size (sqm)</label>
                                    <input type="number" step="0.01" name="lot_size" value="<?= $property['lot_size'] ?>">

                                    <!-- Status -->
                                    <label>Status</label>
                                    <select name="status" disabled>
                                        <option value="available" <?= ($property['status'] == 'available') ? 'selected' : '' ?>>Available</option>
                                        <option value="sold" <?= ($property['status'] == 'sold') ? 'selected' : '' ?>>Sold</option>
                                    </select>

                                    <!-- Existing Images -->
                                    <label><strong>Existing Images</strong></label>
                                    <div class="image-gallery">
                                        <?php if (!empty($property['images'])): ?>
                                            <?php foreach ($property['images'] as $img): ?>
                                                <div class="image-item">
                                                    <img src="/BatEstateExplorer/<?= $img['image_path'] ?>" alt="Property Image">

                                                    <!-- Keep track of current images -->
                                                    <input type="hidden" name="existing_images[]" value="<?= $img['image_path'] ?>">

                                                    <!-- Mark Primary -->
                                                    <label class="primary-label">
                                                        <input type="radio" 
                                                            name="primary_image" 
                                                            value="<?= $img['image_path'] ?>" 
                                                            <?= isset($img['is_primary']) && $img['is_primary'] ? 'checked' : '' ?>>
                                                        Primary
                                                    </label>

                                                    <!-- Remove button -->
                                                    <button type="button" class="remove-img-btn" 
                                                            onclick="markImageForRemoval(this, '<?= $img['image_path'] ?>')">
                                                        Remove
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p>No images uploaded yet.</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Hidden container for removals -->
                                    <div id="removeImages-<?= $property['id'] ?>"></div>

                                    <!-- Upload New Images -->
                                    <label for="newImages-<?= $property['id'] ?>"><strong>Add New Images</strong></label>
                                    <input id="newImages-<?= $property['id'] ?>" 
                                        type="file" 
                                        name="new_images[]" 
                                        multiple 
                                        accept="image/*">

                                    <!-- Listing Type -->
                                    <label>Listing Type</label>
                                    <select name="listing_type" class="listing-type" onchange="toggleSoldBy(this, <?= $property['id'] ?>)">
                                        <option value="owned" <?= ($listingTypeSelected=='owned')?'selected':'' ?>>Owned</option>
                                        <option value="sold_by" <?= ($listingTypeSelected=='sold_by')?'selected':'' ?>>Sold By</option>
                                    </select>

                                    <div id="soldByContainer-<?= $property['id'] ?>" style="display: <?= ($listingTypeSelected=='sold_by')?'block':'none' ?>;">
                                        <label>Agent Email</label>
                                        <input type="email" name="sold_by_email" placeholder="Enter agent email" 
                                            value="<?= ($listingTypeSelected=='sold_by')?$property['sold_by_email']:'' ?>">
                                    </div>

                                    <button type="button" onclick="confirmEdit(<?= $property['id'] ?>)">Update Listing</button>
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
                <h2>Add New Listing</h2>

                <div class="overview-container">
                    <div class="overview-card">
                        <form id="addListingForm" enctype="multipart/form-data">

                            <!-- Property Name -->
                            <label for="title"><strong>Property Name</strong></label>
                            <input type="text" id="title" name="title" required>

                            <!-- Location -->
                            <label for="location"><strong>Location</strong></label>
                            <select id="location" name="location" required>
                                <option value="">Select Location</option>
                                <option value="Agoncillo">Agoncillo</option>
                                <option value="Alitagtag">Alitagtag</option>
                                <option value="Balayan">Balayan</option>
                                <option value="Balete">Balete</option>
                                <option value="Batangas City">Batangas City</option>
                                <option value="Bauan">Bauan</option>
                                <option value="Calaca">Calaca</option>
                                <option value="Calatagan">Calatagan</option>
                                <option value="Cuenca">Cuenca</option>
                                <option value="Ibaan">Ibaan</option>
                                <option value="Laurel">Laurel</option>
                                <option value="Lemery">Lemery</option>
                                <option value="Lian">Lian</option>
                                <option value="Lipa City">Lipa City</option>
                                <option value="Lobo">Lobo</option>
                                <option value="Mabini">Mabini</option>
                                <option value="Malvar">Malvar</option>
                                <option value="Mataasnakahoy">Mataasnakahoy</option>
                                <option value="Nasugbu">Nasugbu</option>
                                <option value="Padre Garcia">Padre Garcia</option>
                                <option value="Rosario">Rosario</option>
                                <option value="San Jose">San Jose</option>
                                <option value="San Juan">San Juan</option>
                                <option value="San Luis">San Luis</option>
                                <option value="San Nicolas">San Nicolas</option>
                                <option value="San Pascual">San Pascual</option>
                                <option value="Santa Teresita">Santa Teresita</option>
                                <option value="Santo Tomas">Santo Tomas</option>
                                <option value="Taal">Taal</option>
                                <option value="Talisay">Talisay</option>
                                <option value="Tanauan City">Tanauan City</option>
                                <option value="Taysan">Taysan</option>
                                <option value="Tingloy">Tingloy</option>
                                <option value="Tuy">Tuy</option>
                            </select>

                            <!-- Price -->
                            <label for="price"><strong>Price (₱)</strong></label>
                            <input type="number" id="price" name="price" min="0" step="0.01" required>

                            <!-- Lot Size -->
                            <label for="lot_size"><strong>Lot Size (sqm)</strong></label>
                            <input type="number" id="lot_size" name="lot_size" min="0" step="0.01" required>

                            <!-- Property Type -->
                            <label for="property_type"><strong>Property Type</strong></label>
                            <select id="property_type" name="property_type" required>
                                <option value="">-- Select Type --</option>
                                <option value="Property">Property</option>
                                <option value="Lot">Lot</option>
                            </select>

                            <!-- Bedrooms -->
                            <label for="bedrooms"><strong>Bedrooms</strong></label>
                            <input type="number" id="bedrooms" name="bedrooms" min="0" step="1">

                            <!-- Bathrooms -->
                            <label for="bathrooms"><strong>Bathrooms</strong></label>
                            <input type="number" id="bathrooms" name="bathrooms" min="0" step="1">

                            <!-- Description -->
                            <label for="description"><strong>Description</strong></label>
                            <textarea id="description" name="description" rows="4" required></textarea>

                            <!-- Images -->
                            <label for="images"><strong>Property Images</strong></label>
                            <div id="imageUploadArea" class="drag-drop-area" tabindex="0">
                                <p>Drag & drop images here or click to browse</p>
                                <input type="file" id="images" name="images[]" accept="image/*" multiple style="display:none;">
                            </div>
                            <div id="imagePreview" class="image-preview" aria-live="polite"></div>

                            <!-- Submit -->
                            <button type="button" id="openListingModalBtn" class="btn-submit">Save Listing</button>
                        </form>
                    </div>
                </div>

                <!-- Listing Fee Modal -->
                <div id="listingFeeModal" class="deposit-modal" onclick="closeListingFeeModal(event)">
                    <div class="modal-content" onclick="event.stopPropagation()">
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
                    <h2>Performance Analytics</h2>
                    <div class="analytics-top">
                        <h1><?= $avg_rating ?></h1>
                        <div class="stars">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <span class="star <?= $i <= round($avg_rating) ? 'filled' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <p><?= $total_reviews ?> Review<?= $total_reviews != 1 ? 's' : '' ?></p>
                    </div>

                    <div class="review-cards">
                        <?php if (!empty($reviews)): ?>
                            <?php foreach($reviews as $r): 
                                $user_name = trim($r['first_name'] . ' ' . $r['last_name']);
                                $image_path = !empty($r['image_path']) ? "/BatEstateExplorer/" . $r['image_path'] : '/assets/images/default.jpg';
                            ?>
                            <div class="review-card">
                                <div class="review-left">
                                    <h4><?= htmlspecialchars($user_name) ?></h4>
                                    <p><?= htmlspecialchars($r['email']) ?></p>
                                    <div class="stars">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <span class="star <?= $i <= $r['rating'] ? 'filled' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($r['review_text'])) ?></p>
                                </div>
                                <div class="review-right">
                                    <img src="<?= htmlspecialchars($image_path) ?>" alt="<?= htmlspecialchars($r['title']) ?>">
                                    <p><?= htmlspecialchars($r['title']) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No reviews found for your properties.</p>
                        <?php endif; ?>
                    </div>
    
            <?php break; ?>

            <?php case 'company_listings': ?>
                <h2>Company Listings: <?= htmlspecialchars($company_name) ?></h2>
                <p>List of all properties from your company.</p>

                <!-- Scrollable container -->
                <div style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd;">
                    <table border="1" cellpadding="8" cellspacing="0" width="100%">
                        <thead style="position: sticky; top: 0; background: #f5f5f5; z-index: 1;">
                            <tr>
                                <th>Property</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Bedrooms</th>
                                <th>Bathrooms</th>
                                <th>Size (sqm)</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Sold By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($company_listings)): ?>
                                <?php foreach ($company_listings as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['title']); ?></td>
                                        <td><?= htmlspecialchars($row['location']); ?></td>
                                        <td>₱<?= number_format($row['price'], 2); ?></td>
                                        <td><?= (int)$row['bedrooms']; ?></td>
                                        <td><?= (int)$row['bathrooms']; ?></td>
                                        <td><?= number_format($row['sqm'], 2); ?></td>
                                        <td><?= ucfirst($row['status']); ?></td>
                                        <td><?= !empty($row['created_by']) ? htmlspecialchars($row['created_by']) : 'N/A'; ?></td>
                                        <td><?= !empty($row['sold_by']) ? htmlspecialchars($row['sold_by']) : 'N/A'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align:center;">No company listings found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php break; ?>

            <?php case 'review_privileges': ?>
                    <h2>Review Privileges</h2>

                    <div class="overview-container">
                        <div class="overview-card">
                            <label for="searchEmail">Search Client by Email:</label>
                            <input type="email" id="searchEmail" placeholder="Enter email..." />
                            <button onclick="searchClient()">Search</button>
                        </div>
                    </div>

                    <!-- Privilege Modal -->
                    <div id="privilegeModal" class="edit-modal">
                        <div class="modal-content">
                            <span class="close" onclick="closePrivilegeModal()">&times;</span>
                            <h2>Grant Review Privilege</h2>
                            <p id="userNameEmail"></p>

                            <h3>Select a Property</h3>
                            <div id="propertyList" class="property-list">
                                <?php if (!empty($listings)): ?>
                                    <?php foreach ($listings as $property): 
                                        $first_img_src = !empty($property['images']) 
                                            ? "/BatEstateExplorer/" . $property['images'][0]['image_path'] 
                                            : "/BatEstateExplorer/assets/img/no-image.png"; 
                                    ?>
                                        <div class="property-card" 
                                            onclick="selectProperty(this, <?= $property['id'] ?>)">
                                            <img src="<?= $first_img_src ?>" alt="Property Image">
                                            <h4><?= htmlspecialchars($property['title']) ?></h4>
                                            <p><?= htmlspecialchars($property['location']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No properties found.</p>
                                <?php endif; ?>
                            </div>

                            <button onclick="givePrivilege()">Give Privilege</button>
                            <button onclick="closePrivilegeModal()">Exit</button>
                        </div>
                    </div>
            <?php break; ?>

            <?php case 'wallet': ?>
                <h2>Agent Wallet</h2>

                <!-- Wallet Balance -->
                <div class="wallet-balance-section mb-4 d-flex align-items-center justify-content-between" style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem;">
                    <h3>PHP <span id="walletBalance"><?= $walletBalance ?></span></h3>
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
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd;">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light position-sticky top-0">
                            <tr>
                                <th>Date</th>
                                <th>Property</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tbody id="transactionTable">
                            <?php if(!empty($transactions)): ?>
                                <?php foreach($transactions as $tx): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tx['date']); ?></td>
                                        <td><?= htmlspecialchars($tx['property']); ?></td>
                                        <td>₱<?= number_format($tx['amount'], 2); ?></td>
                                        <td><?= ucfirst($tx['status']); ?></td>
                                        <td><?= htmlspecialchars($tx['method']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center">No transactions yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Deposit Modal -->
                <div id="depositModal" class="deposit-modal" onclick="closeDepositModal(event)">
                    <div class="modal-content" onclick="event.stopPropagation()">
                        <span class="close" onclick="closeDepositModal()">&times;</span>
                        <h2>Deposit Funds</h2>
                        <input type="text" id="selectedAmount" class="form-control mb-3" placeholder="Selected amount" disabled>
                        <div class="deposit-amounts mb-3">
                            <?php foreach ([50,100,200,400,600,1000] as $amt): ?>
                                <button type="button" class="deposit-amount-btn" data-amount="<?= $amt ?>">PHP <?= $amt ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div id="paypal-button-container"></div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const selectedInput = document.getElementById('selectedAmount');
                        const balanceEl = document.getElementById('walletBalance');
                        const modal = document.getElementById('depositModal');
                        const walletLimit = 10000;
                        const tableBody = document.getElementById('transactionTable');

                        // Open / close modal
                        window.openDepositModal = () => modal.style.display = 'flex';
                        window.closeDepositModal = (event) => {
                            if (!event || event.target === modal) modal.style.display = 'none';
                        }

                        // Deposit amount buttons
                        document.querySelectorAll('.deposit-amount-btn').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const amount = parseFloat(btn.dataset.amount);
                                const current = parseFloat(balanceEl.innerText.replace(/,/g,'')) || 0;

                                if(current + amount > walletLimit){
                                    alert(`Deposit exceeds wallet limit of PHP ${walletLimit}. Please try a smaller amount.`);
                                    selectedInput.value = '';
                                    return;
                                }

                                selectedInput.value = amount;
                            });
                        });

                        // Render PayPal buttons
                        paypal.Buttons({
                            style: { layout:'vertical', color:'blue', shape:'pill', label:'pay' },

                            createOrder: function(data, actions) {
                                const amount = parseFloat(selectedInput.value);
                                if (!amount) {
                                    alert('Please select an amount first.');
                                    return;
                                }

                                const current = parseFloat(balanceEl.innerText.replace(/,/g,'')) || 0;
                                if (current + amount > walletLimit) {
                                    alert(`Deposit exceeds wallet limit of PHP ${walletLimit}. Please try a smaller amount.`);
                                    return;
                                }

                                return actions.order.create({
                                    purchase_units: [{ amount: { value: amount.toFixed(2) } }]
                                });
                            },

                            onApprove: function(data, actions) {
                                return actions.order.capture().then(function(details) {
                                    const amount = parseFloat(selectedInput.value);
                                    let current = parseFloat(balanceEl.innerText.replace(/,/g,'')) || 0;
                                    let newBalance = current + amount;

                                    if (newBalance > walletLimit) {
                                        alert(`Deposit exceeds wallet limit of PHP ${walletLimit}. Transaction will not be processed.`);
                                        return;
                                    }

                                    // Update wallet balance on page
                                    balanceEl.innerText = newBalance.toLocaleString('en-PH', { minimumFractionDigits: 2 });

                                    // Save deposit to DB
                                    fetch('/BatEstateExplorer/public/api/deposit.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: `amount=${encodeURIComponent(amount)}`
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            alert('Deposit successful! Paid by: ' + details.payer.name.given_name);
                                            selectedInput.value = '';
                                            closeDepositModal();

                                            // Add new transaction row dynamically
                                            const now = new Date();
                                            const formattedDate = now.getFullYear() + '-' +
                                                                String(now.getMonth()+1).padStart(2,'0') + '-' +
                                                                String(now.getDate()).padStart(2,'0') + ' ' +
                                                                String(now.getHours()).padStart(2,'0') + ':' +
                                                                String(now.getMinutes()).padStart(2,'0') + ':' +
                                                                String(now.getSeconds()).padStart(2,'0');

                                            const newRow = document.createElement('tr');
                                            newRow.innerHTML = `
                                                <td>${formattedDate}</td>
                                                <td>Deposit</td>
                                                <td>₱${amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                                <td>Completed</td>
                                                <td>PayPal</td>
                                            `;

                                            // Remove "No transactions yet" placeholder if present
                                            const placeholder = tableBody.querySelector('.text-center');
                                            if (placeholder) tableBody.innerHTML = '';

                                            tableBody.prepend(newRow);

                                            // Limit table to last 10 transactions
                                            while(tableBody.rows.length > 10) {
                                                tableBody.deleteRow(10);
                                            }

                                        } else {
                                            alert('Deposit saved to PayPal but failed to update wallet: ' + (data.error || 'Unknown error'));
                                        }
                                    })
                                    .catch(err => {
                                        console.error(err);
                                        alert('Deposit saved to PayPal but failed to update wallet in DB.');
                                    });
                                });
                            },

                            onError: function(err) {
                                console.error(err);
                                alert('An error occurred during the PayPal transaction. Please check the amount and try again.');
                            }

                        }).render('#paypal-button-container');
                    });
                </script>

                <style>
                    .deposit-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:9999; }
                    .modal-content { background:#fff; padding:20px; border-radius:8px; max-width:400px; width:90%; }
                    .modal-content .close { float:right; font-size:1.5rem; cursor:pointer; }
                    .deposit-amounts button { margin:4px; padding:8px 12px; cursor:pointer; }
                    .btn-circle { border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; }
                </style>
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

                        <!-- Danger Zone in new grid row -->
                        <div class="overview-card danger-zone">
                            <h3>Danger Zone</h3>
                            <p class="danger-note">⚠️ Once deleted, this account <strong>cannot be recovered</strong>. Please proceed with caution.</p>
                            <button type="button" id="openDeleteModal" class="delete-btn">Delete Agent Account</button>
                        </div>
                    </div>  

                    <!-- Edit Form (full width) -->
                    <form id="profileForm" class="profile-edit" method="POST" action="/BatEstateExplorer/public/api/save_profile.php" style="display:none;">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>

                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" autocomplete="email" required>

                        <button type="submit">Save Changes</button>
                        <button type="button" id="cancelEditBtn">Cancel</button>
                    </form>

                    <div id="profileMessage"></div>
                </div>

                <!-- Modal (outside container so it overlays everything) -->
                <div id="deleteModal" class="modal" style="display:none;">
                    <div class="modal-content">
                        <h4>Confirm Account Deletion</h4>
                        <p>Are you sure you want to delete this agent account? This action cannot be undone.</p>
                        <form id="deleteAgentForm" method="POST" action="/BatEstateExplorer/public/api/delete_agent.php">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                            <button type="submit" id="confirmDeleteBtn" class="delete-btn">Yes, Delete</button>
                            <button type="button" id="cancelDeleteBtn">Cancel</button>
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

<script src="/BatEstateExplorer/assets/js/associate_profile.js"></script>