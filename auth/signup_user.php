<?php
// signup_user.php: Handles normal form signup with optional profile picture
session_start();
require_once __DIR__ . '/../config/database.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Invalid request method.'];
    header('Location: signup.php');
    exit;
}

// === Get & sanitize input ===
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';
$phone      = trim($_POST['phone'] ?? '');

// === Basic Validation ===
if (!$first_name || !$last_name || !$email || !$password || !$confirm) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'All required fields must be filled.'];
    header('Location: signup.php');
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Invalid email address format.'];
    header('Location: signup.php');
    exit;
}

// Validate phone (digits only, optional +, length 7-15)
if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Invalid phone number format.'];
    header('Location: signup.php');
    exit;
}

if ($password !== $confirm) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Passwords do not match.'];
    header('Location: signup.php');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Password must be at least 6 characters long.'];
    header('Location: signup.php');
    exit;
}

// =======================================
// 🔍 Real Email Verification
// =======================================
list(, $domain) = explode('@', $email);

// 1️⃣ Check if domain has valid MX record (can receive mail)
if (!checkdnsrr($domain, 'MX')) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Invalid or unreachable email domain. Please use a real email provider.'];
    header('Location: signup.php');
    exit;
}

// 2️⃣ Block disposable / temporary email domains
$disposableDomains = [
    'mailinator.com', '10minutemail.com', 'tempmail.com', 'guerrillamail.com',
    'trashmail.com', 'yopmail.com', 'fakeinbox.com', 'dispostable.com',
    'getnada.com', 'maildrop.cc', 'sharklasers.com'
];

if (in_array(strtolower($domain), $disposableDomains, true)) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Disposable or temporary email addresses are not allowed.'];
    header('Location: signup.php');
    exit;
}

// === Check if email already exists ===
$stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email=?');
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Email is already registered.'];
    header('Location: signup.php');
    exit;
}
mysqli_stmt_close($stmt);

// === Hash password ===
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// === Handle profile picture (now required) ===
if (empty($_FILES['profile_picture']['name']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Profile picture is required.'];
    header('Location: signup.php');
    exit;
}

$file = $_FILES['profile_picture'];
$allowedTypes = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'image/webp' // optional modern format
];

// Double-check MIME type (some GIFs may report as octet-stream)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes, true)) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Invalid profile picture type.'];
    header('Location: signup.php');
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'pfp_' . uniqid('', true) . '.' . $ext;
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/profile_images/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Failed to upload profile picture.'];
    header('Location: signup.php');
    exit;
}

// Store relative path
$profile_picture_path = str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', $destination);

// === Insert user into DB ===
$stmt = mysqli_prepare($conn, 'INSERT INTO users 
    (email, password_hash, first_name, last_name, phone, user_type, status, profile_image_path)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$user_type = 'user';
$status    = 'active';

mysqli_stmt_bind_param($stmt, 'ssssssss', $email, $password_hash, $first_name, $last_name, $phone, $user_type, $status, $profile_picture_path);

if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Database error: ' . mysqli_stmt_error($stmt)];
    header('Location: signup.php');
    exit;
}

mysqli_stmt_close($stmt);
$conn->close();

// === Success ===
$_SESSION['notification'] = ['type' => 'success', 'message' => 'Account created successfully! You can now log in.'];
header('Location: login.php');
exit;
?>
