<?php
session_start();
require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'User not logged in.'
    ];
    header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=overview');
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'User not found.'
    ];
    header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=overview');
    exit;
}

// Collect POST data
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$email     = trim($_POST['email'] ?? '');
$address   = trim($_POST['address'] ?? '');
$status    = $user['status'];
$userType  = $user['user_type']; // preserve current user_type

if (!$firstName || !$lastName || !$email) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'First name, last name, and email are required.'
    ];
    header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=overview');
    exit;
}

// Check email uniqueness
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $userId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Email already in use.'
    ];
    header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=overview');
    exit;
}
$stmt->close();

// Update user (password untouched)
$stmt = $conn->prepare("
    UPDATE users SET
        first_name = ?, last_name = ?, phone = ?, email = ?,
        address = ?, user_type = ?, status = ?, updated_at = NOW()
    WHERE id = ?
");

$stmt->bind_param(
    "sssssssi",
    $firstName, $lastName, $phone, $email,
    $address, $userType, $status, $userId
);

if ($stmt->execute()) {
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Profile updated successfully.'
    ];
} else {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Failed to update profile: ' . $stmt->error
    ];
}

$stmt->close();
$conn->close();

// Redirect back to profile overview tab
header('Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=overview');
exit;
