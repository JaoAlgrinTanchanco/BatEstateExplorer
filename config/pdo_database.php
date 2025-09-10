<?php
session_start();
require_once '../config/pdo_database.php'; // make sure this points to your PDO config

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = trim($_POST['email']);

    // Check if email exists in DB
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Store OTP and expiry in session (10 minutes)
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_expires'] = time() + 600;

        // For local testing: display OTP instead of sending email
        $message = "OTP for $email: <strong>$otp</strong> (Valid for 10 minutes)";
        
        // Uncomment this to redirect to OTP verification page
        // header('Location: verify_otp.php');
        // exit;
    } else {
        $message = "No account found with this email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<style>
body { font-family: Arial; background: #f4f4f4; padding: 50px; }
form { background: #fff; padding: 20px; border-radius: 8px; max-width: 400px; margin: auto; }
input[type=email] { width: 100%; padding: 10px; margin: 10px 0; }
button { padding: 10px 20px; }
.message { color: red; }
</style>
</head>
<body>

<h2>Forgot Password</h2>
<?php if($message) echo "<p class='message'>$message</p>"; ?>
<form method="POST" action="">
    <label>Email Address</label>
    <input type="email" name="email" placeholder="Enter your account email" required>
    <button type="submit">Send OTP</button>
</form>

</body>
</html>
