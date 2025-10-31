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
    SELECT 
        id, user_id, title, location, price, lot_size, property_type, 
        bedrooms, bathrooms, description, image_path, property_document_path,
        company_prop_id, company_listing_id, is_company_listing
    FROM property_drafts
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $draftId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($draft = $result->fetch_assoc()) {
    // -------------------------
    // Normalize Images
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
    // Normalize Documents
    // -------------------------
    $docPaths = $draft['property_document_path'] ?? '';
    $draft['property_document_path'] = '';

    if (!empty($docPaths)) {
        $docs = array_filter(explode(',', $docPaths));
        $normalized = [];
        foreach ($docs as $d) {
            $urlPath = str_replace('\\', '/', $d);
            $normalized[] = '/' . ltrim($urlPath, '/');
        }
        $draft['property_document_path'] = implode(',', $normalized);
    }

    // -------------------------
    // Company Listing Fields
    // -------------------------
    $draft['is_company_listing'] = intval($draft['is_company_listing'] ?? 0);
    $draft['company_listing_id'] = $draft['company_listing_id'] ?? '';
    $draft['company_prop_id'] = $draft['company_prop_id'] ?? '';

    echo json_encode($draft);
} else {
    echo json_encode(['error' => 'Draft not found']);
}
