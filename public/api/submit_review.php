<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

// Get input
$user_id     = (int)($_POST['user_id'] ?? 0);  // 🔹 user_id must be sent in form
$property_id = (int)($_POST['property_id'] ?? 0);
$rating      = (int)($_POST['rating'] ?? 0);
$review_text = trim($_POST['review_text'] ?? '');

if (!$user_id || !$property_id || !$rating || !$review_text) {
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}

// ✅ Check privileges from users table
$stmt = $conn->prepare("SELECT privileges FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

$privileges = json_decode($row['privileges'] ?? '[]', true);
if (!is_array($privileges)) $privileges = [];

if (!in_array((string)$property_id, $privileges, true)) {
    echo json_encode(['error' => 'You are not allowed to review this property.']);
    exit;
}

// ✅ Insert review
$stmt = $conn->prepare("
    INSERT INTO property_reviews (property_id, user_id, rating, review_text) 
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiis", $property_id, $user_id, $rating, $review_text);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
