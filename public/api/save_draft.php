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
    if (!$user_data) throw new Exception("No logged in user detected.");
    $user_id = $user_data['id'];

    // =========================
    // Collect form data
    // =========================
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
    // Handle image uploads
    // =========================
    $uploadedImagePaths = [];
    if (!empty($_FILES['images']['name'])) {
        foreach ($_FILES['images']['name'] as $i => $name) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $tmp = $_FILES['images']['tmp_name'][$i];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) continue;

            $newName = uniqid('draft_img_', true) . '.' . $ext;
            $dest = $upload_dir . $newName;
            $relativePath = $db_path_prefix . $newName;

            if (move_uploaded_file($tmp, $dest)) {
                $uploadedImagePaths[] = $relativePath;
            }
        }
    }
    $image_path = !empty($uploadedImagePaths) ? implode(',', $uploadedImagePaths) : null;

    // =========================
    // Handle document uploads
    // =========================
    $uploadedDocPaths = [];
    if (!empty($_FILES['property_document']['name'])) {
        foreach ($_FILES['property_document']['name'] as $i => $name) {
            if ($_FILES['property_document']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $tmp = $_FILES['property_document']['tmp_name'][$i];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf','doc','docx','jpg','jpeg','png'])) continue;

            $newName = uniqid('draft_doc_', true) . '.' . $ext;
            $dest = $upload_dir . $newName;
            $relativePath = $db_path_prefix . $newName;

            if (move_uploaded_file($tmp, $dest)) {
                $uploadedDocPaths[] = $relativePath;
            }
        }
    }
    $property_document_path = !empty($uploadedDocPaths) ? implode(',', $uploadedDocPaths) : null;

    // =========================
    // Insert new draft
    // =========================
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

    echo json_encode([
        'success' => true,
        'message' => 'Draft saved successfully!',
        'draft_id' => $pdo->lastInsertId(),
        'debug' => [
            'uploaded_images' => $uploadedImagePaths,
            'uploaded_docs' => $uploadedDocPaths
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'POST' => $_POST,
            'FILES' => $_FILES
        ]
    ]);
}
