<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Sanitize and validate email
if (!isset($_GET['email']) || !filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email parameter']);
    exit;
}

$email = $_GET['email'];

// Query user
$stmt = $conn->prepare("SELECT is_google FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Return JSON response
if ($user) {
    echo json_encode([
        'exists' => true,
        'is_google' => (bool)$user['is_google']
    ]);
} else {
    echo json_encode([
        'exists' => false,
        'is_google' => false
    ]);
}

$stmt->close();
$conn->close();
