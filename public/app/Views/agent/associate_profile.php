<?php
if (!isset($user)) {
    die('Access denied.');
}

// Display flash messages
foreach (['success', 'error'] as $type) {
    if (!empty($_SESSION['flash_' . $type])): ?>
        <div class="alert alert-<?= $type === 'success' ? 'success' : 'danger' ?>">
            <?= $_SESSION['flash_' . $type]; unset($_SESSION['flash_' . $type]); ?>
        </div>
    <?php endif;
}

// Detect active tab
$tab = $_GET['tab'] ?? 'overview';

// Initialize variables
$listings = [];
$agent_id = 0;

// 🔹 Fetch agent info
$stmt = $conn->prepare("SELECT id FROM agents WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$res = $stmt->get_result();
$agent = $res ? $res->fetch_assoc() : null;
$stmt->close();

if ($agent) {
    $agent_id = (int)$agent['id'];

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
    $res = $propsStmt->get_result();
    $listings = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    // 🔹 Attach primary image to each property
    $stmtImg = $conn->prepare("
        SELECT image_path 
        FROM property_images 
        WHERE property_id = ? 
        ORDER BY is_primary DESC, id ASC
        LIMIT 1
    ");
    foreach ($listings as &$property) {  // note the & to modify in place
        $stmtImg->bind_param("i", $property['id']);
        $stmtImg->execute();
        $resImg = $stmtImg->get_result();
        $image = $resImg && $resImg->num_rows ? $resImg->fetch_assoc() : null;

        $property['images'] = $image ? [$image] : [];
    }
    $stmtImg->close();
}

// 🔹 Fetch average rating and total reviews
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
$avg_rating = $row && $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
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
$res = $stmt->get_result();
$reviews = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Make sure $user is the logged-in user
$logged_in_user_id = (int)($user['id'] ?? 0);

// Fetch the agent record to get company_id
$agentStmt = $conn->prepare("SELECT company_id FROM agents WHERE user_id = ?");
$agentStmt->bind_param("i", $logged_in_user_id);
$agentStmt->execute();
$agentRes = $agentStmt->get_result();
$agent = $agentRes->fetch_assoc();
$company_id = (int)($agent['company_id'] ?? 0);

// Fetch company info
$companyStmt = $conn->prepare("SELECT name FROM companies WHERE id = ?");
$companyStmt->bind_param("i", $company_id);
$companyStmt->execute();
$companyRes = $companyStmt->get_result();
$company = $companyRes->fetch_assoc();
$company_name = $company['name'] ?? 'Unknown Company';

// Output console log for debugging
echo "<script>console.log('Company ID: {$company_id}, Company Name: " . addslashes($company_name) . "');</script>";

// Fetch all properties from the same company (no pagination)
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
        a.id AS agent_id, 
        CONCAT(u.first_name, ' ', u.last_name) AS created_by,
        sa.id AS sold_agent_id, 
        CONCAT(su.first_name, ' ', su.last_name) AS sold_by
    FROM properties p
    LEFT JOIN agents a ON p.agent_id = a.id
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN agents sa ON p.sold_by_agent_id = sa.id
    LEFT JOIN users su ON sa.user_id = su.id
    WHERE a.company_id = ?
    ORDER BY p.created_at DESC
");

$propsStmt->bind_param("i", $company_id);
$propsStmt->execute();
$res = $propsStmt->get_result();

?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/agent_profile_tab.css">

<div class="dashboard-container">
    <!-- Header -->
    <header class="dashboard-header">
        <h1>Associate Agent Profile</h1>
    </header>

    <!-- Navigation Tabs -->
    <nav class="dashboard-tabs">
        <a href="?view=associate_profile" class="tab <?= ($tab === 'overview') ? 'active' : '' ?>">Overview</a>
        <a href="?view=associate_profile&tab=my_listings" class="tab <?= ($tab === 'my_listings') ? 'active' : '' ?>">My Listings</a>
        <a href="?view=associate_profile&tab=add_listing" class="tab <?= ($tab === 'add_listing') ? 'active' : '' ?>">Add Listing</a>
        <a href="?view=associate_profile&tab=analytics" class="tab <?= ($tab === 'analytics') ? 'active' : '' ?>">Analytics</a>
        <a href="?view=associate_profile&tab=company_listings" class="tab <?= ($tab === 'company_listings') ? 'active' : '' ?>">Company Listings</a>
        <a href="?view=associate_profile&tab=review_privileges" class="tab <?= ($tab === 'review_privileges') ? 'active' : '' ?>">Review Privileges</a>
    </nav>

    <!-- Content Section -->
    <section class="dashboard-content">
        <?php 
        switch ($tab):
            case 'my_listings': 
        ?>
            <h2>My Listings (Associate)</h2>

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
                                    <option value="Lipa City" <?= $property['location']=='Lipa City'?'selected':'' ?>>Lipa City</option>
                                    <!-- ...other options... -->
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

                                <!-- Existing images -->
                                <div class="image-slider">
                                    <?php foreach ($property['images'] as $img): ?>
                                        <div class="slider-item">
                                            <img src="/BatEstateExplorer/<?= $img['image_path'] ?>" alt="Property Image">
                                            <input type="hidden" name="existing_images[]" value="<?= $img['image_path'] ?>">
                                            <button type="button" onclick="removeImage(this)">Remove</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Add new images -->
                                <label>Add Images</label>
                                <input type="file" name="new_images[]" multiple>

                                <!-- Listing Type -->
                                <label>Listing Type</label>
                                <select name="listing_type" class="listing-type" onchange="toggleSoldBy(this, <?= $property['id'] ?>)">
                                    <option value="owned" <?= ($listingTypeSelected=='owned')?'selected':'' ?>>Owned</option>
                                    <option value="sold_by" <?= ($listingTypeSelected=='sold_by')?'selected':'' ?>>Sold By</option>
                                </select>

                                <div id="soldByContainer-<?= $property['id'] ?>" style="display: <?= ($listingTypeSelected=='sold_by')?'block':'none' ?>;">
                                    <label>Agent Email</label>
                                    <input type="email" name="sold_by_email" placeholder="Enter agent email" value="<?= ($listingTypeSelected=='sold_by')?$property['sold_by_email']:'' ?>">
                                </div>

                                <button type="button" onclick="confirmEdit(<?= $property['id'] ?>)">Update Listing</button>
                            </form>
                        </div>
                    </div>
                    <script>
                        function toggleSoldBy(select, propertyId) {
                            const container = document.getElementById('soldByContainer-' + propertyId);
                            if (!container) return; // safety
                            if (select.value === 'sold_by') {
                                container.style.display = 'block';
                            } else {
                                container.style.display = 'none';
                                container.querySelector('input').value = '';
                            }
                        }
                        function confirmEdit(propertyId) {
                            const form = document.getElementById('editForm-' + propertyId);
                            if (!form) return;

                            // Show a browser confirmation
                            const confirmed = confirm("Are you sure you want to update this listing?");
                            if (confirmed) {
                                form.submit();
                            }
                        }
                    </script>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="overview-card">
                    <p>No listings found.</p>
                </div>
            <?php endif; ?>
            </div>

        <?php break; ?>

        <?php case 'add_listing': ?>
        <h2>Add New Listing (Associate)</h2>

        <div class="overview-container">
        <div class="overview-card">
            <form id="addListingForm" 
                action="/BatEstateExplorer/public/api/associate_save_listing.php" 
                method="POST" 
                enctype="multipart/form-data">

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
                    <input type="file" id="images" accept="image/*" multiple style="display:none;">
                </div>

                <div id="imagePreview" class="image-preview" aria-live="polite"></div>

                <button type="submit" class="btn-submit">Save Listing</button>
            </form>
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
                            <?php while ($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title']); ?></td>
                                <td><?= htmlspecialchars($row['location']); ?></td>
                                <td>₱<?= number_format($row['price'], 2); ?></td>
                                <td><?= (int)$row['bedrooms']; ?></td>
                                <td><?= (int)$row['bathrooms']; ?></td>
                                <td><?= number_format($row['sqm'], 2); ?></td>
                                <td><?= ucfirst($row['status']); ?></td>
                                <td><?= htmlspecialchars($row['created_by'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($row['sold_by'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endwhile; ?>
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

                <script>
                    // Global variables
                    let selectedPropertyId = null;
                    window.currentEmail = null;

                    // Search client by email
                    window.searchClient = function() {
                        const email = document.getElementById('searchEmail').value.trim();
                        if (!email) return alert('Please enter an email');

                        fetch(`/BatEstateExplorer/public/api/give_privilege.php?email=${encodeURIComponent(email)}`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.error) return alert(data.error);

                                document.getElementById('userNameEmail').textContent =
                                    `${data.name || ''} (${data.email})`;

                                document.getElementById('privilegeModal').style.display = 'block';
                                window.currentEmail = data.email;
                            })
                            .catch(err => console.error('Search client error:', err));
                    };

                    // Select a property card
                    window.selectProperty = function(card, propertyId) {
                        document.querySelectorAll('.property-card').forEach(c => c.classList.remove('selected'));
                        card.classList.add('selected');
                        selectedPropertyId = propertyId;
                    };

                    // Close privilege modal
                    window.closePrivilegeModal = function() {
                        document.getElementById('privilegeModal').style.display = 'none';
                        selectedPropertyId = null;
                    };

                    // Give privilege to selected client for selected property
                    window.givePrivilege = function() {
                        if (!selectedPropertyId) return alert('Please select a property first.');
                        if (!window.currentEmail) return alert('No client selected.');

                        fetch('/BatEstateExplorer/public/api/give_privilege.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `email=${encodeURIComponent(window.currentEmail)}&property_id=${encodeURIComponent(selectedPropertyId)}`
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                alert('Privilege granted successfully!');
                                closePrivilegeModal();
                            } else {
                                alert(data.error || 'Something went wrong.');
                            }
                        })
                        .catch(err => console.error('Give privilege error:', err));
                    };
                </script>

            <?php break; ?>

        <?php default:
            // Overview Tab
            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            if ($fullName === '') $fullName = 'Agent';
        ?>
            <h2>Profile Overview</h2>
            <div class="overview-container">
                <div class="profile-view">
                    <button id="editProfileBtn">Edit Profile</button>

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

                    <!-- Professional Details -->
                    <div class="overview-card">
                        <h3>Professional Details</h3>
                        <div class="info-row"><strong>Agent Type:</strong> <span><?= htmlspecialchars($user['agent_type'] ?? '-') ?></span></div>
                        <div class="info-row"><strong>Broker ID:</strong> <span><?= htmlspecialchars($user['broker_id'] ?? '-') ?></span></div>
                        <div class="info-row"><strong>License Number:</strong> <span><?= htmlspecialchars($user['license_number'] ?? '-') ?></span></div>
                        <div class="info-row"><strong>Years of Experience:</strong> <span><?= htmlspecialchars($user['experience_years'] ?? '-') ?></span></div>
                        <div class="info-row"><strong>Specialization:</strong> <span><?= htmlspecialchars($user['specialization'] ?? '-') ?></span></div>
                        <div class="info-row"><strong>Bio:</strong> <span><?= nl2br(htmlspecialchars($user['bio'] ?? '-')) ?></span></div>
                    </div>

                    <!-- Documents -->
                    <div class="overview-card">
                        <h3>Documents</h3>
                        <?php
                        $docs = [
                            'Broker License' => 'broker_license_path',
                            'PRC License' => 'prc_license_path',
                            'Resume' => 'resume_path',
                            'Valid ID' => 'valid_id_path',
                            'Additional Docs' => 'additional_docs_path'
                        ];
                        foreach ($docs as $label => $field):
                        ?>
                            <div class="info-row"><strong><?= $label ?>:</strong>
                                <?php if (!empty($user[$field])): ?>
                                    <a href="<?= htmlspecialchars($user[$field]) ?>" target="_blank">View</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Account Status -->
                    <div class="overview-card">
                        <h3>Account Status</h3>
                        <div class="info-row"><strong>Status:</strong> <span><?= htmlspecialchars($user['status'] ?? '-') ?></span></div>
                        <div class="info-row"><strong>Admin Notes:</strong> <span><?= nl2br(htmlspecialchars($user['admin_notes'] ?? '-')) ?></span></div>
                        <div class="info-row"><strong>Created At:</strong> <span><?= htmlspecialchars($user['created_at'] ?? '-') ?></span></div>
                        <div class="info-row"><strong>Last Updated:</strong> <span><?= htmlspecialchars($user['updated_at'] ?? '-') ?></span></div>
                    </div>

                    <!-- Delete Account -->
                    <div class="overview-card danger-zone">
                        <h3>Danger Zone</h3>
                        <button type="button" id="openDeleteModal" class="delete-btn">Delete Agent Account</button>
                    </div>

                    <!-- Modal (placed at the end of body, outside any container) -->
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
                </div>  
                
                <form id="profileForm" class="profile-edit" method="POST" action="/BatEstateExplorer/public/api/save_profile.php" style="display:none;">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>

                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" autocomplete="email" required>

                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter current password" autocomplete="current-password" required>

                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Leave blank to keep current" autocomplete="new-password">

                    <button type="submit">Save Changes</button>
                    <button type="button" id="cancelEditBtn">Cancel</button>
                </form>

                <div id="profileMessage"></div>
            </div>
        <?php endswitch; ?>
    </section>
</div>

<style>
/* Property List Grid */
.property-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
    box-sizing: border-box;
}

/* Property Card */
.property-card {
    border: 2px solid #ccc;
    border-radius: 8px;
    padding: 0.5rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
}

.property-card img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 6px;
}

.property-card.selected {
    border-color: #007bff;
    background-color: #eef5ff;
    transform: scale(1.02);
}

/* Review Card */
.review-card {
    display: flex;
    gap: 20px;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 12px;
    background-color: #fff;
    transition: box-shadow 0.2s ease;
}

.review-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Review Sections */
.review-left {
    flex: 2;
}

.review-right {
    flex: 1;
    text-align: center;
}

.review-right img {
    width: 100px;
    height: 70px;
    object-fit: cover;
    border-radius: 6px;
}

/* Stars */
.stars {
    display: flex;
    flex-direction: row;
    gap: 2px;
    font-size: 16px;
    line-height: 1;
}

.stars span {
    color: #ccc; /* default star color */
    display: inline-block;
}

.stars span.filled {
    color: gold !important; /* force gold color */
}
</style>


<script>
    document.addEventListener('DOMContentLoaded', () => {

    // ===== Helper: Toggle Bedrooms/Bathrooms for "Lot" =====
    const toggleRooms = (typeSelect, bedroomsInput, bathroomsInput) => {
        const isLot = typeSelect.value === 'Lot';
        bedroomsInput.disabled = isLot;
        bathroomsInput.disabled = isLot;
        if (isLot) {
            bedroomsInput.value = 0;
            bathroomsInput.value = 0;
        }
    };

    // ===== Drag & Drop Image Upload =====
    const initImageUpload = ({ dropAreaId, fileInputId, previewId, formId, maxFiles = 10 }) => {
        const dropArea  = document.getElementById(dropAreaId);
        const fileInput = document.getElementById(fileInputId);
        const preview   = document.getElementById(previewId);
        const form      = document.getElementById(formId);
        if (!dropArea || !fileInput || !form) return;

        let selectedFiles = [];

        const fileSignature = f => `${f.name}|${f.size}|${f.lastModified}`;

        const renderPreviews = () => {
            preview.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const wrap = document.createElement('div');
                wrap.className = 'img-wrap';

                const img = document.createElement('img');
                img.className = 'thumb';
                wrap.appendChild(img);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-img';
                removeBtn.innerHTML = '&times;';
                removeBtn.addEventListener('click', () => {
                    selectedFiles.splice(index, 1);
                    renderPreviews();
                });
                wrap.appendChild(removeBtn);

                const reader = new FileReader();
                reader.onload = e => img.src = e.target.result;
                reader.readAsDataURL(file);

                preview.appendChild(wrap);
            });
        };

        const addFiles = fileList => {
            if (!fileList) return;
            const incoming = Array.from(fileList).filter(f => f.type.startsWith('image/'));
            const existingSigs = new Set(selectedFiles.map(fileSignature));

            for (const f of incoming) {
                if (selectedFiles.length >= maxFiles) break;
                if (!existingSigs.has(fileSignature(f))) {
                    selectedFiles.push(f);
                    existingSigs.add(fileSignature(f));
                }
            }
            renderPreviews();
        };

        ['dragenter','dragover','dragleave','drop'].forEach(evt =>
            dropArea.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); })
        );
        dropArea.addEventListener('dragover', () => dropArea.classList.add('drag-over'));
        dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
        dropArea.addEventListener('drop', e => {
            dropArea.classList.remove('drag-over');
            addFiles(e.dataTransfer.files);
        });
        dropArea.addEventListener('click', () => fileInput.click());
        dropArea.addEventListener('keydown', e => {
            if (['Enter',' '].includes(e.key)) {
                e.preventDefault();
                fileInput.click();
            }
        });
        fileInput.addEventListener('change', () => {
            addFiles(fileInput.files);
            fileInput.value = '';
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            const fd = new FormData(form);
            selectedFiles.forEach(f => fd.append('images[]', f));

            fetch(form.action, { method: 'POST', body: fd })
                .then(res => res.text())
                .then(data => {
                    console.log('Server response:', data);
                    alert('Listing saved!');
                    form.reset();
                    selectedFiles = [];
                    renderPreviews();
                })
                .catch(err => console.error('Upload error:', err));
        });
    };

    initImageUpload({
        dropAreaId: 'imageUploadArea',
        fileInputId: 'images',
        previewId: 'imagePreview',
        formId: 'addListingForm',
        maxFiles: 10
    });

    // ===== Modal Handling =====
    window.openModal = id => document.getElementById(`editModal-${id}`).style.display = 'block';
    window.closeModal = id => document.getElementById(`editModal-${id}`).style.display = 'none';
    window.onclick = event => {
        document.querySelectorAll('.edit-modal').forEach(modal => {
            if (event.target === modal) modal.style.display = 'none';
        });
    };

    // ===== Remove Image from Slider =====
    window.removeImage = btn => btn.closest('.slider-item').remove();

    // ===== Init Bedrooms/Bathrooms Toggle for Edit Modals =====
    document.querySelectorAll('.edit-modal').forEach(modal => {
        const typeSelect = modal.querySelector('select[name="property_type"]');
        const bedrooms = modal.querySelector('input[name="bedrooms"]');
        const bathrooms = modal.querySelector('input[name="bathrooms"]');
        if (!typeSelect || !bedrooms || !bathrooms) return;
        toggleRooms(typeSelect, bedrooms, bathrooms);
        typeSelect.addEventListener('change', () => toggleRooms(typeSelect, bedrooms, bathrooms));
    });

    // ===== Init Bedrooms/Bathrooms Toggle for Add Listing =====
    const addType = document.getElementById('property_type');
    const addBeds = document.getElementById('bedrooms');
    const addBaths = document.getElementById('bathrooms');
    if (addType && addBeds && addBaths) {
        toggleRooms(addType, addBeds, addBaths);
        addType.addEventListener('change', () => toggleRooms(addType, addBeds, addBaths));
    }

    // ===== Company Listing Modal =====
    const propertyModal = document.getElementById('propertyModal');
    document.querySelectorAll('.view-details').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const propertyId = link.dataset.id;
            fetch(`/BatEstateExplorer/public/api/get_company_listings.php?id=${propertyId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) { alert(data.error); return; }

                    document.getElementById('propertyTitle').textContent = data.title || 'N/A';

                    const carousel = document.getElementById('carouselImages');
                    carousel.innerHTML = '';
                    if (data.images?.length) {
                        data.images.forEach((img, idx) => {
                            carousel.innerHTML += `
                                <div class="carousel-item ${idx===0?'active':''}">
                                    <img src="storage/uploads/property_images/${img}" class="d-block w-100">
                                </div>`;
                        });
                    } else {
                        carousel.innerHTML = '<div class="carousel-item active"><p>No images</p></div>';
                    }

                    const details = document.getElementById('propertyDetails');
                    details.innerHTML = `
                        <li class="list-group-item"><b>Location:</b> ${data.location}</li>
                        <li class="list-group-item"><b>Price:</b> ₱${parseFloat(data.price).toLocaleString()}</li>
                        <li class="list-group-item"><b>Bedrooms:</b> ${data.bedrooms}</li>
                        <li class="list-group-item"><b>Bathrooms:</b> ${data.bathrooms}</li>
                        <li class="list-group-item"><b>Size:</b> ${data.sqm} sqm</li>
                        <li class="list-group-item"><b>Status:</b> ${data.status}</li>
                        <li class="list-group-item"><b>Created By:</b> ${data.created_by ?? 'N/A'}</li>
                        <li class="list-group-item"><b>Sold By:</b> ${data.sold_by ?? 'N/A'}</li>
                    `;
                })
                .catch(err => console.error(err));
        });
    });

    const openBtn = document.getElementById("openDeleteModal");
    const modal = document.getElementById("deleteModal");
    const cancelBtn = document.getElementById("cancelDeleteBtn");
    const deleteForm = document.getElementById("deleteAgentForm");
    const confirmBtn = document.getElementById("confirmDeleteBtn");
    const spinner = document.getElementById("deleteSpinner");

    openBtn.addEventListener("click", () => {
        modal.style.display = "flex";
    });

    cancelBtn.addEventListener("click", () => {
        modal.style.display = "none";
    });

    deleteForm.addEventListener("submit", function() {
        confirmBtn.style.display = "none";
        spinner.style.display = "flex";
    });

});
</script>
