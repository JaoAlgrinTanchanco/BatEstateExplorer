<?php
session_start();
require_once __DIR__ . '/../app/bootstrap.php';
require_login(); // Ensure user is logged in

// Get current user ID
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    $_SESSION['flash_error'] = 'User not logged in.';
    header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile');
    exit;
}

// Fetch user from database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['flash_error'] = 'User not found.';
    header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile');
    exit;
}

// Preserve current user_type
$userType = $user['user_type'];

// Collect POST data
$firstName      = trim($_POST['first_name'] ?? '');
$lastName       = trim($_POST['last_name'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$email          = trim($_POST['email'] ?? '');
$currentPass    = $_POST['current_password'] ?? '';
$newPass        = $_POST['new_password'] ?? '';
$address        = trim($_POST['address'] ?? '');
$status         = trim($_POST['status'] ?? $user['status']);

// Validate required fields
if (!$firstName || !$lastName || !$email) {
    $_SESSION['flash_error'] = 'First name, last name, and email are required.';
    header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile');
    exit;
}

// Handle password change
if (!empty($newPass)) {
    if (!$currentPass || !password_verify($currentPass, $user['password_hash'])) {
        $_SESSION['flash_error'] = 'Current password is incorrect.';
        header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile');
        exit;
    }
    $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
} else {
    $hashedPass = $user['password_hash']; // Keep existing password
}

// Check if email is already in use by another user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $userId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['flash_error'] = 'Email already in use by another account.';
    header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile');
    exit;
}
$stmt->close();

// Update user
$stmt = $conn->prepare("
    UPDATE users SET
        first_name = ?, last_name = ?, phone = ?, email = ?, password_hash = ?,
        address = ?, user_type = ?, status = ?,
        updated_at = NOW()
    WHERE id = ?
");

$stmt->bind_param(
    "ssssssssi",
    $firstName, $lastName, $phone, $email, $hashedPass,
    $address, $userType, $status,
    $userId
);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = 'Profile updated successfully.';
} else {
    $_SESSION['flash_error'] = 'Failed to update profile: ' . $stmt->error;
}
$stmt->close();
$conn->close();

// Redirect back to profile
header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile');
exit;
