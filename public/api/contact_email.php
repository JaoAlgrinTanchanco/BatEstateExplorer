<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP setup
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'help.batestate@gmail.com';
        $mail->Password   = 'panidueufcyqeeoq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Set the sender to the user's name
        $mail->setFrom('help.batestate@gmail.com', $name); 
        $mail->addAddress('batestate07@gmail.com', 'BatEstate Admin'); // admin inbox
        $mail->addReplyTo($email, $name);                               // user's email for reply

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Message from BatEstate Website';

        // Simple, professional HTML body without tables
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <h2 style='color: #0d6efd;'>New Contact Form Submission</h2>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Message:</strong><br>".nl2br(htmlspecialchars($message))."</p>
            <p style='margin-top: 20px; font-size: 0.9rem; color: #555;'>
                This message was sent via the BatEstateExplorer website contact form.
            </p>
        </div>
        ";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => "Thank you for reaching out! We'll get back to you soon."]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
}
?>
