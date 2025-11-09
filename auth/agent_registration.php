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

    // Only proceed if there’s a logged-in user and not an admin
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type != 'admin' LIMIT 1");
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
<title>Agent Registration - BatEstateExplorer</title>
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
    <p>Join our network of professional real estate agents and start your journey with BatEstateExplorer.</p>

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
            <button type="button" id="openTermsModal" class="submit-btn">
                <i class="fas fa-paper-plane"></i> Submit Application
            </button>
        </div>
    </form>
</div>

<!-- Terms & Conditions Modal -->
<div id="termsModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Terms and Conditions</h2>
        <div class="terms-text" style="max-height:400px; overflow-y:auto; padding: 0.5rem 0;">
            <p><strong>BatEstateExplorer:</strong> A Web-Based System for Property and Lot Inquiry in the Province of Batangas</p>
            <p><strong>Last Updated:</strong> October 2025</p>
            <p>Welcome to BatEstateExplorer. These Terms and Conditions (“Terms”) govern your access to and use of our website and services. By accessing or using BatEstateExplorer, you agree to comply with and be bound by these Terms. If you do not agree, you may not use the system.</p>

            <h3>1. Purpose of the System</h3>
            <p>BatEstateExplorer is an online platform designed to help users search, view, and inquire about available properties and lots within the province of Batangas. The system provides property listings, search filters, and a messaging feature for communication between users and property owners or agents.</p>

            <h3>2. User Accounts</h3>
            <ul>
                <li>To access certain features, users are required to create an account.</li>
                <li>Users must provide accurate and complete information during registration.</li>
                <li>Users are responsible for maintaining the confidentiality of their account credentials and all activities under their account.</li>
                <li>BatEstateExplorer reserves the right to suspend or terminate any account that provides false information or violates these Terms.</li>
            </ul>

            <h3>3. Use of the System</h3>
            <ul>
                <li>Use BatEstateExplorer solely for lawful purposes related to property and lot inquiries.</li>
                <li>Avoid posting or transmitting false, misleading, or unauthorized content.</li>
                <li>Refrain from attempting unauthorized access to the system, its database, or other users’ accounts.</li>
                <li>Any misuse of the platform may result in account suspension or permanent ban.</li>
            </ul>

            <h3>4. Property Listings</h3>
            <ul>
                <li>Property listings are provided by property owners, agents, or authorized representatives.</li>
                <li>BatEstateExplorer does not own, sell, or lease any of the properties listed on the platform.</li>
                <li>The system serves only as an intermediary between property listers and interested inquirers.</li>
                <li>Any documents submitted by registered agents or property owners will not be publicly posted and are for verification only.</li>
                <li>The platform is not responsible for inaccuracies, omissions, or changes in property details provided by listers.</li>
            </ul>

            <h3>5. Messaging Feature</h3>
            <ul>
                <li>Users must use polite and respectful language.</li>
                <li>Avoid sharing personal, financial, or sensitive information outside the platform.</li>
                <li>Refrain from sending spam or unsolicited messages.</li>
                <li>BatEstateExplorer reserves the right to monitor messages for safety and compliance.</li>
            </ul>

            <h3>6. Privacy, Data Protection, and Agent Registration</h3>
            <ul>
                <li>Personal data collected will be handled in accordance with the Data Privacy Act of 2012 (RA 10173).</li>
                <li>Information like name, email, and contact details will only be used to facilitate property inquiries and system functionality.</li>
                <li>Direct and associate agents must register and submit valid ID and supporting documents to verify legitimacy.</li>
                <li>Documents submitted are for verification only and will not be publicly posted.</li>
                <li>The system admins reserve the right to review and validate all documents before agents can post/manage listings.</li>
            </ul>

            <h3>7. Intellectual Property Rights</h3>
            <p>All system content—including logos, design, interface, layout, and system features—is the intellectual property of BatEstateExplorer and its developers. Unauthorized copying, reproduction, modification, or distribution is strictly prohibited.</p>

            <h3>8. Limitation of Liability</h3>
            <ul>
                <li>BatEstateExplorer and its developers are not liable for inaccuracies in property listings provided by third parties.</li>
                <li>Not responsible for any loss, damage, or misunderstanding from user interactions or transactions outside the platform.</li>
                <li>Not responsible for technical issues or downtime affecting system accessibility.</li>
            </ul>

            <h3>9. Amendments</h3>
            <p>BatEstateExplorer reserves the right to update or modify these Terms at any time. Any changes will be posted within the system. Continued use implies acceptance of updated Terms.</p>

            <h3>10. Contact Information</h3>
            <p>For questions, feedback, or concerns, contact system administrators via the Help & Support section.</p>

        </div>

        <div style="margin-top: 1rem;">
            <input type="checkbox" id="agreeTermsModal">
            <label for="agreeTermsModal">I agree to the Terms and Conditions</label>
        </div>

        <button type="button" id="proceedBtn" class="submit-btn" disabled style="margin-top: 1rem;">
            Proceed
        </button>
    </div>
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

        const openModalBtn = document.getElementById('openTermsModal');
        const termsModal = document.getElementById('termsModal');
        const closeModal = termsModal.querySelector('.close-modal');
        const agreeCheckbox = document.getElementById('agreeTermsModal');
        const proceedBtn = document.getElementById('proceedBtn');

        let selectedTags = [];

        // ================= SPECIALIZATION TAG LOGIC =================
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

        tagsContainer.addEventListener('click', e => {
            if (e.target.classList.contains('remove-tag')) {
                selectedTags = selectedTags.filter(v => v !== e.target.dataset.value);
                renderTags();
            }
        });

        const selectAllBtn = document.getElementById('selectAllSpecializations');
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', () => {
                const allOptions = Array.from(specializationSelect.options)
                    .map(opt => opt.value)
                    .filter(v => v && v !== '' && v !== 'Others');

                if (selectAllBtn.textContent.trim().toLowerCase() === 'select all') {
                    selectedTags = [...new Set([...selectedTags, ...allOptions])];
                    selectAllBtn.textContent = 'Deselect All';
                } else {
                    selectedTags = [];
                    selectAllBtn.textContent = 'Select All';
                }
                renderTags();
            });
        }

        // ================= DYNAMIC DOCUMENT FIELDS =================
        const docInputs = {
            associate: ['broker_license', 'prc_license', 'resume', 'valid_id'],
            direct: ['valid_id', 'property_location', 'property_image', 'property_document']
        };

        const locations = [
            "Agoncillo","Alitagtag","Balayan","Balete","Batangas City","Bauan","Calaca","Calatagan","Cuenca",
            "Ibaan","Laurel","Lemery","Lian","Lipa City","Lobo","Mabini","Malvar","Mataasnakahoy","Nasugbu",
            "Padre Garcia","Rosario","San Jose","San Juan","San Luis","San Nicolas","San Pascual",
            "Santa Teresita","Santo Tomas","Taal","Talisay","Tanauan City","Taysan","Tingloy","Tuy"
        ];

        function ensureDirectDocFields() {
            const directFields = [
                {
                    id: 'property_location',
                    html: `
                        <label for="property_location"><strong>Property Location *</strong></label>
                        <select id="property_location" name="documents[property_location]" required>
                            <option value="">Select Location</option>
                            ${locations.map(l => `<option value="${l}">${l}</option>`).join('')}
                        </select>
                    `
                },
                {
                    id: 'property_image',
                    html: `
                        <label for="property_image">At least One Property Image *</label>
                        <input type="file" id="property_image" name="documents[property_image]" accept=".jpg,.jpeg,.png" required>
                    `
                },
                {
                    id: 'property_document',
                    html: `
                        <label for="property_document">Property Document *</label>
                        <input type="file" id="property_document" name="documents[property_document]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
                    `
                }
            ];

            directFields.forEach(doc => {
                if (!document.getElementById(doc.id)) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'form-group';
                    wrapper.innerHTML = doc.html;
                    docsSection.appendChild(wrapper);
                }
            });
        }
        ensureDirectDocFields();

        function toggleFields() {
            const type = userType.value;

            docsSection.style.display = type ? 'block' : 'none';
            companyField.style.display = type === 'associate_agent' ? 'block' : 'none';
            companySelect.required = type === 'associate_agent';

            docsSection.querySelectorAll('.form-group').forEach(g => {
                g.style.display = 'none';
                const input = g.querySelector('input, select, textarea');
                if (input) input.removeAttribute('required');
            });

            const currentGroup = type === 'associate_agent' ? docInputs.associate :
                                type === 'direct_agent' ? docInputs.direct : [];
            currentGroup.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.closest('.form-group').style.display = 'block';
                    el.setAttribute('required', 'required');
                }
            });
        }
        toggleFields();
        userType.addEventListener('change', toggleFields);

        // ================= PROFILE PICTURE PREVIEW =================
        profilePreview.addEventListener('click', () => profileInput.click());
        profileInput.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) {
                profilePreview.innerHTML = '<span class="upload-text">Upload Here</span>';
                return;
            }
            const reader = new FileReader();
            reader.onload = event => {
                profilePreview.innerHTML = `<img src="${event.target.result}" alt="Profile Picture">`;
            };
            reader.readAsDataURL(file);
        });

        // ================= NOTIFICATION HELPER =================
        function showAjaxNotification(message, type = 'success') {
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
        }

        // ================= PROFILE PICTURE VALIDATION =================
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
            if (file.size > 2 * 1024 * 1024) { // 2MB
                showAjaxNotification('Profile picture must be smaller than 2 MB.', 'error');
                profileInput.value = '';
                profilePreview.innerHTML = '<span class="upload-text">Upload Here</span>';
                return false;
            }
            return true;
        }

        // ================= FORM VALIDATION =================
        function validateForm() {
            if (!validateProfilePicture()) return false;
            const requiredFields = form.querySelectorAll('[required]');
            for (let field of requiredFields) {
                if (!field.value) {
                    field.focus();
                    showAjaxNotification(`Please fill in ${field.name.replace('_',' ')}`, 'error');
                    return false;
                }
            }
            return true;
        }

        // ================= MODAL FLOW =================
        openModalBtn.addEventListener('click', () => {
            if (!validateForm()) return;
            termsModal.style.display = 'block';
        });
        closeModal.addEventListener('click', () => { termsModal.style.display = 'none'; });
        window.addEventListener('click', e => { if (e.target === termsModal) termsModal.style.display = 'none'; });

        agreeCheckbox.addEventListener('change', () => {
            proceedBtn.disabled = !agreeCheckbox.checked;
        });

        proceedBtn.addEventListener('click', async () => {
            termsModal.style.display = 'none';

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
                    selectedTags.length = 0;
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
    document.addEventListener("DOMContentLoaded", () => {
        const regionSelect = document.getElementById("region");
        const provinceSelect = document.getElementById("province");
        const citySelect = document.getElementById("city");
        const barangaySelect = document.getElementById("barangay");

        // Load regions on page load
        fetchRegions();

        async function fetchRegions() {
            const res = await fetch("../public/api/fetch_address.php?level=region");
            const data = await res.json();
            populateSelect(regionSelect, data, "Select Region");
        }

        regionSelect.addEventListener("change", async () => {
            clearSelects([provinceSelect, citySelect, barangaySelect]);
            if (!regionSelect.value) return;
            const res = await fetch(`../public/api/fetch_address.php?level=province&parent=${regionSelect.value}`);
            const data = await res.json();
            populateSelect(provinceSelect, data, "Select Province");
        });

        provinceSelect.addEventListener("change", async () => {
            clearSelects([citySelect, barangaySelect]);
            if (!provinceSelect.value) return;
            const res = await fetch(`../public/api/fetch_address.php?level=city&parent=${provinceSelect.value}`);
            const data = await res.json();
            populateSelect(citySelect, data, "Select City / Municipality");
        });

        citySelect.addEventListener("change", async () => {
            clearSelects([barangaySelect]);
            if (!citySelect.value) return;
            const res = await fetch(`../public/api/fetch_address.php?level=barangay&parent=${citySelect.value}`);
            const data = await res.json();
            populateSelect(barangaySelect, data, "Select Barangay");
        });

        function populateSelect(select, items, placeholder) {
            select.innerHTML = `<option value="">${placeholder}</option>`;
            items.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.code;
            opt.textContent = item.name;
            select.appendChild(opt);
            });
        }

        function clearSelects(selects) {
            selects.forEach(sel => sel.innerHTML = `<option value="">Select</option>`);
        }
    });
</script>

</body>
</html>
