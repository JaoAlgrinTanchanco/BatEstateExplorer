<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../public/app/redirects.php';

$error = '';

// Handle AJAX login requests (for agent login from index.php and Auth Modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $email = sanitize_input($conn, $_POST['email']);
        $password = $_POST['password'];
        $user_type = isset($_POST['user_type']) ? sanitize_input($conn, $_POST['user_type']) : null;
        
        if (empty($email) || empty($password)) {
            throw new Exception('Please enter both email and password.');
        }
        
        // Find user by email
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            if (verify_password($password, $user['password_hash'])) {
                if ($user_type && $user['user_type'] !== $user_type) {
                    throw new Exception('Access denied. ' . ucfirst(str_replace('_', ' ', $user_type)) . ' privileges required.');
                }
                if ($user['status'] !== 'active') {
                    throw new Exception('Your account is pending approval. Please wait for admin review.');
                }

                $token = generate_token($user['id'], $user['email'], $user['user_type']);
                $_SESSION['user_token'] = $token;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                
                $response['success'] = true;
                $response['message'] = 'Login successful!';
                $response['user_type'] = $user['user_type'];
            } else {
                throw new Exception('Incorrect password. Please try again.');
            }
        } else {
            throw new Exception('No account found with that email.');
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle regular form login (non-AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['ajax']) && $_POST['ajax'] === '0') {
    $email = sanitize_input($conn, $_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (verify_password($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    $error = "Your account is pending approval. Please wait for admin review.";
                } else {
                    $token = generate_token($user['id'], $user['email'], $user['user_type']);
                    $_SESSION['user_token'] = $token;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    redirect_by_user_type($user['user_type']);
                }
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BatEstate</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="header">
                <img src="../assets/images/Vector 1.png" alt="BatEstate Logo" class="logo">
                <h1>BatEstate Explorer</h1>
                <p>Welcome Back</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="ajax" value="0">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" autocomplete="username" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                
                <button type="submit" class="submit-btn">Login</button>
            </form>
            
            <div class="links">
                <a href="../index.php">Back to Home</a>
                <span class="divider">|</span>
                <a href="signup.php">Create Account</a>
            </div>
        </div>
    </div>
</body>

</html> 