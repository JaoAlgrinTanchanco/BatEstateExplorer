<?php
// PDO Database configuration for BatEstate
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
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // fetch assoc arrays
            PDO::ATTR_EMULATE_PREPARES => false, // use native prepared statements
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to sanitize input (optional with PDO)
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
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
function get_logged_in_user($pdo) {
    if (!is_logged_in()) return null;
    $token_data = decode_token($_SESSION['user_token']);
    if (!$token_data) return null;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$token_data['user_id']]);
    return $stmt->fetch();
}
