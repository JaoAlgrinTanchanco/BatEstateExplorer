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
$profile_image_url = $google_user->picture;

// Only allow Gmail accounts
if (!str_ends_with($email, '@gmail.com')) {
    die('Only Gmail accounts allowed.');
}

// Check if user already exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    // Create a new user record for Google login
    $password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $phone = null;

    // =============================
    // 📸 Handle Google profile image
    // =============================
    $profile_picture_path = null;

    if ($profile_image_url) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/profile_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo(parse_url($profile_image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (!$ext) $ext = 'jpg'; // default fallback

        $filename = 'pfp_google_' . uniqid('', true) . '.' . $ext;
        $destination = $uploadDir . $filename;

        // Try to download Google profile image
        $imageData = @file_get_contents($profile_image_url);
        if ($imageData !== false) {
            file_put_contents($destination, $imageData);
            // Store relative path (same as signup_user.php)
            $profile_picture_path = str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', $destination);
        }
    }

    // ✅ Added is_google = 1 in insert + local image path
    $stmt = $conn->prepare("
        INSERT INTO users (email, password_hash, first_name, last_name, phone, user_type, status, profile_image_path, is_google)
        VALUES (?, ?, ?, ?, ?, 'user', 'active', ?, 1)
    ");
    $stmt->bind_param('ssssss', $email, $password_hash, $first_name, $last_name, $phone, $profile_picture_path);
    $stmt->execute();

} else {
    // ✅ If the user exists but wasn't marked as Google user, update it
    if (isset($user['is_google']) && (int)$user['is_google'] === 0) {
        $update = $conn->prepare("UPDATE users SET is_google = 1 WHERE id = ?");
        $update->bind_param('i', $user['id']);
        $update->execute();
    }

    // ✅ If the profile picture is empty, fetch it from Google
    if (empty($user['profile_image_path']) && $profile_image_url) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/profile_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo(parse_url($profile_image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (!$ext) $ext = 'jpg';
        $filename = 'pfp_google_' . uniqid('', true) . '.' . $ext;
        $destination = $uploadDir . $filename;

        $imageData = @file_get_contents($profile_image_url);
        if ($imageData !== false) {
            file_put_contents($destination, $imageData);
            $profile_picture_path = str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', $destination);

            $update = $conn->prepare("UPDATE users SET profile_image_path = ? WHERE id = ?");
            $update->bind_param('si', $profile_picture_path, $user['id']);
            $update->execute();
        }
    }
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
?>
