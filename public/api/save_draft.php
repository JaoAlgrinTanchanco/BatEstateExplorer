<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/pdo_database.php';

try {
    $user_data = get_logged_in_user($pdo);
    if (!$user_data) throw new Exception("No logged in user.");
    
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $price         = floatval($_POST['price'] ?? 0);
    $location      = trim($_POST['location'] ?? '');
    $bedrooms      = intval($_POST['bedrooms'] ?? 0);
    $bathrooms     = intval($_POST['bathrooms'] ?? 0);
    $lot_size      = floatval($_POST['lot_size'] ?? 0);
    $property_type = trim($_POST['property_type'] ?? '');

    $pdo->beginTransaction();

    // ---------------------
    // Handle draft images
    // ---------------------
    $upload_dir = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/draft/';
    $db_path_prefix = 'storage/uploads/draft/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $savedImages = [];
    $firstImagePath = null;

    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            $name  = $_FILES['images']['name'][$i];
            $error = $_FILES['images']['error'][$i];
            if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif'])) continue;

            $newName = uniqid('draft_', true) . '.' . $ext;
            $dest = $upload_dir . $newName;

            if (!move_uploaded_file($tmp, $dest)) continue;

            $relativePath = $db_path_prefix . $newName;
            $savedImages[] = $relativePath;

            // Save the first image as thumbnail
            if ($i === 0) $firstImagePath = $relativePath;
        }
    }

    // ---------------------
    // Insert draft
    // ---------------------
    $stmt = $pdo->prepare("
        INSERT INTO property_drafts 
        (user_id, title, description, property_type, location, price, bedrooms, bathrooms, lot_size, images, image_path, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $user_data['id'],
        $title, $description, $property_type, $location, $price,
        $bedrooms, $bathrooms, $lot_size,
        json_encode($savedImages),
        $firstImagePath
    ]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Draft saved!', 'draft_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
