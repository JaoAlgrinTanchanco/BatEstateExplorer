<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../public/app/redirects.php';

// Handle AJAX and regular login requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['ajax'])) {
    $email = sanitize_input($conn, $_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Please enter both email and password.'
        ];
        header("Location: login.php"); // <-- redirect clears POST
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if (verify_password($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $_SESSION['notification'] = [
                    'type' => 'error',
                    'message' => 'Your account is pending approval. Please wait for admin review.'
                ];
                header("Location: login.php");
                exit;
            }

            // Successful login
            $_SESSION['user_token'] = generate_token($user['id'], $user['email'], $user['user_type']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];

            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Logged in successfully!'
            ];

            redirect_by_user_type($user['user_type']); // already redirects
            exit;

        } else {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Incorrect password. Please try again.'
            ];
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'No account found with that email.'
        ];
        header("Location: login.php");
        exit;
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

        <form method="POST" action="">
            <input type="hidden" name="ajax" value="0">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" autocomplete="username" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group password-wrapper">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <span id="togglePasswordText" class="toggle-password-text">Show</span>
            </div>

            <button type="submit" class="submit-btn">Login</button>
        </form>

        <div class="links">
            <a href="../index.php">Back to Home</a>
            <span class="divider">|</span>
            <a href="change_pass.php">Forgot Password?</a>
            <span class="divider">|</span>
            <a href="signup.php">Create Account</a>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../components/notification.php"; ?>

<script>
const togglePasswordText = document.querySelector('#togglePasswordText');
const password = document.querySelector('#password');

togglePasswordText.addEventListener('click', () => {
    if (password.type === 'password') {
        password.type = 'text';
        togglePasswordText.textContent = 'Hide';
    } else {
        password.type = 'password';
        togglePasswordText.textContent = 'Show';
    }
});
</script>
</body>
</html>
