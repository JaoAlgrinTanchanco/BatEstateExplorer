<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get draft ID from JSON payload
$data = json_decode(file_get_contents('php://input'), true);
$draftId = intval($data['id'] ?? 0);

if (!$draftId) {
    echo json_encode(['success' => false, 'error' => 'Invalid draft ID']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Step 1: Fetch draft and its image paths
    $stmt = $conn->prepare("SELECT image_path FROM property_drafts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $draftId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $draft = $result->fetch_assoc();

    if (!$draft) {
        throw new Exception('Draft not found');
    }

    $deletedFiles = [];
    $resolvedPaths = [];

    if (!empty($draft['image_path'])) {
        $paths = array_filter(explode(',', $draft['image_path']));
        $projectRoot = realpath(__DIR__ . '/../../'); // BatEstateExplorer root

        foreach ($paths as $p) {
            // Normalize slashes and prepend project root
            $absPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $p);
            $resolvedPaths[$p] = $absPath;

            if (file_exists($absPath)) {
                if (unlink($absPath)) {
                    $deletedFiles[] = $absPath;
                }
            }
        }
    }

    // Step 2: Delete draft from DB
    $stmt = $conn->prepare("DELETE FROM property_drafts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $draftId, $userId);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'debug' => [
            'draft_id' => $draftId,
            'image_paths_in_db' => $draft['image_path'] ?? '',
            'resolved_paths' => $resolvedPaths,
            'deleted_files' => $deletedFiles
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'draft_id' => $draftId,
            'image_paths_in_db' => $draft['image_path'] ?? '',
            'resolved_paths' => $resolvedPaths ?? [],
            'deleted_files' => $deletedFiles ?? []
        ]
    ]);
}
