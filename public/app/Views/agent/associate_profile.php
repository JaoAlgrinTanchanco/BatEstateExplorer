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

// Fetch listings for My Listings tab (associate agent)
$listings = [];

if ($tab === 'my_listings') {
    // First, get the agent record linked to this user
    $stmtAgent = $conn->prepare("SELECT id FROM agents WHERE user_id = ?");
    $stmtAgent->bind_param("i", $user['id']);
    $stmtAgent->execute();
    $res = $stmtAgent->get_result();
    $agent = $res ? $res->fetch_assoc() : null;
    $stmtAgent->close();

    if ($agent) {
        $agent_id = (int)$agent['id'];
        $stmt = $conn->prepare("
            SELECT *
            FROM properties
            WHERE agent_id = ? OR sold_by_agent_id = ?
            ORDER BY created_at DESC
        ");
        if ($stmt) {
            $stmt->bind_param("ii", $agent_id, $agent_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $listings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
        } else {
            error_log("Prepare failed: " . $conn->error);
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
            <h2>My Listings</h2>

            <div class="overview-container">
                <?php if (!empty($listings)): ?>
                    <?php foreach ($listings as $property): 
                        // Ownership
                        $ownership = ($property['agent_id'] == $agent_id) ? 'Owned' : 'Shared';

                        // Images from DB
                        $images_data = unserialize($property['images_blob'] ?? '') ?: [];
                        $first_img_src = '';
                        if (!empty($images_data)) {
                            $first_img_src = "data:{$images_data[0]['type']};base64," . base64_encode($images_data[0]['data']);
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
                            <div class="info-row"><strong>Price:</strong> <span><?= number_format($property['price'], 2) ?></span></div>
                            <div class="info-row"><strong>Bedrooms:</strong> <span><?= htmlspecialchars($property['bedrooms']) ?></span></div>
                            <div class="info-row"><strong>Bathrooms:</strong> <span><?= htmlspecialchars($property['bathrooms']) ?></span></div>
                            <div class="info-row"><strong>Status:</strong> <span><?= htmlspecialchars($property['status']) ?></span></div>
                            <div class="info-row"><strong>Type:</strong> <span><?= $ownership ?></span></div>
                            
                            <div class="info-row actions">
                                <a href="?view=edit_listing&id=<?= $property['id'] ?>" class="btn-edit">Edit</a>
                                <a href="?view=delete_listing&id=<?= $property['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this listing?')">Delete</a>
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
                        <form id="addListingForm" 
                            action="/BatEstateExplorer/public/api/save_listing.php" 
                            method="POST" 
                            enctype="multipart/form-data">

                            <!-- Property Name -->
                            <label for="title"><strong>Property Name</strong></label>
                            <input type="text" id="title" name="title" required>

                            <!-- Location -->
                            <label for="location"><strong>Location</strong></label>
                            <select id="location" name="location" required>
                                <option value="">-- Select Location --</option>
                                <?php 
                                $locations = ["Lipa City", "Batangas City", "Tanauan City", "Balayan", "Santo Tomas"];
                                foreach ($locations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Price -->
                            <label for="price"><strong>Price (₱)</strong></label>
                            <input type="number" id="price" name="price" min="0" step="0.01" required>

                            <!-- Floor Area -->
                            <label for="sqm"><strong>Floor Area (sqm)</strong></label>
                            <input type="number" id="sqm" name="sqm" min="0" step="0.01">

                            <!-- Lot Size -->
                            <label for="lot_size"><strong>Lot Size (sqm)</strong></label>
                            <input type="number" id="lot_size" name="lot_size" min="0" step="0.01">

                            <!-- Property Type -->
                            <label for="property_type"><strong>Property Type</strong></label>
                            <select id="property_type" name="property_type" required>
                                <option value="">-- Select Type --</option>
                                <option value="Property">Property</option>
                                <option value="Lot">Lot</option>
                            </select>

                            <!-- Bedrooms -->
                            <label for="bedrooms"><strong>Bedrooms</strong></label>
                            <select id="bedrooms" name="bedrooms">
                                <option value="">-- Select Bedrooms --</option>
                                <?php for ($i = 0; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>

                            <!-- Bathrooms -->
                            <label for="bathrooms"><strong>Bathrooms</strong></label>
                            <select id="bathrooms" name="bathrooms">
                                <option value="">-- Select Bathrooms --</option>
                                <?php for ($i = 0; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>

                            <!-- Description -->
                            <label for="description"><strong>Description</strong></label>
                            <textarea id="description" name="description" rows="4" required></textarea>

                            <!-- Image Upload -->
                            <label for="images"><strong>Property Images</strong></label>
                            <div id="imageUploadArea" class="drag-drop-area" tabindex="0">
                                <p>Drag & drop images here or click to browse</p>
                                <input type="file" id="images" accept="image/*" multiple style="display:none;">
                            </div>

                            <!-- Preview Area -->
                            <div id="imagePreview" class="image-preview" aria-live="polite"></div>

                            <!-- Submit -->
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
    const dropArea  = document.getElementById('imageUploadArea');
    const fileInput = document.getElementById('images');
    const preview   = document.getElementById('imagePreview');
    const form      = document.getElementById('addListingForm');
    const MAX_FILES = 10;

    let selectedFiles = [];

    const sig = f => `${f.name}|${f.size}|${f.lastModified}`;

    function renderPreviews() {
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
            wrap.appendChild(removeBtn);

            removeBtn.addEventListener('click', () => {
                selectedFiles.splice(index, 1);
                renderPreviews();
            });

            const reader = new FileReader();
            reader.onload = e => img.src = e.target.result;
            reader.readAsDataURL(file);

            preview.appendChild(wrap);
        });
    }

    function addFiles(fileList) {
        if (!fileList) return;
        const incoming = Array.from(fileList).filter(f => f.type.startsWith('image/'));
        const existingSigs = new Set(selectedFiles.map(sig));

        for (const f of incoming) {
            if (selectedFiles.length >= MAX_FILES) break;
            if (!existingSigs.has(sig(f))) {
                selectedFiles.push(f);
                existingSigs.add(sig(f));
            }
        }
        renderPreviews();
    }

    // Drag/drop handlers
    const el = document.querySelector("#someElement");
    if (el) {
        el.addEventListener("click", function () {
            ['dragenter','dragover','dragleave','drop'].forEach(evt =>
                dropArea.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); }, false)
            );
            dropArea.addEventListener('dragover', () => dropArea.classList.add('drag-over'));
            dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
            dropArea.addEventListener('drop', e => {
                dropArea.classList.remove('drag-over');
                addFiles(e.dataTransfer.files);
            });
        });
    }

    // Click to open file picker
    dropArea.addEventListener('click', () => fileInput.click());
    dropArea.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            fileInput.click();
        }
    });
    fileInput.addEventListener('change', () => {
        addFiles(fileInput.files);
        fileInput.value = ''; // allow re-selection of same file
    });

    // Form submission
    form.addEventListener('submit', e => {
        e.preventDefault(); // prevent default form submit

        const fd = new FormData(form);
        selectedFiles.forEach(f => fd.append('images[]', f));

        fetch(form.action, {
            method: 'POST',
            body: fd
        })
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
});
</script>