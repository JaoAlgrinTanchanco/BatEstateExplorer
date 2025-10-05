<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/pdo_database.php';

try {
    // -------------------------
    // Check logged-in user
    // -------------------------
    $user_data = get_logged_in_user($pdo);
    if (!$user_data) throw new Exception("No logged in user detected.");
    if (!in_array($user_data['user_type'], ['associate_agent', 'direct_agent'])) {
        throw new Exception("Access denied: only associates or direct agents can save listings.");
    }

    // -------------------------
    // Ensure agent exists
    // -------------------------
    $stmt = $pdo->prepare("SELECT id FROM agents WHERE user_id = ?");
    $stmt->execute([$user_data['id']]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    $agent_id = $agent ? $agent['id'] : null;

    if (!$agent_id) {
        $stmtInsert = $pdo->prepare("INSERT INTO agents (user_id, created_at) VALUES (?, NOW())");
        $stmtInsert->execute([$user_data['id']]);
        $agent_id = $pdo->lastInsertId();
    }

    // -------------------------
    // Collect form data
    // -------------------------
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $price         = floatval($_POST['price'] ?? 0);
    $location      = trim($_POST['location'] ?? '');
    $bedrooms      = intval($_POST['bedrooms'] ?? 0);
    $bathrooms     = intval($_POST['bathrooms'] ?? 0);
    $sqm           = floatval($_POST['sqm'] ?? 0);
    $lot_size      = floatval($_POST['lot_size'] ?? 0);
    $property_type = trim($_POST['property_type'] ?? '');
    $existingImages = $_POST['existing_images'] ?? [];

    $uploadedFilesExist = isset($_FILES['images']) && !empty($_FILES['images']['tmp_name']);

    if (!$uploadedFilesExist && empty($existingImages)) {
        throw new Exception("Please upload at least one property image.");
    }

    $pdo->beginTransaction();

    // -------------------------
    // Insert property
    // -------------------------
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

    // -------------------------
    // Insert existing draft images
    // -------------------------
    $stmtImg = $pdo->prepare("
        INSERT INTO property_images (property_id, image_path, is_primary, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $isFirst = true;
    foreach ($existingImages as $src) {
        $stmtImg->execute([$property_id, $src, $isFirst ? 1 : 0]);
        $isFirst = false;
    }

    // -------------------------
    // Handle newly uploaded files
    // -------------------------
    if ($uploadedFilesExist) {
        $upload_dir = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/property_images/';
        $db_path_prefix = 'storage/uploads/property_images/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_count = min(count($_FILES['images']['tmp_name']), 10);
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

            $isPrimary = empty($existingImages) && $i === 0 ? 1 : 0;
            $stmtImg->execute([$property_id, $relativePath, $isPrimary]);
        }
    }

    $pdo->commit();

    // -------------------------
    // Delete draft if draft_id is provided
    // -------------------------
    $draftId = intval($_POST['draft_id'] ?? 0);
    if ($draftId > 0) {
        $url = 'http://localhost/BatEstateExplorer/public/api/delete_draft.php';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['id' => $draftId]));
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) error_log("Draft deletion CURL error: $curlError");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Property submitted successfully! Awaiting admin approval.',
        'property_id' => $property_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
