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
            <label>Profile Picture (Optional)</label>
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

        <div class="form-group">
            <label for="address">Address *</label>
            <textarea id="address" name="address" rows="3"><?= htmlspecialchars($old['address'] ?? $user['address'] ?? '') ?></textarea>
        </div>

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
        <h3>Educational Background</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="education">Education Level *</label>
                <select id="education" name="education">
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
                <label for="school">School/University *</label>
                <input type="text" id="school" name="school"
                    value="<?= htmlspecialchars($old['school'] ?? $user['school'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="course">Course/Major *</label>
                <input type="text" id="course" name="course"
                    value="<?= htmlspecialchars($old['course'] ?? $user['course'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="graduation_year">Graduation Year (Applicable if graduated)</label>
                <input type="number" id="graduation_year" name="graduation_year" min="1950" max="2030"
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

<?php include '../components/notification.php'; ?>

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

        // === SPECIALIZATION TAGS ===
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

        tagsContainer.addEventListener('click', e => {
            if (e.target.classList.contains('remove-tag')) {
                const value = e.target.dataset.value;
                selectedTags = selectedTags.filter(v => v !== value);
                renderTags();
            }
        });

        // === TOGGLE FIELDS BASED ON AGENT TYPE ===
        function toggleFields() {
            const type = userType.value;
            const showDocs = type === 'direct_agent';
            const showCompany = type === 'associate_agent';

            docsSection.style.display = showDocs ? 'block' : 'none';
            companyField.style.display = showCompany ? 'block' : 'none';
            companySelect.required = showCompany;

            // Set required state for file inputs
            docsSection.querySelectorAll('input[type="file"]').forEach(input => {
                input.required = showDocs;
            });
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

        // === AJAX NOTIFICATION HELPER ===
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

        // === AJAX FORM SUBMISSION ===
        form.addEventListener('submit', async e => {
            e.preventDefault();

            const formData = new FormData(form);
            formData.append('ajax', 1); // ensure server detects AJAX

            try {
                const res = await fetch('../public/api/agent_registration_complete.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await res.json();
                window.showAjaxNotification(data.message || 'No message from server.', data.status);

                if (data.status === 'success') {
                    form.reset();
                    selectedTags = [];
                    renderTags();
                    toggleFields();
                    profilePreview.innerHTML = '<span class="upload-text">Upload Here</span>';
                }
            } catch (err) {
                console.error(err);
                window.showAjaxNotification('An error occurred. Please try again.', 'error');
            }
        });
    });
</script>

</body>
</html>
