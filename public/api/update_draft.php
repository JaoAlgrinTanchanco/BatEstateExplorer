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
    // Draft ID (required)
    // =========================
    $draft_id = isset($_POST['id']) ? intval($_POST['id']) : null;
    if (!$draft_id) throw new Exception("Draft ID is required for update.");

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
    // Fetch existing draft
    // =========================
    $stmt = $pdo->prepare("SELECT * FROM property_drafts WHERE id = ? AND user_id = ?");
    $stmt->execute([$draft_id, $user_id]);
    $draft = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$draft) throw new Exception("Draft not found.");

    // =========================
    // Existing files from DB
    // =========================
    $existing_images = $draft['image_path'] ? explode(',', $draft['image_path']) : [];
    $existing_docs   = $draft['property_document_path'] ? explode(',', $draft['property_document_path']) : [];

    // =========================
    // Removed files from form
    // =========================
    $remove_images = isset($_POST['remove_images']) ? (array)$_POST['remove_images'] : [];
    $remove_docs   = isset($_POST['remove_docs']) ? (array)$_POST['remove_docs'] : [];
    $remove_images = array_map(fn($i) => ltrim($i, '/'), $remove_images);
    $remove_docs   = array_map(fn($d) => ltrim($d, '/'), $remove_docs);

    foreach ($remove_images as $img) {
        $fullPath = __DIR__ . '/../../' . str_replace('/', DIRECTORY_SEPARATOR, $img);
        if (file_exists($fullPath)) @unlink($fullPath);
    }

    foreach ($remove_docs as $doc) {
        $fullPath = __DIR__ . '/../../' . str_replace('/', DIRECTORY_SEPARATOR, $doc);
        if (file_exists($fullPath)) @unlink($fullPath);
    }

    // =========================
    // New image uploads
    // =========================
    $uploadedImagePaths = [];
    if (!empty($_FILES['images']['name'][0])) {
        $count = count($_FILES['images']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $tmp  = $_FILES['images']['tmp_name'][$i];
            $name = basename($_FILES['images']['name'][$i]);
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif'])) continue;

            $newName = uniqid('draft_img_', true) . '.' . $ext;
            $dest = $upload_dir . $newName;
            $relativePath = $db_path_prefix . $newName;

            if (move_uploaded_file($tmp, $dest)) $uploadedImagePaths[] = $relativePath;
        }
    }

    // =========================
    // New document uploads
    // =========================
    $uploadedDocPaths = [];
    if (!empty($_FILES['property_document']['name'][0])) {
        $count = count($_FILES['property_document']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['property_document']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $tmp  = $_FILES['property_document']['tmp_name'][$i];
            $name = basename($_FILES['property_document']['name'][$i]);
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf','doc','docx','jpg','jpeg','png'])) continue;

            $newName = uniqid('draft_doc_', true) . '.' . $ext;
            $dest = $upload_dir . $newName;
            $relativePath = $db_path_prefix . $newName;

            if (move_uploaded_file($tmp, $dest)) $uploadedDocPaths[] = $relativePath;
        }
    }

    // =========================
    // Preserve existing files if not removed
    // =========================
    $existing_images_from_form = isset($_POST['existing_images']) ? (array)$_POST['existing_images'] : [];
    $existing_docs_from_form   = isset($_POST['existing_property_documents']) ? (array)$_POST['existing_property_documents'] : [];

    $finalImages = array_merge($existing_images_from_form, $uploadedImagePaths);
    $finalDocs   = array_merge($existing_docs_from_form, $uploadedDocPaths);

    $image_path = !empty($finalImages) ? implode(',', $finalImages) : null;
    $property_document_path = !empty($finalDocs) ? implode(',', $finalDocs) : null;

    // =========================
    // Update draft
    // =========================
    $stmt = $pdo->prepare("
        UPDATE property_drafts SET
            title = ?, location = ?, price = ?, lot_size = ?, property_type = ?,
            bedrooms = ?, bathrooms = ?, description = ?, image_path = ?, property_document_path = ?, updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([
        $title, $location, $price, $lot_size, $property_type,
        $bedrooms, $bathrooms, $description, $image_path, $property_document_path,
        $draft_id, $user_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Draft updated successfully!',
        'draft_id' => $draft_id,
        'debug' => [
            'final_images' => $finalImages,
            'final_docs' => $finalDocs,
            'uploaded_images' => $uploadedImagePaths,
            'uploaded_docs' => $uploadedDocPaths,
            'removed_images' => $remove_images,
            'removed_docs' => $remove_docs,
            'existing_images_from_form' => $existing_images_from_form,
            'existing_docs_from_form' => $existing_docs_from_form
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
