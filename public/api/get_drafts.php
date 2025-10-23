<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, title, created_at 
    FROM property_drafts 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$drafts = [];
while ($row = $result->fetch_assoc()) {
    $drafts[] = $row;
}

echo json_encode($drafts);
