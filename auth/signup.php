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
    <div class="signup-container">
        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        
        <h1><i class="fas fa-user-plus"></i> Create Your Account</h1>
        <p>Join thousands of users who found their dream properties with BatEstate Explorer.</p>
        
        <form id="signupForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
            </div>
            
            <div class="form-group">
                <label for="user_type">Account Type *</label>
                <select id="user_type" name="user_type" required>
                    <option value="">Select Account Type</option>
                    <option value="buyer">Property Buyer</option>
                    <option value="seller">Property Seller</option>
                    <option value="investor">Real Estate Investor</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone">
            </div>
            
            <div class="form-group">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </div>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
    </div>

    <script>
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            const formData = new FormData(this);
            const name = formData.get('first_name') + ' ' + formData.get('last_name');
            formData.set('name', name);
            
            fetch('signup_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Account created successfully! You can now sign in.');
                    window.location.href = 'login.php';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    </script>
</body>
</html>
