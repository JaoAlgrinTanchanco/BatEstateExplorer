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

    // Generate OTP
    $otp = rand(100000, 999999);
    $_SESSION['signup_email'] = $email;
    $_SESSION['signup_first_name'] = $firstName;
    $_SESSION['signup_last_name'] = $lastName;
    $_SESSION['signup_otp'] = $otp;
    $_SESSION['signup_otp_expires'] = time() + 300; // 5 minutes

    // Send OTP Email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'batestate07@gmail.com';
        $mail->Password   = 'jgsiczkzxyvuxvgb';
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
                        <p>Please use the OTP below to verify your email address:</p>
                        <div style="font-size:24px; font-weight:bold; margin:20px 0;">' . $otp . '</div>
                        <p>This OTP will expire in 5 minutes.</p>
                    </td>
                </tr>
            </table>
        </div>';

        $mail->AltBody = "Your OTP is: $otp (expires in 5 minutes)";

        $mail->send();

        echo json_encode(['status' => 'success', 'message' => 'OTP sent to your email.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
    }
    exit;
}
