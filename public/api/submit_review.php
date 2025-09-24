<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Get logged in user ID and POST data ---
$user_id     = (int)($_SESSION['user_id'] ?? 0);
$property_id = (int)($_POST['property_id'] ?? 0);
$rating      = (int)($_POST['rating'] ?? 0);
$review_text = trim($_POST['review_text'] ?? '');

// --- Validate input ---
if (!$user_id || !$property_id || !$rating || !$review_text) {
    echo json_encode([
        'error' => 'All fields are required.',
        'debug' => compact('user_id', 'property_id', 'rating', 'review_text', 'session')
    ]);
    exit;
}

// --- Check user privileges ---
$stmt = $conn->prepare("SELECT privileges, first_name, last_name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

$privileges = json_decode($user['privileges'] ?? '[]', true);
if (!is_array($privileges)) $privileges = [];

// Use integer comparison for property_id
if (!in_array($property_id, $privileges, true)) {
    echo json_encode(['error' => 'You are not allowed to review this property.']);
    exit;
}

// --- Insert review ---
$stmt = $conn->prepare("
    INSERT INTO property_reviews (property_id, user_id, rating, review_text) 
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiis", $property_id, $user_id, $rating, $review_text);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'review' => [
            'user_name'   => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'rating'      => $rating,
            'review_text' => $review_text,
            'created_at'  => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
