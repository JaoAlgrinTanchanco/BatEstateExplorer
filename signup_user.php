<?php
// signup_user.php: Handles AJAX signup from Auth Modal
header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Get and sanitize input
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!$name || !$email || !$password) {
    $response['message'] = 'All fields are required.';
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

// Split name into first and last (simple split)
$parts = explode(' ', $name, 2);
$first_name = $parts[0];
$last_name = isset($parts[1]) ? $parts[1] : '';

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table
$stmt = mysqli_prepare($conn, 'INSERT INTO users (email, password_hash, first_name, last_name, user_type, status) VALUES (?, ?, ?, ?, ?, ?)');
$user_type = 'buyer'; // Default for modal signup
$status = 'active';
mysqli_stmt_bind_param($stmt, 'ssssss', $email, $password_hash, $first_name, $last_name, $user_type, $status);
if (!mysqli_stmt_execute($stmt)) {
    $response['message'] = 'Database error: ' . mysqli_stmt_error($stmt);
    echo json_encode($response);
    exit;
}

$response['success'] = true;
$response['message'] = 'Account created successfully!';
echo json_encode($response);
