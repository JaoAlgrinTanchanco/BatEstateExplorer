<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // hide errors from frontend
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/pdo_database.php';
session_start();

try {
    // =========================
    // Check logged-in user
    // =========================
    $user_data = get_logged_in_user($pdo);
    if (!$user_data) throw new Exception("No logged in user detected.");
    if ($user_data['user_type'] !== 'associate_agent') throw new Exception("Access denied: only associates can save listings.");

    // =========================
    // Ensure agent record exists
    // =========================
    $stmt = $pdo->prepare("SELECT id FROM agents WHERE user_id = ?");
    $stmt->execute([$user_data['id']]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    $agent_id = $agent ? $agent['id'] : null;

    if (!$agent_id) {
        $stmtInsert = $pdo->prepare("INSERT INTO agents (user_id, created_at) VALUES (?, NOW())");
        $stmtInsert->execute([$user_data['id']]);
        $agent_id = $pdo->lastInsertId();
    }

    // =========================
    // Collect form data
    // =========================
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $price         = floatval($_POST['price'] ?? 0);
    $location      = trim($_POST['location'] ?? '');
    $bedrooms      = intval($_POST['bedrooms'] ?? 0);
    $bathrooms     = intval($_POST['bathrooms'] ?? 0);
    $sqm           = floatval($_POST['sqm'] ?? 0);
    $lot_size      = floatval($_POST['lot_size'] ?? 0);
    $property_type = trim($_POST['property_type'] ?? '');

    // =========================
    // Debug: log incoming POST & FILES
    // =========================
    error_log("=== POST DATA ===\n" . print_r($_POST, true));
    error_log("=== FILES DATA ===\n" . print_r($_FILES, true));

    // =========================
    // Check at least one image is uploaded
    // =========================
    if (!isset($_FILES['images']) || empty($_FILES['images']['tmp_name'])) {
        echo json_encode([
            'success' => false,
            'error'   => 'Please upload at least one property image.',
            'debug'   => ['POST' => $_POST, 'FILES' => $_FILES]
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // =========================
    // Insert property
    // =========================
    $stmt = $pdo->prepare("
        INSERT INTO properties
        (title, description, property_type, location, price, bedrooms, bathrooms, sqm, lot_size, agent_id, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $title, $description, $property_type, $location, $price,
        $bedrooms, $bathrooms, $sqm, $lot_size, $agent_id
    ]);
    $property_id = $pdo->lastInsertId();

    // =========================
    // Handle image uploads (limit 10)
    // =========================
    $upload_dir = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/property_images/';
    $db_path_prefix = 'storage/uploads/property_images/';

    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $uploadedImages = [];
    $file_count = min(count($_FILES['images']['tmp_name']), 10);
    $stmtImg = $pdo->prepare("
        INSERT INTO property_images (property_id, image_path, is_primary, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    for ($i = 0; $i < $file_count; $i++) {
        $tmp   = $_FILES['images']['tmp_name'][$i];
        $name  = $_FILES['images']['name'][$i];
        $error = $_FILES['images']['error'][$i];

        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif'])) continue;

        $newFileName = uniqid('prop_', true) . '.' . $ext;
        $destination = $upload_dir . $newFileName;
        $relativePath = $db_path_prefix . $newFileName;

        if (!move_uploaded_file($tmp, $destination)) {
            throw new Exception("Failed to move uploaded file: $name");
        }

        $isPrimary = ($i === 0) ? 1 : 0;
        $stmtImg->execute([$property_id, $relativePath, $isPrimary]);

        $uploadedImages[] = [
            'original'   => $name,
            'saved_as'   => $newFileName,
            'relative'   => $relativePath,
            'is_primary' => $isPrimary
        ];
    }

    $pdo->commit();

    // =========================
    // Success response
    // =========================
    echo json_encode([
        'success' => true,
        'message' => 'Property submitted successfully! Awaiting admin approval.',
        'property_id' => $property_id,
        'debug' => [
            'POST' => $_POST,
            'FILES' => $_FILES,
            'uploadedImages' => $uploadedImages
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Error saving property: ' . $e->getMessage(),
        'debug' => [
            'POST' => $_POST,
            'FILES' => $_FILES
        ]
    ]);
}
