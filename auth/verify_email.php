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

        $mail->Body = '
        <div style="font-family: Arial, sans-serif; background:#f9fafc; padding:30px;">
            <table style="max-width:600px; margin:auto; background:#fff; border-radius:10px; padding:20px; text-align:center;">
                <tr>
                    <td>
                        <h2>Hi ' . htmlspecialchars($firstName) . '!</h2>
                        <p>Click the button below to verify your email and proceed with signup:</p>
                        <a href="' . $verifyUrl . '" style="display:inline-block; padding:12px 20px; background:#007bff; color:#fff; border-radius:5px; text-decoration:none; margin-top:20px;">Verify Email</a>
                        <p style="margin-top:20px; font-size:12px; color:#555;">If you did not register, please ignore this email.</p>
                    </td>
                </tr>
            </table>
        </div>';

        $mail->AltBody = "Click this link to verify your email: $verifyUrl";

        $mail->send();

        echo json_encode(['status' => 'success', 'message' => 'Verification email sent.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
    }
    exit;
}
