<?php
session_start();
require_once __DIR__ . '/../app/bootstrap.php';
require_login(); // Ensure user is logged in

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
$stmt->close();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

// Collect POST data
$firstName      = trim($_POST['first_name'] ?? '');
$lastName       = trim($_POST['last_name'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$email          = trim($_POST['email'] ?? '');
$newPass        = $_POST['password'] ?? '';
$currentPass    = $_POST['current_password'] ?? '';
$address        = trim($_POST['address'] ?? '');
$education      = trim($_POST['education'] ?? '');
$school         = trim($_POST['school'] ?? '');
$course         = trim($_POST['course'] ?? '');
$graduationYear = trim($_POST['graduation_year'] ?? '');
$certifications = trim($_POST['certifications'] ?? '');
$training       = trim($_POST['training'] ?? '');
$agentType      = trim($_POST['agent_type'] ?? '');
$brokerId       = trim($_POST['broker_id'] ?? '');
$licenseNumber  = trim($_POST['license_number'] ?? '');
$experienceYears= (int)($_POST['experience_years'] ?? 0);
$specialization = trim($_POST['specialization'] ?? '');
$bio            = trim($_POST['bio'] ?? '');
$status         = trim($_POST['status'] ?? $user['status']);
$adminNotes     = trim($_POST['admin_notes'] ?? '');

// Validate required fields
if (!$firstName || !$lastName || !$email) {
    echo json_encode(['status' => 'error', 'message' => 'First name, last name, and email are required.']);
    exit;
}

// Handle password change
if (!empty($newPass)) {
    if (!$currentPass || !password_verify($currentPass, $user['password_hash'])) {
        echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
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
    echo json_encode(['status' => 'error', 'message' => 'Email already in use by another account.']);
    exit;
}
$stmt->close();

// Update user with all fields (without admin_notes)
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
    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update profile: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
