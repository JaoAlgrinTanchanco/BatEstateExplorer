<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - BatEstate Explorer</title>
    <link rel="stylesheet" href="../assets/css/signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            
            <!-- Form connected to signup_user.php -->
            <form id="signupForm" action="../auth/signup_user.php" method="POST">
                <div class="form-row stagger-item">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="form-group stagger-item">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                
                <div class="form-group stagger-item">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-row stagger-item">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                
                <div class="form-group stagger-item">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </div>
            </form>

            <div class="login-link stagger-item">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </div>
    </div>

    <!-- ✅ Centralized Notification -->
    <?php include __DIR__ . "/../components/notification.php"; ?>

    <!-- Optional: client-side password match check -->
    <script>
        document.getElementById("signupForm").addEventListener("submit", function(e) {
            const form = e.target;
            const password = form.password.value;
            const confirm = form.confirm_password.value;

            if (password !== confirm) {
                e.preventDefault();
                alert("Passwords do not match."); // optional quick feedback
            }
        });
    </script>
</body>
</html>
