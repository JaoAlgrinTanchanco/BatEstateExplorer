<?php
session_start();
require_once '../config/pdo_database.php';
require_once '../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $otp = rand(100000, 999999);

        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_expires'] = time() + 600;

        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // or your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'your-email@gmail.com'; // SMTP username
            $mail->Password = 'your-app-password'; // SMTP password / app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('no-reply@example.com', 'BatEstate');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body    = "Your OTP to reset your password is: <b>$otp</b><br>It expires in 10 minutes.";

            $mail->send();
            header('Location: verify_otp.php');
            exit;
        } catch (Exception $e) {
            $message = "Failed to send OTP. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $message = "No account found with this email.";
    }
}
