<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Read and decode the JSON payload ---
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON input.']);
    exit;
}

// --- Get logged-in user ID and payload ---
$user_id     = (int)($_SESSION['user_id'] ?? 0);
$property_id = (int)($data['property_id'] ?? 0);
$rating      = (int)($data['rating'] ?? 0);
$review_text = trim($data['review_text'] ?? '');

// --- Validate input ---
if (!$user_id || !$property_id || !$rating || empty($review_text)) {
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}

// --- Fetch user info ---
$stmt = $conn->prepare("SELECT privileges, first_name, last_name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

$privileges = json_decode($user['privileges'] ?? '[]', true);
if (!is_array($privileges)) $privileges = [];
$privileges = array_map('intval', $privileges);

// Check if user can review this property
if (!in_array($property_id, $privileges, true)) {
    echo json_encode(['error' => 'You are not allowed to review this property.']);
    exit;
}

// --- Fetch company_prop_id from properties ---
$stmt = $conn->prepare("SELECT company_prop_id FROM properties WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$res = $stmt->get_result();
$property = $res->fetch_assoc();
$stmt->close();

$company_prop_id = !empty($property['company_prop_id']) ? $property['company_prop_id'] : null;

// --- Insert review ---
if ($company_prop_id !== null) {
    $stmt = $conn->prepare("
        INSERT INTO property_reviews (property_id, user_id, rating, review_text, company_prop_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiiss", $property_id, $user_id, $rating, $review_text, $company_prop_id);
} else {
    $stmt = $conn->prepare("
        INSERT INTO property_reviews (property_id, user_id, rating, review_text)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiis", $property_id, $user_id, $rating, $review_text);
}

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
