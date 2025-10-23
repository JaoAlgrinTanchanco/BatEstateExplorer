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
    echo 'Database connection failed.';
    exit;
}

// Check if token exists in the URL
if (!isset($_GET['token'])) {
    echo 'Invalid verification link.';
    exit;
}

$token = $_GET['token'];

// Find token in the database
$stmt = $pdo->prepare("SELECT * FROM email_verifications WHERE token = ?");
$stmt->execute([$token]);
$verification = $stmt->fetch();

if (!$verification) {
    echo 'Invalid or expired verification link.';
    exit;
}

// Optional: Check if token is expired (30 minutes)
$createdAt = strtotime($verification['created_at']);
if (time() - $createdAt > 1800) {
    echo 'Verification link has expired.';
    exit;
}

// Mark email as verified in session for front-end polling
$_SESSION['verified_email'] = $verification['email'];

// Optionally remove token from DB to prevent reuse
$stmt = $pdo->prepare("DELETE FROM email_verifications WHERE token = ?");
$stmt->execute([$token]);

// Show a simple HTML page with a button
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verified - BatEstate Explorer</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background:#f9fafc; }
        .checkmark { font-size: 50px; color: #28a745; margin-bottom: 20px; }
        .message { font-size: 20px; margin-bottom: 30px; }
        a.button {
            display: inline-block;
            padding: 12px 25px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        a.button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="checkmark">&#10004;</div>
    <div class="message">Your email has been successfully verified! You can now return to the signup page.</div>
    <a class="button" href="signup.php" onclick="window.close();">Return to Signup</a>
</body>
</html>
