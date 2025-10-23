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
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Merriweather:wght@700&display=swap');
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    html, body {
      height: 100%;
      font-family: "Inter", Arial, sans-serif;
      background: #f9fafc;
      color: #111;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow-x: hidden;
      position: relative;
    }
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url("../images/Vector 1.png");
      background-repeat: repeat;
      background-position: center;
      background-size: 300px auto;
      opacity: 0.05;
      z-index: 0;
      pointer-events: none;
    }

    .verify-success-wrapper {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 440px;
      padding: 2.8rem 2.2rem;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
      text-align: center;
      animation: fadeInUp 0.6s ease forwards;
    }

    .verify-success-wrapper .checkmark {
      font-size: 60px;
      color: #28a745;
      margin-bottom: 1rem;
    }

    .verify-success-wrapper h2 {
      font-family: "Merriweather", serif;
      font-size: 1.8rem;
      margin-bottom: 0.6rem;
    }

    .verify-success-wrapper p {
      font-size: 0.95rem;
      color: #333;
      line-height: 1.6;
      margin-bottom: 2rem;
    }

    .verify-success-wrapper a.button {
      display: inline-flex;
      justify-content: center;
      align-items: center;
      gap: 0.4rem;
      padding: 0.9rem 1.8rem;
      border-radius: 30px;
      background: #111;
      color: #fff;
      font-size: 1rem;
      font-weight: 500;
      text-decoration: none;
      transition: background 0.3s ease;
    }
    .verify-success-wrapper a.button:hover {
      background: #333;
    }

    .verify-success-wrapper .footer {
      margin-top: 1.8rem;
      font-size: 0.75rem;
      color: #aaa;
      border-top: 1px solid #eee;
      padding-top: 1rem;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(25px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 500px) {
      .verify-success-wrapper {
        padding: 2rem 1.5rem;
        border-radius: 15px;
      }
      .verify-success-wrapper h2 {
        font-size: 1.5rem;
      }
      .verify-success-wrapper a.button {
        font-size: 0.95rem;
        padding: 0.8rem 1.4rem;
      }
    }
  </style>
</head>
<body>
  <div class="verify-success-wrapper">
    <div class="checkmark">&#10004;</div>
    <h2>Email Verified!</h2>
    <p>Your email has been successfully verified.<br>You can now return to the signup page to complete your registration.</p>
    <a class="button" href="signup.php" onclick="window.close();">Return to Signup</a>
    <div class="footer">© <?= date('Y') ?> BatEstate. All rights reserved.</div>
  </div>
</body>
</html>
