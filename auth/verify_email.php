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
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// PHPMailer
require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Email is already registered.']);
        exit;
    }

    // Generate a unique verification token
    $token = bin2hex(random_bytes(32));
    $_SESSION['signup_email'] = $email;
    $_SESSION['signup_first_name'] = $firstName;
    $_SESSION['signup_last_name'] = $lastName;
    $_SESSION['signup_token'] = $token;

    // Save token in a verification table
    $stmt = $pdo->prepare("INSERT INTO email_verifications (email, first_name, last_name, token, created_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE token=?, created_at=NOW()");
    $stmt->execute([$email, $firstName, $lastName, $token, $token]);

    // Verification link
    $verifyUrl = "http://localhost/BatEstateExplorer/auth/verify_email_button.php?token=$token";

    // Send verification email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'batestate07@gmail.com';
        $mail->Password   = 'jgsiczkzxyvuxvgb'; // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('batestate07@gmail.com', 'BatEstate Explorer');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Email Verification - BatEstate Explorer';

        $mail->addEmbeddedImage(
            'C:/xampp/htdocs/BatEstateExplorer/assets/images/Vector 1.png',
            'batestate_logo',
            'logo.png'
        );

        // Styled HTML body with logo on top
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; background:#f9fafc; padding:30px;">
        <table style="max-width:600px; margin:auto; background:#ffffff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:20px;">
            <tr>
            <td style="text-align:center; padding-bottom:20px;">
                <img src="cid:batestate_logo" alt="BatEstate Logo" style="max-width:120px; margin-bottom:15px;">
                <h2 style="color:#111; margin:0;">BatEstate Explorer</h2>
                <p style="color:#555; font-size:14px; margin-top:5px;">Email Verification</p>
            </td>
            </tr>
            <tr>
            <td style="font-size:15px; color:#333; line-height:1.6;">
                <p>Hello <b>' . htmlspecialchars($firstName) . '</b>,</p>
                <p>Welcome to <b>BatEstate Explorer</b>! Please verify your email address to complete your registration and activate your account.</p>
                <div style="text-align:center; margin:30px 0;">
                <a href="' . $verifyUrl . '" 
                    style="display:inline-block; font-size:16px; font-weight:600; color:#fff; background:#007bff; padding:14px 28px; border-radius:8px; text-decoration:none;">
                    Verify Email
                </a>
                </div>
                <p>If you did not request this, please ignore this email. This link will expire in <b>10 minutes</b>.</p>
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

        $mail->AltBody = "Hello $firstName,\n\nPlease verify your email by clicking this link:\n$verifyUrl\n\nIf you didn’t request this, ignore this email.\n\n— The BatEstate Team";

        $mail->send();

        echo json_encode(['status' => 'success', 'message' => 'Verification email sent.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
    }
    exit;
}
