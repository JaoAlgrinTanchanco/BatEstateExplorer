<?php
// signup_user.php: Handles AJAX signup
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Get and sanitize input
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name  = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email      = isset($_POST['email']) ? trim($_POST['email']) : '';
$password   = isset($_POST['password']) ? $_POST['password'] : '';
$phone      = isset($_POST['phone']) ? trim($_POST['phone']) : '';

if (!$first_name || !$last_name || !$email || !$password) {
    $response['message'] = 'All required fields must be filled.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email address.';
    echo json_encode($response);
    exit;
}

if (strlen($password) < 6) {
    $response['message'] = 'Password must be at least 6 characters.';
    echo json_encode($response);
    exit;
}

// Check if email already exists
$stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ?');
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) > 0) {
    $response['message'] = 'Email already exists.';
    echo json_encode($response);
    exit;
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table
$stmt = mysqli_prepare($conn, 'INSERT INTO users 
    (email, password_hash, first_name, last_name, phone, user_type, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?)');
$user_type = 'user';   // ✅ default user type
$status = 'active';    // ✅ default status
mysqli_stmt_bind_param($stmt, 'sssssss', $email, $password_hash, $first_name, $last_name, $phone, $user_type, $status);

if (!mysqli_stmt_execute($stmt)) {
    $response['message'] = 'Database error: ' . mysqli_stmt_error($stmt);
    echo json_encode($response);
    exit;
}

$response['success'] = true;
$response['message'] = 'Account created successfully!';
echo json_encode($response);
