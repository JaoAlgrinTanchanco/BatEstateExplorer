<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// --- PDO Connection ---
$host = 'localhost';
$dbname = 'batestate';
$username = 'root';
$password = '';
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => "Database connection failed: " . $e->getMessage()
    ];
    die(header("Location: change_pass.php"));
}

// PHPMailer
require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$step = $_SESSION['step'] ?? 1;

// Reset step if GET reset
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reset'])) {
    $step = 1;
    unset($_SESSION['step'], $_SESSION['reset_email'], $_SESSION['reset_user_id'], $_SESSION['reset_otp'], $_SESSION['otp_expires']);
}

// Back button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['back'])) {
    if ($step > 1) {
        $_SESSION['step'] = --$step;
    }
}

// Function to send OTP
function send_otp($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'johnanseldoton@gmail.com';
        $mail->Password   = 'smcnahgndykgieab'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('johnanseldoton@gmail.com', 'BatEstate');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8'; // for emoji & special chars
        $mail->Encoding = 'base64';
        $mail->Subject = 'Password Reset OTP - BatEstate';

        // ✅ Embed your local logo
        $mail->addEmbeddedImage(
            'C:/xampp/htdocs/BatEstateExplorer/assets/images/Vector 1.png', // local path
            'batestate_logo', // CID
            'logo.png' // name
        );

        // Styled HTML body with logo on top
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; background:#f9fafc; padding:30px;">
            <table style="max-width:600px; margin:auto; background:#ffffff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:20px;">
                <tr>
                    <td style="text-align:center; padding-bottom:20px;">
                        <img src="cid:batestate_logo" alt="BatEstate Logo" style="max-width:120px; margin-bottom:15px;">
                        <h2 style="color:#111; margin:0;">BatEstate Security</h2>
                        <p style="color:#555; font-size:14px; margin-top:5px;">Password Reset Verification</p>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:15px; color:#333; line-height:1.6;">
                        <p>Hello,</p>
                        <p>We received a request to reset your BatEstate account password. Please use the OTP below to proceed:</p>
                        <div style="text-align:center; margin:25px 0;">
                            <span style="display:inline-block; font-size:24px; font-weight:bold; letter-spacing:5px; color:#111; background:#f1f1f1; padding:15px 25px; border-radius:8px;">' . $otp . '</span>
                        </div>
                        <p>This OTP will expire in <b>5 minutes</b>. If you did not request this, you can safely ignore this email.</p>
                        <p style="margin-top:25px;">Thank you,<br><b>The BatEstate Team</b></p>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:center; font-size:12px; color:#999; padding-top:20px; border-top:1px solid #eee;">
                        © ' . date("Y") . ' BatEstate. All rights reserved.
                    </td>
                </tr>
            </table>
        </div>';

        $mail->AltBody = "Your OTP is: $otp (valid for 5 minutes). If you did not request this, please ignore.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

// Step 1: Send OTP
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user) {
        $otp = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_user_id'] = $user['id'];
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_expires'] = time() + 300; // 5 minutes
        $_SESSION['step'] = 2;
        $step = 2;

        $result = send_otp($email, $otp);
        $_SESSION['notification'] = [
            'type' => $result === true ? 'success' : 'error',
            'message' => $result === true ? "OTP sent to your email." : "Mailer Error: $result"
        ];
    } else {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => "No account found with this email."
        ];
    }
}

// Step 2: Verify OTP / Resend
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['otp'])) {
        $input_otp = trim($_POST['otp']);
        if ($input_otp == $_SESSION['reset_otp'] && time() <= $_SESSION['otp_expires']) {
            $_SESSION['step'] = 3;
            $step = 3;
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => "OTP verified. You can now reset your password."
            ];
        } else {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => "Invalid or expired OTP."
            ];
        }
    } elseif (!empty($_POST['resend'])) {
        $otp = rand(100000, 999999);
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_expires'] = time() + 300;
        $result = send_otp($_SESSION['reset_email'], $otp);
        $_SESSION['notification'] = [
            'type' => $result === true ? 'success' : 'error',
            'message' => $result === true ? "OTP resent to your email." : "Mailer Error: $result"
        ];
    }
}

// Step 3: Reset Password
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['new_password'])) {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$new_password, $_SESSION['reset_user_id']]);

    unset($_SESSION['reset_email'], $_SESSION['reset_user_id'], $_SESSION['reset_otp'], $_SESSION['otp_expires'], $_SESSION['step']);
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => "Password reset successfully!"
    ];
    $step = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password</title>
<link rel="stylesheet" href="../assets/css/change_pass.css">
<script>
function startTimer(duration, display, resendBtn) {
    let timer = duration;
    let countdown = setInterval(function() {
        let minutes = Math.floor(timer / 60);
        let seconds = timer % 60;
        display.textContent = (minutes < 10 ? "0" : "") + minutes + ":" +
                              (seconds < 10 ? "0" : "") + seconds;
        resendBtn.disabled = true;
        if (--timer < 0) {
            clearInterval(countdown);
            display.textContent = "OTP expired!";
            resendBtn.disabled = false;
        }
    }, 1000);
}

window.onload = function () {
<?php if($step===2 && isset($_SESSION['otp_expires'])):
    $remaining = $_SESSION['otp_expires'] - time();
    if($remaining < 0) $remaining = 0;
?>
    startTimer(<?= $remaining ?>, document.querySelector('#timer'), document.querySelector('#resendBtn'));
<?php endif; ?>
};
</script>
</head>
<body>
<div class="change-wrapper">
<h2>Change Password</h2>
<!-- Back to Sign Up link -->
<p style="text-align: center; margin-bottom: 20px;">
    <a href="login.php" style="text-decoration: none; color: #111; font-weight: bold;">
        &larr; Back to Sign In
    </a>
</p>
<!-- Centralized Notification -->
<?php include __DIR__ . '/../components/notification.php'; ?>

<?php if($step === 1): ?>
<form method="POST">
    <label>Email Address</label>
    <input type="email" name="email" placeholder="Enter your email" required>
    <button type="submit">Send OTP</button>
</form>

<?php elseif($step === 2): ?>
<p>OTP sent to your email. Expires in <span id="timer"></span></p>
<form method="POST">
    <label>Enter OTP</label>
    <input type="text" name="otp" placeholder="6-digit OTP" required>
    <button type="submit">Verify OTP</button>
</form>
<form method="POST">
    <button type="submit" name="resend" value="1" id="resendBtn">Resend OTP</button>
</form>
<form method="POST" style="margin-top:10px;">
    <button type="submit" name="back" value="1">Back</button>
</form>

<?php elseif($step === 3): ?>
<form method="POST">
    <label>New Password</label>
    <input type="password" name="new_password" placeholder="Enter new password" required>
    <button type="submit">Reset Password</button>
</form>
<form method="POST" style="margin-top:10px;">
    <button type="submit" name="back" value="1">Back</button>
</form>
<?php endif; ?>
</div>
</body>
</html>
