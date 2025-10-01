<?php
// signup_user.php: Handles normal form signup with notifications
session_start();
require_once __DIR__ . '/../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Invalid request method.'
    ];
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
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) > 0) {
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
