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
    // -------------------------
    // Images
    // -------------------------
    $draft['images'] = [];
    if (!empty($draft['image_path'])) {
        $paths = array_filter(explode(',', $draft['image_path']));
        foreach ($paths as $p) {
            $urlPath = str_replace('\\', '/', $p);
            $draft['images'][] = '/' . ltrim($urlPath, '/');
        }
    }

    // -------------------------
    // Documents
    // -------------------------
    $draft['property_document_path'] = '';
    if (!empty($draft['property_document_path'])) {
        $docs = array_filter(explode(',', $draft['property_document_path']));
        $normalized = [];
        foreach ($docs as $d) {
            $urlPath = str_replace('\\', '/', $d);
            $normalized[] = '/' . ltrim($urlPath, '/');
        }
        $draft['property_document_path'] = implode(',', $normalized);
    }

    echo json_encode($draft);
} else {
    echo json_encode(['error' => 'Draft not found']);
}
