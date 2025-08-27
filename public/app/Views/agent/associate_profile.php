<?php
if (!isset($user)) die('Access denied.');

// Display flash messages
if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">
        <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger">
        <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
    </div>
<?php endif; ?>

<?php
// Detect active tab
$tab = $_GET['tab'] ?? 'overview';

$listings = [];

if ($tab === 'my_listings') {
    // Get agent record
    $stmtAgent = $conn->prepare("SELECT id FROM agents WHERE user_id = ?");
    $stmtAgent->bind_param("i", $user['id']);
    $stmtAgent->execute();
    $res = $stmtAgent->get_result();
    $agent = $res ? $res->fetch_assoc() : null;
    $stmtAgent->close();

    if ($agent) {
        $agent_id = (int)$agent['id'];

        // Fetch properties
        $stmt = $conn->prepare("
            SELECT *
            FROM properties
            WHERE agent_id = ? OR sold_by_agent_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("ii", $agent_id, $agent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // For each property, fetch its images
        foreach ($properties as $property) {
            $stmtImg = $conn->prepare("
                SELECT image_path 
                FROM property_images 
                WHERE property_id = ? 
                ORDER BY is_primary DESC, id ASC
            ");
            $stmtImg->bind_param("i", $property['id']);
            $stmtImg->execute();
            $resImg = $stmtImg->get_result();
            $images = $resImg ? $resImg->fetch_all(MYSQLI_ASSOC) : [];
            $stmtImg->close();

            // Add images to property array
            $property['images'] = $images;
            $listings[] = $property;
        }
    }
}

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
                        <div class="info-row"><strong>Listing Type:</strong> <span><?= $ownership ?></span></div>
                        
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
                                    <!-- Add all locations like before -->
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

                                <button type="submit">Update Listing</button>
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
                <p>Charts, leads, and sales data here.</p>
        <?php break; ?>

        <?php case 'company_listings': ?>
                <h2>Company Listings</h2>
                <p>List of all properties from your company.</p>

                <?php
                // Fetch all properties with agent info
                $stmt = $conn->prepare("
                    SELECT 
                        p.id, 
                        p.title,  -- use title instead of image
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
                    ORDER BY p.created_at DESC
                ");

                $stmt->execute();
                $res = $stmt->get_result();
                ?>

                <table border="1" cellpadding="8" cellspacing="0" width="100%">
                    <thead>
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
                            <th>Actions</th>
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
                                <td>
                                    <a href="#" 
                                    class="view-details" 
                                    data-id="<?= $row['id']; ?>" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#propertyModal">Details</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <!-- Modal placed at bottom of page, hidden until triggered -->
                <div class="modal fade" id="propertyModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="propertyTitle">Property Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Image slider -->
                                <div id="propertyCarousel" class="carousel slide mb-3" data-bs-ride="carousel">
                                <div class="carousel-inner" id="carouselImages"></div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                                </div>

                                <!-- Property details -->
                                <ul class="list-group" id="propertyDetails"></ul>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
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

});
</script>
