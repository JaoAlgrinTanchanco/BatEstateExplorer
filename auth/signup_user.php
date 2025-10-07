<?php
<<<<<<< HEAD
// signup_user.php: Handles normal form signup with notifications
session_start();
require_once __DIR__ . '/../config/database.php';

// Only allow POST requests
=======
// signup_user.php: Handles normal form signup with optional profile picture
session_start();
require_once __DIR__ . '/../config/database.php';

// Only allow POST
>>>>>>> origin/ansel
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Invalid request method.'
    ];
<<<<<<< HEAD
    header('Location: signup.php'); // fixed path
    exit;
}

// Get and sanitize input
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name  = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email      = isset($_POST['email']) ? trim($_POST['email']) : '';
$password   = isset($_POST['password']) ? $_POST['password'] : '';
$confirm    = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$phone      = isset($_POST['phone']) ? trim($_POST['phone']) : '';

// Validation
if (!$first_name || !$last_name || !$email || !$password || !$confirm) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'All required fields must be filled.'
    ];
    header('Location: signup.php'); // fixed path
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Invalid email address.'
    ];
    header('Location: signup.php'); // fixed path
    exit;
}

if ($password !== $confirm) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Passwords do not match.'
    ];
    header('Location: signup.php'); // fixed path
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Password must be at least 6 characters.'
    ];
    header('Location: signup.php'); // fixed path
    exit;
}

// Check if email already exists
$stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ?');
=======
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

// === Validation ===
if (!$first_name || !$last_name || !$email || !$password || !$confirm) {
    $_SESSION['notification'] = ['type'=>'error','message'=>'All required fields must be filled.'];
    header('Location: signup.php');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['notification'] = ['type'=>'error','message'=>'Invalid email address.'];
    header('Location: signup.php');
    exit;
}
if ($password !== $confirm) {
    $_SESSION['notification'] = ['type'=>'error','message'=>'Passwords do not match.'];
    header('Location: signup.php');
    exit;
}
if (strlen($password) < 6) {
    $_SESSION['notification'] = ['type'=>'error','message'=>'Password must be at least 6 characters.'];
    header('Location: signup.php');
    exit;
}

// === Check if email exists ===
$stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email=?');
>>>>>>> origin/ansel
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) > 0) {
<<<<<<< HEAD
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Email already exists.'
    ];
    header('Location: signup.php'); // fixed path
    exit;
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table
$stmt = mysqli_prepare($conn, 'INSERT INTO users 
    (email, password_hash, first_name, last_name, phone, user_type, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?)');
$user_type = 'user';
$status    = 'active';
mysqli_stmt_bind_param($stmt, 'sssssss', $email, $password_hash, $first_name, $last_name, $phone, $user_type, $status);

if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Database error: ' . mysqli_stmt_error($stmt)
    ];
    header('Location: signup.php'); // fixed path
    exit;
}

// Success: set notification and redirect to login
$_SESSION['notification'] = [
    'type' => 'success',
    'message' => 'Account created successfully! You can now login.'
];
header('Location: login.php'); // fixed path
exit;
=======
    $_SESSION['notification'] = ['type'=>'error','message'=>'Email already exists.'];
    header('Location: signup.php');
    exit;
}

// === Hash password ===
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// === Handle profile picture (optional) ===
$profile_picture_path = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['profile_picture'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes, true)) {
        $_SESSION['notification'] = ['type'=>'error','message'=>'Invalid profile picture type.'];
        header('Location: signup.php');
        exit;
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'pfp_'.uniqid().'.'.$ext;
    $uploadDir = $_SERVER['DOCUMENT_ROOT'].'/BatEstateExplorer/storage/uploads/profile_images/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
    $destination = $uploadDir.$filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $_SESSION['notification'] = ['type'=>'error','message'=>'Failed to upload profile picture.'];
        header('Location: signup.php');
        exit;
    }
    // Store relative path
    $profile_picture_path = str_replace($_SERVER['DOCUMENT_ROOT'].'/', '', $destination);
}

// === Insert user into DB ===
$stmt = mysqli_prepare($conn, 'INSERT INTO users 
    (email, password_hash, first_name, last_name, phone, user_type, status, profile_image_path)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$user_type = 'user';
$status    = 'active';
mysqli_stmt_bind_param($stmt, 'ssssssss', $email, $password_hash, $first_name, $last_name, $phone, $user_type, $status, $profile_picture_path);

if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['notification'] = ['type'=>'error','message'=>'Database error: '.mysqli_stmt_error($stmt)];
    header('Location: signup.php');
    exit;
}

// Success
$_SESSION['notification'] = ['type'=>'success','message'=>'Account created successfully! You can now login.'];
header('Location: login.php');
exit;
?>
>>>>>>> origin/ansel
