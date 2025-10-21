<?php
    session_start();
    require_once '../config/database.php';
    require_once __DIR__ . '/../public/app/redirects.php';
    include __DIR__ . "/../components/notification.php";

    // Only handle POST requests with an email field
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email'])) {
        header("Location: login.php");
        exit;
    }

    $email = sanitize_input($conn, $_POST['email']);
    $password = $_POST['password'] ?? '';
    $isAjax = !empty($_POST['ajax']); // will use later if needed for async requests

    // ==============================
    // 1. Basic Validation
    // ==============================
    if (empty($email) || empty($password)) {
        $_SESSION['old_email'] = $email;
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Please enter both email and password.'
        ];
        header("Location: login.php");
        exit;
    }

    // ==============================
    // 2. Fetch user by email
    // ==============================
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $_SESSION['old_email'] = $email;
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'No account found with that email.'
        ];
        header("Location: login.php");
        exit;
    }

    // ==============================
    // 3. Verify password
    // ==============================
    if (!verify_password($password, $user['password_hash'])) {
        $_SESSION['old_email'] = $email;
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Incorrect password. Please try again.'
        ];
        header("Location: login.php");
        exit;
    }

    // ==============================
    // 4. Check account status
    // ==============================
    if ($user['status'] !== 'active') {
        $_SESSION['old_email'] = $email;
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Your account is pending approval. Please wait for admin review.'
        ];
        header("Location: login.php");
        exit;
    }

    // ==============================
    // 5. Successful login
    // ==============================
    $_SESSION['user_token'] = generate_token($user['id'], $user['email'], $user['user_type']);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];

    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Logged in successfully!'
    ];

    // Redirect user based on type (agent, admin, etc.)
    redirect_by_user_type($user['user_type']);
    exit;
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
                    value="<?php 
                        echo isset($_SESSION['old_email']) ? htmlspecialchars($_SESSION['old_email']) : ''; 
                        unset($_SESSION['old_email']); // clear after displaying
                    ?>">
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

<!-- =========================
     Blocked Account Modal
========================= -->
<div id="blockedModal" class="modal hidden">
  <div class="modal-content">
    <h2>Your account has been banned</h2>
    <p id="blockReason">Reason: <span></span></p>
    <p id="blockDuration">Duration Remaining: <span></span></p>
    <button id="closeModalBtn">Close</button>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const password = document.querySelector('#password');
    const togglePasswordText = document.querySelector('#togglePasswordText');
    const modal = document.querySelector('#blockedModal');
    const closeModalBtn = document.querySelector('#closeModalBtn');

    // ================================
    // 1. Toggle Password Visibility
    // ================================
    togglePasswordText.addEventListener('click', () => {
        const isHidden = password.type === 'password';
        password.type = isHidden ? 'text' : 'password';
        togglePasswordText.textContent = isHidden ? 'Hide' : 'Show';
    });

    // ================================
    // 2. Show Blocked Modal (for later use)
    // ================================
    function showBlockedModal(reason, remainingTime) {
        document.querySelector('#blockReason span').textContent = reason;
        document.querySelector('#blockDuration span').textContent = remainingTime;
        modal.classList.remove('hidden');
    }

    closeModalBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    // ================================
    // 3. (Later) Triggered when login detects blocked account
    // ================================
    // Example:
    // showBlockedModal("Inappropriate behavior", "2 days 5 hours remaining");
    });
</script>

</body>
</html>
