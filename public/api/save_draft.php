<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

$title = trim($_POST['title'] ?? '');
$location = trim($_POST['location'] ?? '');
$price = $_POST['price'] ?? null;
$lot_size = $_POST['lot_size'] ?? null;
$property_type = trim($_POST['property_type'] ?? '');
$bedrooms = $_POST['bedrooms'] ?? null;
$bathrooms = $_POST['bathrooms'] ?? null;
$description = trim($_POST['description'] ?? '');
$images = $_POST['images'] ?? '[]';

if (!$title) {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO property_drafts 
    (user_id, title, location, price, lot_size, property_type, bedrooms, bathrooms, description, images)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "issddsiiss",
    $userId, $title, $location, $price, $lot_size, $property_type, $bedrooms, $bathrooms, $description, $images
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Draft saved']);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
