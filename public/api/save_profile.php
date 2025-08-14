<?php
session_start();
require_once __DIR__ . '/../app/bootstrap.php';
require_login(); // Make sure user is logged in

header('Content-Type: application/json');

// Get current user ID
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

// Fetch user from database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

// Collect POST data
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$email     = trim($_POST['email'] ?? '');
$newPass   = $_POST['password'] ?? '';
$currentPass = $_POST['current_password'] ?? '';

// Validate required fields
if (!$firstName || !$lastName || !$email) {
    echo json_encode(['status' => 'error', 'message' => 'First name, last name, and email are required.']);
    exit;
}

// If changing password, verify current password
if ($newPass) {
    if (!$currentPass || !password_verify($currentPass, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
        exit;
    }
    $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
} else {
    $hashedPass = $user['password']; // Keep existing password
}

// Check if email is being changed to one already in use
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $userId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already in use by another account.']);
    exit;
}

// Update user
$stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("sssssi", $firstName, $lastName, $phone, $email, $hashedPass, $userId);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update profile.']);
}
?>
