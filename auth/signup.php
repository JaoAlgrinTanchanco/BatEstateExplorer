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

            <!-- Only errors will show here -->
            <div id="responseMessage" style="margin-top: 1rem; font-weight: bold;"></div>
            
            <div class="login-link stagger-item">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </div>
    </div>

    <!-- AJAX Script -->
    <script>
        document.getElementById("signupForm").addEventListener("submit", async function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            // Password match validation
            if (formData.get("password") !== formData.get("confirm_password")) {
                document.getElementById("responseMessage").textContent = "Passwords do not match.";
                document.getElementById("responseMessage").style.color = "red";
                return;
            }

            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();
                const messageBox = document.getElementById("responseMessage");

                if (result.success) {
                    // ✅ Success: just reset form silently
                    messageBox.textContent = "";
                    form.reset();
                } else {
                    // ❌ Show error message
                    messageBox.textContent = result.message;
                    messageBox.style.color = "red";
                }
            } catch (error) {
                console.error("Error:", error);
                document.getElementById("responseMessage").textContent = "Something went wrong.";
                document.getElementById("responseMessage").style.color = "red";
            }
        });
    </script>
</body>
</html>
