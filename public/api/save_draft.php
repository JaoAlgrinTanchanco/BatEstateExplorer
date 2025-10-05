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
    $draft_id      = isset($_POST['id']) ? intval($_POST['id']) : null; // optional, for update
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $price         = isset($_POST['price']) ? floatval($_POST['price']) : null;
    $location      = trim($_POST['location'] ?? '');
    $bedrooms      = isset($_POST['bedrooms']) ? intval($_POST['bedrooms']) : null;
    $bathrooms     = isset($_POST['bathrooms']) ? intval($_POST['bathrooms']) : null;
    $lot_size      = isset($_POST['lot_size']) ? floatval($_POST['lot_size']) : null;
    $property_type = trim($_POST['property_type'] ?? '');

    // =========================
    // Handle image uploads (optional)
    // =========================
    $upload_dir = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/draft/';
    $db_path_prefix = 'storage/uploads/draft/';

    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $uploadedPaths = [];
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
                throw new Exception("Failed to move uploaded file: $name");
            }

            $uploadedPaths[] = $relativePath;
        }
    }

    $image_path = $uploadedPaths ? implode(',', $uploadedPaths) : null;

    // =========================
    // Insert or Update Draft
    // =========================
    if ($draft_id) {
        // Update existing draft
        $stmt = $pdo->prepare("
            UPDATE property_drafts
            SET title = ?, location = ?, price = ?, lot_size = ?, property_type = ?, bedrooms = ?, bathrooms = ?, description = ?, updated_at = NOW()
            " . ($image_path ? ", image_path = CONCAT(IFNULL(image_path, ''), ?, ',')" : "") . "
            WHERE id = ? AND user_id = ?
        ");

        $params = [$title, $location, $price, $lot_size, $property_type, $bedrooms, $bathrooms, $description];
        if ($image_path) $params[] = $image_path;
        $params[] = $draft_id;
        $params[] = $user_id;

        $stmt->execute($params);

        $message = "Draft updated successfully!";
    } else {
        // Insert new draft
        $stmt = $pdo->prepare("
            INSERT INTO property_drafts 
            (user_id, title, location, price, lot_size, property_type, bedrooms, bathrooms, description, image_path, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
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
            $image_path
        ]);

        $message = "Draft saved successfully!";
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'debug' => [
            'POST' => $_POST,
            'FILES' => $_FILES,
            'uploaded' => $uploadedPaths
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
