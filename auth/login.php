<?php
    session_start();
    require_once '../config/database.php';
    require_once __DIR__ . '/../public/app/redirects.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
        $email = sanitize_input($conn, $_POST['email']);
        $password = $_POST['password'] ?? '';
        $isAjax = isset($_POST['ajax']) ? (bool)$_POST['ajax'] : false;

        // Basic validation
        if (empty($email) || empty($password)) {
            $_SESSION['old_email'] = $email;
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Please enter both email and password.'
            ];
            header("Location: login.php");
            exit;
        }

        // Fetch user by email
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            $_SESSION['old_email'] = $email;
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'No account found with that email.'
            ];
            header("Location: login.php");
            exit;
        }

        // Verify password
        if (!verify_password($password, $user['password_hash'])) {
            $_SESSION['old_email'] = $email;
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Incorrect password. Please try again.'
            ];
            header("Location: login.php");
            exit;
        }

        if ((int)$user['is_blocked'] === 1) {
            // Determine if this is an agent or a regular user
            $reportQuery = null;

            if ($user['user_type'] === 'agent') {
                $reportQuery = "
                    SELECT reason, other_reason, duration 
                    FROM agent_reports 
                    WHERE agent_id = ? 
                    AND status = 'blocked'
                    ORDER BY blockage_date DESC 
                    LIMIT 1
                ";
            } else { // normal user
                $reportQuery = "
                    SELECT reason, other_reason, duration 
                    FROM user_reports 
                    WHERE reported_user_id = ? 
                    AND status = 'blocked'
                    ORDER BY blockage_date DESC 
                    LIMIT 1
                ";
            }

            $blockQuery = mysqli_prepare($conn, $reportQuery);
            mysqli_stmt_bind_param($blockQuery, "i", $user['id']);
            mysqli_stmt_execute($blockQuery);
            $blockResult = mysqli_stmt_get_result($blockQuery);
            $blockInfo = mysqli_fetch_assoc($blockResult);

            $reasonText = $blockInfo['reason'] === 'other'
                ? $blockInfo['other_reason']
                : ucfirst($blockInfo['reason'] ?? 'Violation');

            $duration = $blockInfo['duration'] ?? '7 days';
            $reason   = $reasonText ?: 'Violation of platform policies';

            // Redirect user to login page with block notice
            header("Location: login.php?blocked=1&reason=" . urlencode($reason) . "&duration=" . urlencode($duration));
            exit;
        }

        // Check account status
        if ($user['status'] !== 'active') {
            $_SESSION['old_email'] = $email;
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

        redirect_by_user_type($user['user_type']); // redirects and exits
        exit;
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

<?php include __DIR__ . "/../components/notification.php"; ?>

<script>
    /* ==============================================
    1️⃣ Run Block Check Immediately on Page Load
    ============================================== */
    (function triggerBlockCheckFirst() {
        // Use absolute-safe path — adjust if needed
        fetch('../public/api/block_check.php')
            .then(res => res.json())
            .then(data => {
                console.log('[Block Check Executed FIRST]', data.message, data.unblocked_agents?.length || 0, 'agents updated');
            })
            .catch(err => console.error('Block check failed before page load:', err));
    })();

    /* ==============================================
    2️⃣ Continue normal page logic after block check
    ============================================== */
    document.addEventListener('DOMContentLoaded', () => {

        // ================================
        // Toggle Password Visibility
        // ================================
        const togglePasswordText = document.querySelector('#togglePasswordText');
        const password = document.querySelector('#password');

        if (togglePasswordText && password) {
            togglePasswordText.addEventListener('click', () => {
                if (password.type === 'password') {
                    password.type = 'text';
                    togglePasswordText.textContent = 'Hide';
                } else {
                    password.type = 'password';
                    togglePasswordText.textContent = 'Show';
                }
            });
        }

        // ================================
        // Show Blocked Account Modal
        // ================================
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('blocked') === '1') {
            const reason = urlParams.get('reason') || 'Violation of platform policies';
            const duration = (urlParams.get('duration') || '7 days').toLowerCase();

            const isPermanent = ['lifetime', 'permanent', 'permanently', 'forever'].includes(duration);
            const modal = document.createElement('div');
            modal.className = 'blocked-modal';

            const iconColor = isPermanent ? '#d93025' : '#e6b800';
            const iconBg = isPermanent ? '#ffe6e6' : '#fff8dc';
            const message = isPermanent
                ? 'Your account has been permanently banned by the administrator.'
                : 'Your account has been temporarily disabled by the administrator.';

            const title = isPermanent ? 'Account Banned' : 'Account Blocked';

            modal.innerHTML = `
                <div class="blocked-overlay"></div>
                <div class="blocked-content animate-in">
                    <div class="blocked-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="72" height="72" fill="none" stroke="${iconColor}" stroke-width="3">
                            <path d="M32 6 L2 58 H62 Z" fill="${iconBg}" stroke="${iconColor}"/>
                            <line x1="32" y1="22" x2="32" y2="38" stroke="${iconColor}" stroke-width="5" stroke-linecap="round"/>
                            <circle cx="32" cy="48" r="3" fill="${iconColor}"/>
                        </svg>
                    </div>
                    <h2>${title}</h2>
                    <p class="blocked-subtext">${message}</p>
                    <div class="blocked-details">
                        <p><strong>Violation:</strong> ${reason}</p>
                        ${!isPermanent ? `<p><strong>Until:</strong> ${duration}</p>` : ''}
                    </div>
                    <button id="closeBlockedModal" class="blocked-btn">Okay</button>
                </div>
            `;
            document.body.appendChild(modal);

            document.querySelector('#closeBlockedModal').addEventListener('click', () => {
                modal.remove();
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    });
</script>

</body>
</html>
