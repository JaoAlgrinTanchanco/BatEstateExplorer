<?php
    session_start();
    include __DIR__ . "/../components/notification.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - BatEstate Explorer</title>
    <link rel="stylesheet" href="../assets/css/signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Show/Hide toggle */
        .toggle-password {
            display: flex;
            justify-content: flex-end;
            width: 100%;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #111;
            cursor: pointer;
            user-select: none;
        }
        .toggle-password:hover {
            color: #000;
        }
    </style>
</head>
<body>
<div class="signup-wrapper">
    <!-- Left image card -->
    <div class="signup-image-container">
        <div class="signup-image"></div>
    </div>

    <!-- Right signup form -->
    <div class="signup-container">
        <a href="../index.php" class="back-link stagger-item">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        
        <h1 class="stagger-item"><i class="fas fa-user-plus"></i> Create Your Account</h1>
        <p class="stagger-item">Join thousands of users who found their dream properties with BatEstate Explorer.</p>
        
        <form id="signupForm" action="../auth/signup_user.php" method="POST" enctype="multipart/form-data" autocomplete="on">
            <!-- Profile Picture Upload -->
            <div class="form-group profile-pic-group stagger-item">
                <label>Profile Picture *</label>
                <div class="profile-pic-wrapper" style="position: relative;">
                    <!-- Keep input visible but 100% transparent and full size to be focusable -->
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/png, image/jpeg, image/jpg, image/gif" 
                        style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                    <div class="profile-pic-preview" id="profilePicPreview">
                        <span class="upload-text">Upload Here</span>
                    </div>
                </div>
            </div>

            <div class="form-row stagger-item">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required autocomplete="given-name">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required autocomplete="family-name">
                </div>
            </div>

            <div class="form-row stagger-item">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone">
                </div>
            </div>
            
            <div class="form-row stagger-item">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password">
                </div>
            </div>

            <div class="stagger-item">
                <div class="toggle-password" id="togglePassword">Show password</div>
            </div>

            <div class="form-group stagger-item">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </div>
        </form>

        <div class="google-login stagger-item">
            <a href="/BatEstateExplorer/auth/google_login.php" class="google-btn">
                <img src="/BatEstateExplorer/assets/images/google-color-icon.svg" alt="Google"> Continue with Google
            </a>
        </div>

        <div class="links stagger-item">
            <a href="login.php">Sign in here</a>
            <span class="divider">|</span>
            <a href="agent_registration.php">Become an Agent</a>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div id="emailVerifyModal" class="modal">
  <div class="modal-content">
    <p>We will send an email to verify your email address. Are you sure you want to proceed with this email?</p>
    <div id="modalEmailContainer" class="email-container"></div>
    <div class="modal-buttons">
      <button id="modalYes">Yes</button>
      <button id="modalNo">No</button>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const signupForm = document.getElementById('signupForm');
    const modal = document.getElementById('emailVerifyModal');
    const modalEmail = document.getElementById('modalEmailContainer');
    const modalYes = document.getElementById('modalYes');
    const modalNo = document.getElementById('modalNo');
    const toggle = document.getElementById("togglePassword");
    const passwordField = document.getElementById("password");
    const confirmField = document.getElementById("confirm_password");
    const profileInput = document.getElementById("profile_picture");
    const profilePreview = document.getElementById("profilePicPreview");

    const firstNameField = document.getElementById('first_name');
    const lastNameField = document.getElementById('last_name');
    const emailField = document.getElementById('email');
    const phoneField = document.getElementById('phone');

    // Your notification helper
    function notify(type, message) {
        const container = document.querySelector('.notification-container')
            || (() => {
                const wrap = document.createElement('div');
                wrap.className = 'notification-container';
                document.body.appendChild(wrap);
                return wrap;
            })();

        const notif = document.createElement('div');
        notif.className = `notification ${type}`;
        const icon = type === 'success'
            ? '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="#fff" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="#fff" d="m13 13h-2v-6h2zm0 4h-2v-2h2zm-1-15c-1.3132 0-2.61358.25866-3.82683.7612-1.21326.50255-2.31565 1.23915-3.24424 2.16773-1.87536 1.87537-2.92893 4.41891-2.92893 7.07107 0 2.6522 1.05357 5.1957 2.92893 7.0711.92859.9286 2.03098 1.6651 3.24424 2.1677 1.21325.5025 2.51363.7612 3.82683.7612 2.6522 0 5.1957-1.0536 7.0711-2.9289 1.8753-1.8754 2.9289-4.4189 2.9289-7.0711 0-1.3132-.2587-2.61358-.7612-3.82683-.5026-1.21326-1.2391-2.31565-2.1677-3.24424-.9286-.92858-2.031-1.66518-3.2443-2.16773-1.2132-.50254-2.5136-.7612-3.8268-.7612z"/></svg>';
        notif.innerHTML = `
            <div class="notification__icon">${icon}</div>
            <div class="notification__title">${message}</div>
            <div class="notification__close">&times;</div>
        `;
        container.appendChild(notif);

        notif.querySelector('.notification__close').addEventListener('click', () => notif.remove());
        setTimeout(() => notif.remove(), 5000);
    }

    // Toggle password show/hide
    toggle.addEventListener("click", () => {
        const isPassword = passwordField.type === "password";
        passwordField.type = isPassword ? "text" : "password";
        confirmField.type = isPassword ? "text" : "password";
        toggle.textContent = isPassword ? "Hide password" : "Show password";
    });

    // Profile picture preview
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

    // Email & phone validation
    const isValidEmail = email => /^[^\s@]+@gmail\.com$/i.test(email);
    const isValidPhone = phone => /^\+?[0-9]{7,15}$/.test(phone);

    // Form submit
    signupForm.addEventListener('submit', e => {
        e.preventDefault();

        const firstName = firstNameField.value.trim();
        const lastName = lastNameField.value.trim();
        const email = emailField.value.trim();
        const profile = profileInput.files[0];
        const password = passwordField.value;
        const confirm = confirmField.value;
        const phone = phoneField.value.trim();

        // ===== Validations =====
        if (!firstName) { notify('error', "Please enter your first name."); return; }
        if (!lastName) { notify('error', "Please enter your last name."); return; }
        if (!email) { notify('error', "Please enter your email."); return; }
        if (!isValidEmail(email)) { notify('error', "Please use a valid Gmail address (e.g., example@gmail.com)."); return; }
        if (!profile) { notify('error', "Please upload a profile picture."); return; }
        if (!password || !confirm) { notify('error', "Please enter your password and confirm it."); return; }
        if (password !== confirm) { notify('error', "Passwords do not match."); return; }
        if (phone && !isValidPhone(phone)) { notify('error', "Please enter a valid phone number (7-15 digits, optional +)."); return; }

        // ===== All validations passed → show modal =====
        modalEmail.textContent = email;
        modal.style.display = 'flex';
    });

    // Modal Yes: send OTP
    modalYes.addEventListener('click', () => {
        modal.style.display = 'none';
        const formData = new FormData(signupForm);

        fetch('../auth/verify_email.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                notify('success', 'OTP sent! Please check your email to complete registration.');
                window.location.href = 'signup.php';
            } else {
                notify('error', data.message || 'Failed to send OTP.');
            }
        })
        .catch(() => notify('error', 'An error occurred while sending OTP.'));
    });

    // Modal No: hide modal
    modalNo.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });
});
</script>

</body>
</html>
