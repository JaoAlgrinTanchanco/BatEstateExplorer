<?php
session_start();
require_once '../config/pdo_database.php';

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

// Get notification message
$notification = $_SESSION['notification'] ?? null;
unset($_SESSION['notification']);

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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
.registration-container {
    max-width: 850px;
    margin: 40px auto;
    padding: 25px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.12);
}
.form-group { margin-bottom: 20px; }
.form-group label { font-weight: 600; margin-bottom: 6px; display: block; color: #333; }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 12px; font-size: 15px; border: 2px solid #e1e5e9; border-radius: 6px; transition: 0.3s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #007bff; outline: none; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.submit-btn { background: #007bff; color: #fff; padding: 15px 30px; font-size: 18px; border: none; border-radius: 6px; cursor: pointer; transition: 0.3s; }
.submit-btn:hover { background: #0056b3; }
.back-link { display: inline-block; margin-bottom: 20px; color: #007bff; text-decoration: none; }
.back-link:hover { text-decoration: underline; }
.notification { padding: 12px 20px; margin-bottom: 20px; border-radius: 6px; }
.notification.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.notification.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>
</head>
<body>
<div class="registration-container">

    <a href="javascript:void(0)" class="back-link" id="back-link">
        <i class="fas fa-arrow-left"></i> Back
    </a>

    <h1><i class="fas fa-user-tie"></i> Agent Registration</h1>
    <p>Join our network of professional real estate agents and start your journey with BatEstate Explorer.</p>

    <?php if($notification): ?>
        <div class="notification <?= $notification['type'] === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($notification['message']) ?>
        </div>
    <?php endif; ?>

    <form action="../public/api/agent_registration_complete.php" method="POST" enctype="multipart/form-data">
        <!-- Personal Info -->
        <h3>Personal Information</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required
                    value="<?= htmlspecialchars($old['first_name'] ?? $user['first_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required
                    value="<?= htmlspecialchars($old['last_name'] ?? $user['last_name'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required
                    value="<?= htmlspecialchars($old['email'] ?? $user['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" required
                    value="<?= htmlspecialchars($old['phone'] ?? $user['phone'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="address">Address *</label>
            <textarea id="address" name="address" rows="3" required><?= htmlspecialchars($old['address'] ?? $user['address'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
        </div>

        <div class="form-group">
            <label for="user_type">Agent Type *</label>
            <select id="user_type" name="user_type" required>
                <option value="">Select Agent Type</option>
                <option value="direct_agent" <?= ($old['user_type'] ?? $agent_type_param) === 'direct_agent' ? 'selected' : '' ?>>Direct Agent</option>
                <option value="associate_agent" <?= ($old['user_type'] ?? $agent_type_param) === 'associate_agent' ? 'selected' : '' ?>>Associate Agent</option>
            </select>
        </div>

        <!-- Professional Info -->
        <h3>Professional Information</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="broker_id">Broker ID</label>
                <input type="text" id="broker_id" name="broker_id"
                    value="<?= htmlspecialchars($old['broker_id'] ?? $user['broker_id'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="prc_number">PRC Number</label>
                <input type="text" id="prc_number" name="prc_number"
                    value="<?= htmlspecialchars($old['prc_number'] ?? $user['prc_number'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="experience_years">Years of Experience</label>
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
            <div class="form-group">
                <label for="specializations">Specializations</label>
                <input type="text" id="specializations" name="specializations"
                    value="<?= htmlspecialchars($old['specializations'] ?? $user['specializations'] ?? '') ?>" placeholder="e.g., Residential, Commercial, Luxury">
            </div>
        </div>

        <div class="form-group">
            <label for="experience_details">Experience Details</label>
            <textarea id="experience_details" name="experience_details" rows="4"
                placeholder="Describe your real estate experience and achievements"><?= htmlspecialchars($old['experience_details'] ?? $user['experience_details'] ?? '') ?></textarea>
        </div>

        <!-- Education -->
        <h3>Educational Background</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="education">Education Level</label>
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
                <label for="school">School/University</label>
                <input type="text" id="school" name="school"
                    value="<?= htmlspecialchars($old['school'] ?? $user['school'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="course">Course/Major</label>
                <input type="text" id="course" name="course"
                    value="<?= htmlspecialchars($old['course'] ?? $user['course'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="graduation_year">Graduation Year</label>
                <input type="number" id="graduation_year" name="graduation_year" min="1950" max="2030"
                    value="<?= htmlspecialchars($old['graduation_year'] ?? $user['graduation_year'] ?? '') ?>">
            </div>
        </div>

        <!-- Certifications & Training -->
        <h3>Certifications & Training</h3>
        <div class="form-group">
            <label for="certifications">Professional Certifications</label>
            <textarea id="certifications" name="certifications" rows="3"><?= htmlspecialchars($old['certifications'] ?? $user['certifications'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label for="training">Additional Training</label>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const userType = document.getElementById('user_type');
    const docsSection = document.getElementById('required-documents');
    const companyField = document.getElementById('company-field');
    const companySelect = document.getElementById('company_id');

    function toggleFields() {
        const type = userType.value;
        if(type === 'direct_agent') {
            docsSection.style.display = 'block';
            companyField.style.display = 'none';
            companySelect.required = false;
            docsSection.querySelectorAll('input[type="file"]').forEach(el => el.required = true);
        } else if(type === 'associate_agent') {
            docsSection.style.display = 'none';
            companyField.style.display = 'block';
            companySelect.required = true;
            docsSection.querySelectorAll('input[type="file"]').forEach(el => el.required = false);
        } else {
            docsSection.style.display = 'none';
            companyField.style.display = 'none';
            companySelect.required = false;
            docsSection.querySelectorAll('input[type="file"]').forEach(el => el.required = false);
        }
    }

    toggleFields();
    userType.addEventListener('change', toggleFields);

    // Back link
    const backLink = document.getElementById('back-link');
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('from') === 'profile'){
        backLink.innerHTML = '<i class="fas fa-arrow-left"></i> Back to Profile';
    }
    backLink.addEventListener('click', ()=> window.history.back());
});
</script>
</body>
</html>
