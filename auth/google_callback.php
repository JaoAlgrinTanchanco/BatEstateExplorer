<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/api/auth_functions.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize Google Client
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope(['email', 'profile']);

// Validate code
if (!isset($_GET['code'])) {
    die('No code provided.');
}

// Exchange authorization code for access token
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    die('Google login error: ' . htmlspecialchars($token['error']));
}

$client->setAccessToken($token['access_token']);

// Get user profile from Google
$google_service = new Google_Service_Oauth2($client);
$google_user = $google_service->userinfo->get();

$email = $google_user->email;
$first_name = $google_user->givenName;
$last_name = $google_user->familyName;
$profile_image = $google_user->picture;

// Only allow Gmail accounts
if (!str_ends_with($email, '@gmail.com')) {
    die('Only Gmail accounts allowed.');
}

// Check if user already exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    // Create a new user record for Google login
    $password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $phone = null;
    $stmt = $conn->prepare("
        INSERT INTO users (email, password_hash, first_name, last_name, phone, user_type, status, profile_image_path)
        VALUES (?, ?, ?, ?, ?, 'user', 'active', ?)
    ");
    $stmt->bind_param('ssssss', $email, $password_hash, $first_name, $last_name, $phone, $profile_image);
    $stmt->execute();
}

// Attempt login using shared logic
$result = login_user($conn, $email, null, true);

// Handle different outcomes
if (isset($result['blocked'])) {
    header("Location: /BatEstateExplorer/public/auth/login.php?blocked=1&reason=" . urlencode($result['reason']) . "&duration=" . urlencode($result['duration']));
    exit;
} elseif (isset($result['error'])) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => $result['error']];
    header("Location: /BatEstateExplorer/public/auth/login.php");
    exit;
} else {
    // Redirect to dashboard
    header('Location: /BatEstateExplorer/public/controllers/user_dashboard.php?view=home');
    exit;
}
