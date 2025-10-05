<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$userId = $_SESSION['user_id'];
$draftId = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT * FROM property_drafts 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $draftId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($draft = $result->fetch_assoc()) {
    // Convert comma-separated image paths into an array
    $draft['images'] = [];
    if (!empty($draft['image_path'])) {
        $paths = array_filter(explode(',', $draft['image_path']));
        $draft['images'] = array_values($paths); // reindex
    }

    echo json_encode($draft);
} else {
    echo json_encode(['error' => 'Draft not found']);
}
