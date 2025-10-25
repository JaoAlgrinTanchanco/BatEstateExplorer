<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/pdo_database.php';
session_start();

try {
    // =========================
    // Check logged-in user
    // =========================
    $user_data = get_logged_in_user($pdo);
    if (!$user_data) {
        throw new Exception("No logged in user detected.");
    }
    $user_id = $user_data['id'];

    // =========================
    // Collect form data
    // =========================
    $draft_id      = isset($_POST['id']) ? intval($_POST['id']) : null;
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $price         = isset($_POST['price']) ? floatval($_POST['price']) : null;
    $location      = trim($_POST['location'] ?? '');
    $bedrooms      = isset($_POST['bedrooms']) ? intval($_POST['bedrooms']) : null;
    $bathrooms     = isset($_POST['bathrooms']) ? intval($_POST['bathrooms']) : null;
    $lot_size      = isset($_POST['lot_size']) ? floatval($_POST['lot_size']) : null;
    $property_type = trim($_POST['property_type'] ?? '');

    // =========================
    // Setup upload directories
    // =========================
    $upload_dir     = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/draft/';
    $db_path_prefix = 'storage/uploads/draft/';

    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    // =========================
    // Handle image uploads (multiple)
    // =========================
    $uploadedImagePaths = [];
    if (!empty($_FILES['images']['name'][0])) {
        $file_count = min(count($_FILES['images']['name']), 10);
        for ($i = 0; $i < $file_count; $i++) {
            $tmp   = $_FILES['images']['tmp_name'][$i];
            $name  = $_FILES['images']['name'][$i];
            $error = $_FILES['images']['error'][$i];

            if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif'])) continue;

            $newFileName = uniqid('draft_', true) . '.' . $ext;
            $destination = $upload_dir . $newFileName;
            $relativePath = $db_path_prefix . $newFileName;

            if (!move_uploaded_file($tmp, $destination)) {
                throw new Exception("Failed to move uploaded image: $name");
            }

            $uploadedImagePaths[] = $relativePath;
        }
    }
    $image_path = $uploadedImagePaths ? implode(',', $uploadedImagePaths) : null;

    // =========================
    // Handle property_documents upload (existing + new)
    // =========================
    $existingDocs = $_POST['existing_docs'] ?? [];
    if (!is_array($existingDocs)) $existingDocs = [];

    $uploadedDocPaths = [];

    // Handle new file uploads (new_docs[])
    if (!empty($_FILES['new_docs']['name'][0])) {
        $doc_count = min(count($_FILES['new_docs']['name']), 10);
        for ($i = 0; $i < $doc_count; $i++) {
            $tmp   = $_FILES['new_docs']['tmp_name'][$i];
            $name  = $_FILES['new_docs']['name'][$i];
            $error = $_FILES['new_docs']['error'][$i];

            if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf','doc','docx','jpg','jpeg','png'])) continue;

            $newDocName = uniqid('doc_', true) . '.' . $ext;
            $destination = $upload_dir . $newDocName;
            $relativePath = $db_path_prefix . $newDocName;

            if (!move_uploaded_file($tmp, $destination)) {
                throw new Exception("Failed to move uploaded document: $name");
            }

            $uploadedDocPaths[] = $relativePath;
        }
    }

    // Merge both old + new paths
    $allDocs = array_merge($existingDocs, $uploadedDocPaths);
    $property_document_path = !empty($allDocs) ? implode(',', $allDocs) : null;

    // =========================
    // Insert or Update Draft
    // =========================
    if ($draft_id) {
        // Update existing draft
        $query = "
            UPDATE property_drafts
            SET title = ?, location = ?, price = ?, lot_size = ?, property_type = ?, 
                bedrooms = ?, bathrooms = ?, description = ?, updated_at = NOW()";

        $params = [$title, $location, $price, $lot_size, $property_type, $bedrooms, $bathrooms, $description];

        if ($image_path) {
            $query .= ", image_path = CONCAT(IFNULL(image_path, ''), ?, IF(image_path IS NULL OR image_path = '', '', ','))";
            $params[] = $image_path;
        }
        if ($property_document_path) {
            $query .= ", property_document_path = CONCAT(IFNULL(property_document_path, ''), ?, IF(property_document_path IS NULL OR property_document_path = '', '', ','))";
            $params[] = $property_document_path;
        }

        $query .= " WHERE id = ? AND user_id = ?";
        $params[] = $draft_id;
        $params[] = $user_id;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $message = "Draft updated successfully!";
    } else {
        // Insert new draft
        $stmt = $pdo->prepare("
            INSERT INTO property_drafts
            (user_id, title, location, price, lot_size, property_type, bedrooms, bathrooms, description, image_path, property_document_path, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $user_id,
            $title,
            $location,
            $price,
            $lot_size,
            $property_type,
            $bedrooms,
            $bathrooms,
            $description,
            $image_path,
            $property_document_path
        ]);
        $message = "Draft saved successfully!";
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'debug' => [
            'POST' => $_POST,
            'FILES' => $_FILES,
            'uploaded_images' => $uploadedImagePaths,
            'uploaded_docs' => $uploadedDocPaths
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error saving draft: ' . $e->getMessage(),
        'debug' => [
            'POST' => $_POST,
            'FILES' => $_FILES
        ]
    ]);
}
