<?php
    session_start();
    require_once '../config/pdo_database.php';

    // ===== Clear old inputs if user is logged out =====
    if (!isset($_SESSION['user_id'])) {
        unset($_SESSION['old_inputs']);
    }

    // Get logged-in user info
    $user_id = $_SESSION['user_id'] ?? null;
    $user = [];

    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // Get old POST values if redirected after error
    $old = $_SESSION['old_inputs'] ?? [];
    unset($_SESSION['old_inputs']);

    // Get agent type from URL (direct_agent or associate_agent)
    $agent_type_param = $_GET['type'] ?? ($old['user_type'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agent Registration - BatEstate Explorer</title>
<link rel="stylesheet" href="../assets/css/hero.css">
<link rel="stylesheet" href="../assets/css/agent_registration.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
<?php include '../components/notification.php'; ?>

<div class="registration-container">
    <a href="javascript:history.back()" class="back-link">
        <i class="fas fa-arrow-left"></i> Back
    </a>

    <h1><i class="fas fa-user-tie"></i> Agent Registration</h1>
    <p>Join our network of professional real estate agents and start your journey with BatEstate Explorer.</p>

    <form id="agentRegistrationForm" enctype="multipart/form-data">
        <!-- Profile Picture Upload -->
        <div class="form-group profile-pic-group">
            <label>Profile Picture *</label>
            <div class="profile-pic-wrapper">
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                <div class="profile-pic-preview" id="profilePicPreview">
                    <span class="upload-text">Upload Here</span>
                </div>
            </div>
        </div>

        <!-- Personal Info -->
        <h3>Personal Information</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name"
                    value="<?= htmlspecialchars($old['first_name'] ?? $user['first_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name"
                    value="<?= htmlspecialchars($old['last_name'] ?? $user['last_name'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email"
                    value="<?= htmlspecialchars($old['email'] ?? $user['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone"
                    value="<?= htmlspecialchars($old['phone'] ?? $user['phone'] ?? '') ?>">
            </div>
        </div>

        <!-- Address Section -->
        <h3>Address Information</h3>
        <div class="form-group address-section">

        <!-- First Row: Region | Province | City | Barangay -->
        <div class="address-row">
        <!-- Region -->
        <div class="form-group">
            <label for="region">Region</label>
            <select id="region" name="region" required>
            <option value="">Select Region</option>
            </select>
        </div>

        <!-- Province -->
        <div class="form-group">
            <label for="province">Province</label>
            <select id="province" name="province" required>
            <option value="">Select Province</option>
            </select>
        </div>

        <!-- City -->
        <div class="form-group">
            <label for="city">City / Municipality</label>
            <select id="city" name="city" required>
            <option value="">Select City / Municipality</option>
            </select>
        </div>

        <!-- Barangay -->
        <div class="form-group">
            <label for="barangay">Barangay</label>
            <select id="barangay" name="barangay" required>
            <option value="">Select Barangay</option>
            </select>
        </div>
        </div>

        <!-- Second Row: Street / Postal Code -->
        <div class="address-row-2">
            <div class="form-group">
            <label for="street">Street / Building / House No.</label>
            <input type="text" id="street" name="street" placeholder="e.g. P. Torres St. Bldg 21 Lot 2" required>
            </div>

            <div class="form-group">
            <label for="postal_code">Postal Code</label>
            <input type="text" id="postal_code" name="postal_code" placeholder="e.g. 4217" required>
            </div>
        </div>
        </div>

        <h3>Security Information</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
        </div>

        <div class="form-group">
            <label for="user_type">Agent Type *</label>
            <select id="user_type" name="user_type">
                <option value="">Select Agent Type</option>
                <option value="direct_agent" <?= ($old['user_type'] ?? $agent_type_param) === 'direct_agent' ? 'selected' : '' ?>>Direct Agent</option>
                <option value="associate_agent" <?= ($old['user_type'] ?? $agent_type_param) === 'associate_agent' ? 'selected' : '' ?>>Associate Agent</option>
            </select>
        </div>

        <!-- Professional Info -->
        <h3>Professional Information</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="broker_id">Broker ID *</label>
                <input type="text" id="broker_id" name="broker_id"
                    value="<?= htmlspecialchars($old['broker_id'] ?? $user['broker_id'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="prc_number">PRC Number *</label>
                <input type="text" id="prc_number" name="prc_number"
                    value="<?= htmlspecialchars($old['prc_number'] ?? $user['prc_number'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="experience_years">Years of Experience *</label>
                <select id="experience_years" name="experience_years">
                    <option value="">Select Experience</option>
                    <?php
                    $exp_options = ['0-1','2-5','6-10','10+'];
                    foreach($exp_options as $exp) {
                        $selected = ($old['experience_years'] ?? $user['experience_years'] ?? '') === $exp ? 'selected' : '';
                        echo "<option value=\"$exp\" $selected>$exp years</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group specialization-group">
                <label for="specializationSelect">Specializations *</label>
                <div class="specialization-select-row">
                    <select id="specializationSelect" class="form-control">
                        <option value="" disabled selected>Select a specialization</option>
                        <option value="Condominium">Condominium</option>
                        <option value="Apartment">Apartment</option>
                        <option value="Townhouse">Townhouse</option>
                        <option value="House and Lot">House and Lot</option>
                        <option value="Commercial Building">Commercial Building</option>
                        <option value="Lot Only">Lot Only</option>
                        <option value="Farm Lot">Farm Lot</option>
                        <option value="Industrial Lot">Industrial Lot</option>
                        <option value="Beachfront Property">Beachfront Property</option>
                        <option value="Resort">Resort</option>
                        <option value="Hotels and Motels">Hotels and Motels</option>
                        <option value="Dormitory">Dormitory</option>
                        <option value="Office Space">Office Space</option>
                        <option value="Warehouse">Warehouse</option>
                        <option value="Retail Space">Retail Space</option>
                        <option value="Mixed-Use Development">Mixed-Use Development</option>
                        <option value="Luxury Estate">Luxury Estate</option>
                        <option value="Foreclosed Property">Foreclosed Property</option>
                        <option value="Subdivision Development">Subdivision Development</option>
                        <option value="Others">Others</option>
                    </select>

                    <button type="button" id="selectAllSpecializations" class="select-all-btn">
                        Select All
                    </button>
                </div>
            </div>

            <div class="form-group specialization-tags-group">
                <div class="specialization-tags" id="specializationTags"></div>
            </div>

            <input type="hidden" name="specializations" id="specializationInput"
                value="<?= htmlspecialchars($old_inputs['specializations'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="experience_details">Experience Details (Optional)</label>
            <textarea id="experience_details" name="experience_details" rows="4"
                placeholder="Describe your real estate experience and achievements"><?= htmlspecialchars($old['experience_details'] ?? $user['experience_details'] ?? '') ?></textarea>
        </div>

        <!-- Education -->
        <h3>Educational Background (Optional)</h3>
        <p class="note-text">You may skip this section if not applicable.</p>

        <div class="form-row">
            <div class="form-group">
                <label for="education">Education Level</label>
                <select id="education" name="education">
                    <option value="">-- Select Education Level (Optional) --</option>
                    <?php
                    $edu_levels = ['High School','Associate','Bachelor','Master','PhD'];
                    foreach($edu_levels as $edu) {
                        $selected = ($old['education'] ?? $user['education'] ?? '') === $edu ? 'selected' : '';
                        echo "<option value=\"$edu\" $selected>$edu</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="school">School / University</label>
                <input type="text" id="school" name="school"
                    placeholder="e.g. Batangas State University"
                    value="<?= htmlspecialchars($old['school'] ?? $user['school'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="course">Course / Major</label>
                <input type="text" id="course" name="course"
                    placeholder="e.g. BS Real Estate Management"
                    value="<?= htmlspecialchars($old['course'] ?? $user['course'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="graduation_year">Graduation Year</label>
                <input type="number" id="graduation_year" name="graduation_year" min="1950" max="2030"
                    placeholder="Optional"
                    value="<?= htmlspecialchars($old['graduation_year'] ?? $user['graduation_year'] ?? '') ?>">
            </div>
        </div>

        <!-- Certifications & Training -->
        <h3>Certifications & Training</h3>
        <div class="form-group">
            <label for="certifications">Professional Certifications (Optional)</label>
            <textarea id="certifications" name="certifications" rows="3"><?= htmlspecialchars($old['certifications'] ?? $user['certifications'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label for="training">Additional Training (Optional)</label>
            <textarea id="training" name="training" rows="3"><?= htmlspecialchars($old['training'] ?? $user['training'] ?? '') ?></textarea>
        </div>

        <!-- Company (Associate Agents) -->
        <div id="company-field" style="display:none;">
            <label for="company_id">Select Company (Associate Agent only):</label>
            <select name="company_id" id="company_id" class="form-control">
                <option value="" disabled selected>-- Select Company --</option>
                <?php
                try {
                    $stmt = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $selected = ($old['company_id'] ?? $user['company_id'] ?? '') == $row['id'] ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($row['id']) . '" ' . $selected . '>' . htmlspecialchars($row['name']) . '</option>';
                    }
                } catch (PDOException $e) {
                    echo '<option disabled>Error loading companies</option>';
                }
                ?>
            </select>
        </div>

        <!-- Required Documents (Direct Agents) -->
        <div id="required-documents" style="display:none;">
            <h3>Required Documents</h3>
            <p>Please upload clear copies of the following (JPG, PNG, PDF, DOC, DOCX | Max: 5MB each)</p>
            <div class="form-group">
                <label for="broker_license">Broker's License *</label>
                <input type="file" id="broker_license" name="documents[broker_license]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
            </div>
            <div class="form-group">
                <label for="prc_license">PRC License *</label>
                <input type="file" id="prc_license" name="documents[prc_license]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
            </div>
            <div class="form-group">
                <label for="resume">Resume / CV *</label>
                <input type="file" id="resume" name="documents[resume]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
            </div>
            <div class="form-group">
                <label for="valid_id">Valid Government ID *</label>
                <input type="file" id="valid_id" name="documents[valid_id]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
            </div>
        </div>

        <div class="form-group">
            <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Submit Application</button>
        </div>
    </form>
</div>

<!-- AJAX Notification Container -->
<div id="ajax-notification-container" class="notification-container"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // === DOM ELEMENTS ===
        const form = document.getElementById('agentRegistrationForm');
        const userType = document.getElementById('user_type');
        const docsSection = document.getElementById('required-documents');
        const companyField = document.getElementById('company-field');
        const companySelect = document.getElementById('company_id');
        const ajaxContainer = document.getElementById('ajax-notification-container');

        const specializationSelect = document.getElementById('specializationSelect');
        const tagsContainer = document.getElementById('specializationTags');
        const hiddenInput = document.getElementById('specializationInput');

        const profileInput = document.getElementById('profile_picture');
        const profilePreview = document.getElementById('profilePicPreview');

        let selectedTags = [];

        // === SPECIALIZATION TAG LOGIC ===
        specializationSelect.addEventListener('change', () => {
            const value = specializationSelect.value;
            if (!value || selectedTags.includes(value)) {
                specializationSelect.selectedIndex = 0;
                return;
            }
            selectedTags.push(value);
            renderTags();
            specializationSelect.selectedIndex = 0;
        });

        function renderTags() {
            tagsContainer.innerHTML = '';
            selectedTags.forEach(tagValue => {
                const tag = document.createElement('span');
                tag.className = 'specialization-tag';
                tag.innerHTML = `
                    ${tagValue}
                    <button type="button" class="remove-tag" data-value="${tagValue}" aria-label="Remove tag">&times;</button>
                `;
                tagsContainer.appendChild(tag);
            });
            hiddenInput.value = selectedTags.join(', ');
        }

        // === SELECT ALL SPECIALIZATIONS BUTTON ===
        const selectAllBtn = document.getElementById('selectAllSpecializations');
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', () => {
                const allOptions = Array.from(specializationSelect.options)
                    .map(opt => opt.value)
                    .filter(v => v && v !== '' && v !== 'Others'); // skip placeholder and "Others"

                selectedTags = Array.from(new Set([...selectedTags, ...allOptions]));
                renderTags();
            });
        }

        tagsContainer.addEventListener('click', e => {
            if (e.target.classList.contains('remove-tag')) {
                const value = e.target.dataset.value;
                selectedTags = selectedTags.filter(v => v !== value);
                renderTags();
            }
        });

        // === FILE GROUPS (for easier toggle) ===
        const docInputs = {
            associate: ['broker_license', 'prc_license', 'resume', 'valid_id'],
            direct: ['valid_id', 'property_location', 'property_image', 'property_document']
        };

        // Create new Direct Agent document inputs dynamically
        const newDirectDocs = [
            { id: 'property_location', label: 'Property Location *' },
            { id: 'property_image', label: 'At least One Property Image *' },
            { id: 'property_document', label: 'Property Document *' }
        ];

        function ensureDirectDocFields() {
            const locations = [
                "Agoncillo","Alitagtag","Balayan","Balete","Batangas City","Bauan","Calaca","Calatagan","Cuenca",
                "Ibaan","Laurel","Lemery","Lian","Lipa City","Lobo","Mabini","Malvar","Mataasnakahoy","Nasugbu",
                "Padre Garcia","Rosario","San Jose","San Juan","San Luis","San Nicolas","San Pascual",
                "Santa Teresita","Santo Tomas","Taal","Talisay","Tanauan City","Taysan","Tingloy","Tuy"
            ];

            // Broker/PRC/Resume/Valid ID already exist above
            const directDocFields = [
                {
                    id: "property_location",
                    label: "Property Location *",
                    html: `
                        <label for="property_location"><strong>Property Location *</strong></label>
                        <select id="property_location" name="documents[property_location]" required>
                            <option value="">Select Location</option>
                            ${locations.map(l => `<option value="${l}">${l}</option>`).join('')}
                        </select>
                    `
                },
                {
                    id: "property_image",
                    label: "At least One Property Image *",
                    html: `<label for="property_image">At least One Property Image *</label>
                        <input type="file" id="property_image" name="documents[property_image]" accept=".jpg,.jpeg,.png" required>`
                },
                {
                    id: "property_document",
                    label: "Property Document *",
                    html: `<label for="property_document">Property Document *</label>
                        <input type="file" id="property_document" name="documents[property_document]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>`
                }
            ];

            directDocFields.forEach(doc => {
                if (!document.getElementById(doc.id)) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'form-group';
                    wrapper.innerHTML = doc.html;
                    docsSection.appendChild(wrapper);
                }
            });
        }
        ensureDirectDocFields();

        // === TOGGLE FIELDS BASED ON AGENT TYPE ===
        function toggleFields() {
            const type = userType.value;

            // Always show the section if either type selected
            docsSection.style.display = type ? 'block' : 'none';
            companyField.style.display = (type === 'associate_agent') ? 'block' : 'none';
            companySelect.required = (type === 'associate_agent');

            // Hide all groups and remove required from everything first
            docsSection.querySelectorAll('.form-group').forEach(g => {
                g.style.display = 'none';
                const input = g.querySelector('input, select, textarea');
                if (input) input.removeAttribute('required');
            });

            if (type === 'associate_agent') {
                // Show Associate documents
                docInputs.associate.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.closest('.form-group').style.display = 'block';
                        el.setAttribute('required', 'required');
                    }
                });
            } 
            else if (type === 'direct_agent') {
                // Show Direct documents
                docInputs.direct.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.closest('.form-group').style.display = 'block';
                        el.setAttribute('required', 'required');
                    }
                });
            }

            // If nothing selected, hide section entirely
            if (!type) {
                docsSection.style.display = 'none';
            }
        }

        toggleFields();
        userType.addEventListener('change', toggleFields);

        // === PROFILE PICTURE PREVIEW ===
        profilePreview.addEventListener('click', () => profileInput.click());

        profileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) {
                profilePreview.innerHTML = '<span class="upload-text">Upload Here</span>';
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                profilePreview.innerHTML = `<img src="${event.target.result}" alt="Profile Picture">`;
            };
            reader.readAsDataURL(file);
        });

        // === NOTIFICATION HELPER ===
        window.showAjaxNotification = (message, type = 'success') => {
            if (!ajaxContainer) return;

            const notif = document.createElement('div');
            notif.className = `notification ${type}`;
            notif.innerHTML = `
                <div class="notification__icon"></div>
                <div class="notification__title">${message}</div>
                <div class="notification__close" aria-label="Close">&times;</div>
            `;
            ajaxContainer.appendChild(notif);

            setTimeout(() => notif.remove(), 5000);
            notif.querySelector('.notification__close').addEventListener('click', () => notif.remove());
        };

        // === PROFILE PIC VALIDATION ===
        function validateProfilePicture() {
            const file = profileInput.files[0];
            if (!file) {
                showAjaxNotification('Please upload a profile picture before submitting.', 'error');
                profileInput.focus();
                return false;
            }

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showAjaxNotification('Invalid image type. Please use JPG, PNG, or WEBP.', 'error');
                profileInput.value = '';
                profilePreview.innerHTML = '<span class="upload-text">Upload Here</span>';
                return false;
            }

            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                showAjaxNotification('Profile picture must be smaller than 2 MB.', 'error');
                profileInput.value = '';
                profilePreview.innerHTML = '<span class="upload-text">Upload Here</span>';
                return false;
            }

            return true;
        }

        // === AJAX FORM SUBMIT ===
        form.addEventListener('submit', async e => {
            e.preventDefault();
            if (!validateProfilePicture()) return;

            const formData = new FormData(form);
            formData.append('ajax', 1);

            try {
                const res = await fetch('../public/api/agent_registration_complete.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await res.json();
                showAjaxNotification(data.message || 'No message from server.', data.status);

                if (data.status === 'success') {
                    form.reset();
                    selectedTags = [];
                    renderTags();
                    toggleFields();
                    profilePreview.innerHTML = '<span class="upload-text">Upload Here</span>';
                }
            } catch (err) {
                console.error(err);
                showAjaxNotification('An error occurred. Please try again.', 'error');
            }
        });
    });
    document.addEventListener("DOMContentLoaded", function () {
        const regionSelect = document.getElementById("region");
        const provinceSelect = document.getElementById("province");
        const citySelect = document.getElementById("city");
        const barangaySelect = document.getElementById("barangay");

        // === REGION → PROVINCES ===
        const regionProvinces = {
        "Region I (Ilocos Region)": ["Ilocos Norte", "Ilocos Sur", "La Union", "Pangasinan"],
        "Region II (Cagayan Valley)": ["Batanes", "Cagayan", "Isabela", "Nueva Vizcaya", "Quirino"],
        "Region III (Central Luzon)": ["Aurora", "Bataan", "Bulacan", "Nueva Ecija", "Pampanga", "Tarlac", "Zambales"],
        "Region IV-A (CALABARZON)": ["Batangas", "Cavite", "Laguna", "Quezon", "Rizal"],
        "MIMAROPA Region (Region IV-B)": ["Marinduque", "Occidental Mindoro", "Oriental Mindoro", "Palawan", "Romblon"],
        "Region V (Bicol Region)": ["Albay", "Camarines Norte", "Camarines Sur", "Catanduanes", "Masbate", "Sorsogon"],
        "Region VI (Western Visayas)": ["Aklan", "Antique", "Capiz", "Guimaras", "Iloilo", "Negros Occidental"],
        "Region VII (Central Visayas)": ["Bohol", "Cebu", "Negros Oriental", "Siquijor"],
        "Region VIII (Eastern Visayas)": ["Biliran", "Eastern Samar", "Leyte", "Northern Samar", "Samar", "Southern Leyte"],
        "Region IX (Zamboanga Peninsula)": ["Zamboanga del Norte", "Zamboanga del Sur", "Zamboanga Sibugay"],
        "Region X (Northern Mindanao)": ["Bukidnon", "Camiguin", "Lanao del Norte", "Misamis Occidental", "Misamis Oriental"],
        "Region XI (Davao Region)": ["Davao de Oro", "Davao del Norte", "Davao del Sur", "Davao Occidental", "Davao Oriental"],
        "Region XII (SOCCSKSARGEN)": ["Cotabato", "Sarangani", "South Cotabato", "Sultan Kudarat"],
        "Region XIII (Caraga)": ["Agusan del Norte", "Agusan del Sur", "Dinagat Islands", "Surigao del Norte", "Surigao del Sur"],
        "NCR (National Capital Region)": ["Metro Manila"],
        "CAR (Cordillera Administrative Region)": ["Abra", "Apayao", "Benguet", "Ifugao", "Kalinga", "Mountain Province"],
        "BARMM (Bangsamoro Autonomous Region in Muslim Mindanao)": ["Basilan", "Lanao del Sur", "Maguindanao del Norte", "Maguindanao del Sur", "Sulu", "Tawi-Tawi"]
        };

        // === PROVINCE → CITIES ===
        const provinceCities = {
        "Abra": ["Bangued"],
        "Agusan del Norte": ["Butuan City", "Cabadbaran City"],
        "Agusan del Sur": ["Bayugan City"],
        "Aklan": ["Kalibo"],
        "Albay": ["Legazpi City", "Tabaco City", "Ligao City"],
        "Antique": ["San Jose de Buenavista"],
        "Apayao": ["Kabugao"],
        "Aurora": ["Baler"],
        "Basilan": ["Isabela City", "Lamitan City"],
        "Bataan": ["Abucay", "Bagac", "Balanga City", "Dinalupihan", "Mariveles", "Orani"],
        "Batanes": ["Basco"],
        "Batangas": ["Batangas City", "Lipa City", "Tanauan City", "Sto. Tomas City", "San Jose"],
        "Benguet": ["Baguio City", "La Trinidad"],
        "Biliran": ["Naval"],
        "Bohol": ["Tagbilaran City"],
        "Bukidnon": ["Malaybalay City", "Valencia City"],
        "Bulacan": ["Malolos City", "Meycauayan City", "San Jose del Monte City"],
        "Cagayan": ["Tuguegarao City"],
        "Camarines Norte": ["Daet"],
        "Camarines Sur": ["Naga City", "Iriga City"],
        "Camiguin": ["Mambajao"],
        "Capiz": ["Roxas City"],
        "Catanduanes": ["Virac"],
        "Cavite": [
            "Cavite City", "Bacoor City", "Dasmariñas City", "Imus City",
            "Tagaytay City", "Trece Martires City", "General Trias City"
        ],
        "Cebu": ["Cebu City", "Mandaue City", "Lapu-Lapu City", "Toledo City", "Bogo City", "Carcar City", "Talisay City", "Danao City", "Naga City"],
        "Cotabato": ["Kidapawan City"],
        "Davao de Oro": ["Nabunturan"],
        "Davao del Norte": ["Tagum City", "Panabo City", "Samal City"],
        "Davao del Sur": ["Davao City", "Digos City"],
        "Davao Occidental": ["Malita"],
        "Davao Oriental": ["Mati City"],
        "Dinagat Islands": ["San Jose"],
        "Eastern Samar": ["Borongan City"],
        "Guimaras": ["Jordan"],
        "Ifugao": ["Lagawe"],
        "Ilocos Norte": ["Laoag City"],
        "Ilocos Sur": ["Vigan City", "Candon City"],
        "Iloilo": ["Iloilo City", "Passi City"],
        "Isabela": ["Ilagan City", "Cauayan City", "Santiago City"],
        "Kalinga": ["Tabuk City"],
        "La Union": ["San Fernando City"],
        "Laguna": ["Calamba City", "Biñan City", "Santa Rosa City", "San Pedro City", "San Pablo City", "Cabuyao City"],
        "Lanao del Norte": ["Iligan City"],
        "Lanao del Sur": ["Marawi City"],
        "Leyte": ["Tacloban City", "Ormoc City", "Baybay City"],
        "Maguindanao del Norte": ["Datu Odin Sinsuat"],
        "Maguindanao del Sur": ["Buluan"],
        "Marinduque": ["Boac"],
        "Masbate": ["Masbate City"],
        "Misamis Occidental": ["Oroquieta City", "Ozamiz City", "Tangub City"],
        "Misamis Oriental": ["Cagayan de Oro City", "Gingoog City"],
        "Mountain Province": ["Bontoc"],
        "Negros Occidental": [
            "Bacolod City", "Bago City", "Cadiz City", "Escalante City",
            "Himamaylan City", "Kabankalan City", "La Carlota City", "San Carlos City",
            "Silay City", "Sipalay City", "Talisay City", "Victorias City"
        ],
        "Negros Oriental": [
            "Dumaguete City", "Bais City", "Bayawan City", "Canlaon City",
            "Guihulngan City", "Tanjay City"
        ],
        "Northern Samar": ["Catarman"],
        "Nueva Ecija": ["Cabanatuan City", "Palayan City", "Gapan City", "San Jose City", "Science City of Muñoz"],
        "Nueva Vizcaya": ["Bayombong"],
        "Occidental Mindoro": ["Mamburao"],
        "Oriental Mindoro": ["Calapan City"],
        "Palawan": ["Puerto Princesa City"],
        "Pampanga": ["Angeles City", "San Fernando City", "Mabalacat City"],
        "Pangasinan": ["Dagupan City", "San Carlos City", "Alaminos City", "Urdaneta City"],
        "Quezon": ["Lucena City", "Tayabas City"],
        "Quirino": ["Cabarroguis"],
        "Rizal": ["Antipolo City"],
        "Romblon": ["Romblon"],
        "Samar": ["Catbalogan City", "Calbayog City"],
        "Sarangani": ["Alabel"],
        "Siquijor": ["Siquijor"],
        "Sorsogon": ["Sorsogon City"],
        "South Cotabato": ["Koronadal City", "General Santos City"],
        "Southern Leyte": ["Maasin City"],
        "Sultan Kudarat": ["Isulan", "Tacurong City"],
        "Sulu": ["Jolo"],
        "Surigao del Norte": ["Surigao City"],
        "Surigao del Sur": ["Tandag City", "Bislig City"],
        "Tarlac": ["Tarlac City"],
        "Tawi-Tawi": ["Bongao"],
        "Zambales": ["Olongapo City"],
        "Zamboanga del Norte": ["Dipolog City", "Dapitan City"],
        "Zamboanga del Sur": ["Pagadian City", "Zamboanga City"],
        "Zamboanga Sibugay": ["Ipil"],
        "Metro Manila": [
            "Manila City", "Quezon City", "Caloocan City", "Makati City", "Pasay City",
            "Pasig City", "Taguig City", "Mandaluyong City", "Marikina City",
            "Muntinlupa City", "Parañaque City", "Navotas City", "Malabon City",
            "Valenzuela City", "Las Piñas City", "San Juan City", "Pateros"
        ]
        };

        // === CITY → BARANGAYS ===
        const cityBarangays = {
        // === Batangas Province ===
        "Lipa City": [
            "Anilao", "Bagong Pook", "Balintawak", "Banay-Banay", "Bolbok", "Bulacnin",
            "Calamias", "Cumba", "Dagatan", "Duhatan", "Halang", "Inosloban", "Latag",
            "Lodlod", "Marawoy", "Mataas na Lupa", "Poblacion Barangay 1", "Poblacion Barangay 2",
            "Poblacion Barangay 3", "Poblacion Barangay 4", "Sabang", "Sampaguita", "San Carlos",
            "San Celestino", "San Francisco", "San Isidro", "San Jose", "San Salvador",
            "Santo Niño", "Santo Toribio", "Sico", "Tangob", "Tambo", "Tanguay", "Tibig"
        ],
        "Batangas City": [
            "Alangilan", "Bagong Pook", "Balagtas", "Calicanto", "Concepcion", "Kumintang Ibaba",
            "Kumintang Ilaya", "Libjo", "Pallocan West", "Sampaga", "Sta. Clara", "Sta. Rita",
            "Alangilan", "Pallocan East", "San Jose Sico", "Santa Rita Aplaya", "Talumpok Silangan"
        ],
        "Sto. Tomas City": [
            "San Roque", "San Pedro", "San Miguel", "San Vicente", "San Rafael", "San Felix",
            "San Bartolome", "San Francisco", "San Juan", "San Antonio"
        ],

        // === Laguna Province ===
        "Calamba City": [
            "Bañadero", "Banlic", "Barandal", "Bubuyan", "Canlubang", "Halang", "Looc", "Makiling",
            "Paciano Rizal", "Palingon", "Pansol", "Parian", "Real", "Saimsim", "Sirang Lupa", "Ulango"
        ],

        // === Cavite Province ===
        "Dasmariñas City": [
            "Burol", "Paliparan I", "Paliparan II", "Paliparan III", "Salitran I", "Salitran II", "Salitran III",
            "Sabang", "San Agustin I", "San Agustin II", "San Agustin III", "San Jose", "Sta. Cristina I",
            "Sta. Cristina II", "Victoria Reyes", "Zone I", "Zone II", "Zone III", "Zone IV"
        ],

        // === NCR ===
        "Quezon City": [
            "Alicia", "Amihan", "Bagong Pag-asa", "Bagumbayan", "Bagumbuhayan", "Batasan Hills",
            "Commonwealth", "Diliman", "Don Antonio", "Fairview", "Gulod", "Holy Spirit", "Kalusugan",
            "Kamuning", "Old Balara", "Payatas", "Project 6", "Socorro", "Tandang Sora", "UP Campus"
        ],
        "Manila City": [
            "Ermita", "Intramuros", "Malate", "Paco", "Pandacan", "Port Area", "Quiapo", "Sampaloc",
            "San Andres", "San Miguel", "San Nicolas", "Santa Ana", "Santa Cruz", "Santa Mesa",
            "Tondo I", "Tondo II"
        ],
        "Taguig City": [
            "Bagumbayan", "Bambang", "Calzada", "Central Bicutan", "Central Signal Village",
            "Hagonoy", "Ibayo-Tipas", "Ligid-Tipas", "Maharlika Village", "Napindan", "North Daang Hari",
            "Palingon", "Pinagsama", "San Miguel", "Santa Ana", "South Daang Hari", "Upper Bicutan", "Wawa"
        ],
        "Makati City": [
            "Bangkal", "Bel-Air", "Cembo", "Comembo", "Dasmariñas", "East Rembo", "Forbes Park",
            "Guadalupe Nuevo", "La Paz", "Magallanes", "Olympia", "Palanan", "Pembo", "Pio del Pilar",
            "Poblacion", "San Antonio", "San Isidro", "San Lorenzo", "Urdaneta Village", "West Rembo"
        ],

        // === Visayas ===
        "Cebu City": [
            "Apas", "Basak Pardo", "Banilad", "Capitol Site", "Guadalupe", "Inayawan", "Lahug", "Luz",
            "Mabolo", "Pahina Central", "Pardo", "Sambag I", "Sambag II", "Talamban", "Tisa", "Zapatera"
        ],
        "Iloilo City": [
            "Arevalo", "City Proper", "Jaro", "La Paz", "Lapuz", "Mandurriao", "Molo"
        ],
        "Bacolod City": [
            "Alijis", "Banago", "Estefania", "Granada", "Handumanan", "Mandalagan", "Mansilingan",
            "Singcang-Airport", "Sum-ag", "Tangub", "Villamonte", "Vista Alegre", "Pahanocoy", "Barangay 1", "Barangay 2"
        ],

        // === Mindanao ===
        "Davao City": [
            "Agdao", "Buhangin", "Bunawan", "Calinan", "Marilog", "Talomo", "Toril", "Tugbok", "Paquibato"
        ],
        "Zamboanga City": [
            "Ayala", "Baliwasan", "Calarian", "Divisoria", "Guiwan", "Putik", "Sta. Maria", "Tetuan",
            "Tugbungan", "Zone I (Pasonanca)", "Tumaga", "San Roque"
        ],
        "General Santos City": [
            "Apopong", "Baluan", "Bula", "Calumpang", "City Heights", "Dadiangas East",
            "Dadiangas North", "Dadiangas South", "Labangal", "Lagao", "San Isidro", "Tambler"
        ],
        "Cagayan de Oro City": [
            "Balulang", "Bulua", "Carmen", "Gusa", "Iponan", "Kauswagan", "Lapasan",
            "Lumbia", "Macasandig", "Nazareth", "Patag", "Puntod", "Tignapoloan", "Tumpagon"
        ]
        };

        // Helper function to reset and populate a dropdown
        function populateDropdown(selectElement, items, placeholder) {
            selectElement.innerHTML = `<option value="">${placeholder}</option>`;
            items.forEach(item => {
                const opt = document.createElement("option");
                opt.value = item;
                opt.textContent = item;
                selectElement.appendChild(opt);
            });
            selectElement.disabled = items.length === 0;
        }

        // When region changes
        regionSelect.addEventListener("change", function () {
            const provinces = regionProvinces[this.value] || [];
            populateDropdown(provinceSelect, provinces, "Select Province");
            populateDropdown(citySelect, [], "Select City / Municipality");
            populateDropdown(barangaySelect, [], "Select Barangay");
        });

        // When province changes
        provinceSelect.addEventListener("change", function () {
            const cities = provinceCities[this.value] || [];
            populateDropdown(citySelect, cities, "Select City / Municipality");
            populateDropdown(barangaySelect, [], "Select Barangay");
        });

        // When city changes
        citySelect.addEventListener("change", function () {
            const barangays = cityBarangays[this.value] || [];
            populateDropdown(barangaySelect, barangays, "Select Barangay");
        });

        // Populate Region dropdown on load
        populateDropdown(regionSelect, Object.keys(regionProvinces), "Select Region");
    });
</script>

</body>
</html>
