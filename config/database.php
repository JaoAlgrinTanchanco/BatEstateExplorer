<?php
// Database configuration for BatEstate
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'batestate';

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Function to sanitize input
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Function to hash password
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Function to generate JWT-like session token
function generate_token($user_id, $email, $user_type) {
    $payload = [
        'user_id' => $user_id,
        'email' => $email,
        'user_type' => $user_type,
        'exp' => time() + (24 * 60 * 60) // 24 hours
    ];
    return base64_encode(json_encode($payload));
}

// Function to decode token
function decode_token($token) {
    $decoded = json_decode(base64_decode($token), true);
    if ($decoded && isset($decoded['exp']) && $decoded['exp'] > time()) {
        return $decoded;
    }
    return false;
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_token']) && decode_token($_SESSION['user_token']);
}

// Function to check if user is admin
function is_admin() {
    if (!is_logged_in()) return false;
    $token_data = decode_token($_SESSION['user_token']);
    return $token_data && $token_data['user_type'] === 'admin';
}

// Function to get current user data
function get_logged_in_user($conn) {
    if (!is_logged_in()) return null;
    $token_data = decode_token($_SESSION['user_token']);
    if (!$token_data) return null;
    
    $user_id = $token_data['user_id'];
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}
?> 