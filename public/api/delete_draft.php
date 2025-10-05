<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$draftId = intval($data['id'] ?? 0);

if (!$draftId) {
    echo json_encode(['success' => false, 'error' => 'Invalid draft ID']);
    exit;
}

$userId = $_SESSION['user_id'];

// Step 1: Fetch draft to get images
$stmt = $conn->prepare("SELECT image_path FROM property_drafts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $draftId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($draft = $result->fetch_assoc()) {
    if (!empty($draft['image_path'])) {
        $images = array_filter(explode(',', $draft['image_path']));
        foreach ($images as $imgPath) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $imgPath;
            if (file_exists($fullPath)) {
                @unlink($fullPath); // delete the file, suppress errors
            }
        }
    }

    // Step 2: Delete the draft record
    $delStmt = $conn->prepare("DELETE FROM property_drafts WHERE id = ? AND user_id = ?");
    $delStmt->bind_param("ii", $draftId, $userId);

    if ($delStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $delStmt->error]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Draft not found']);
}
