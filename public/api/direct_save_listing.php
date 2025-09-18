<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/pdo_database.php';

// 🔹 Ensure logged-in user
$user_data = get_logged_in_user($pdo);
if (!$user_data) {
    echo json_encode(['success' => false, 'error' => 'No logged-in user detected.']);
    exit;
}

// 🔹 Ensure only direct agents can access
if ($user_data['user_type'] !== 'direct_agent') {
    echo json_encode(['success' => false, 'error' => 'Access denied: only direct agents can save listings.']);
    exit;
}

// 🔹 Ensure direct agent has an agent_id
$stmt = $pdo->prepare("SELECT id FROM agents WHERE user_id = ?");
$stmt->execute([$user_data['id']]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

if ($agent) {
    $agent_id = $agent['id'];
} else {
    $stmtInsert = $pdo->prepare("INSERT INTO agents (user_id, created_at) VALUES (?, NOW())");
    $stmtInsert->execute([$user_data['id']]);
    $agent_id = $pdo->lastInsertId();
}

// 🔹 Collect form data safely
$title         = trim($_POST['title'] ?? '');
$description   = trim($_POST['description'] ?? '');
$price         = (float) ($_POST['price'] ?? 0);
$location      = trim($_POST['location'] ?? '');
$bedrooms      = (int) ($_POST['bedrooms'] ?? 0);
$bathrooms     = (int) ($_POST['bathrooms'] ?? 0);
$sqm           = (float) ($_POST['sqm'] ?? 0);
$lot_size      = (float) ($_POST['lot_size'] ?? 0);
$property_type = trim($_POST['property_type'] ?? '');

try {
    $pdo->beginTransaction();

    // 🔹 Insert property (default: pending)
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

    // 🔹 Handle image uploads (limit 10)
    $upload_dir = 'C:\\xampp\\htdocs\\BatEstateExplorer\\storage\\uploads\\property_images\\'; // absolute path
    $db_path_prefix = 'storage/uploads/property_images/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $uploadedImages = []; // 👈 collect debug info

    if (
        isset($_FILES['images']) &&
        is_array($_FILES['images']['tmp_name']) &&
        $_FILES['images']['tmp_name'][0] !== ''
    ) {
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
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) continue;

            $newFileName = uniqid('prop_', true) . '.' . $ext;
            $destination = $upload_dir . $newFileName;
            $relativePath = $db_path_prefix . $newFileName;

            if (move_uploaded_file($tmp, $destination)) {
                $isPrimary = ($i === 0) ? 1 : 0; // first image = primary
                $stmtImg->execute([$property_id, $relativePath, $isPrimary]);

                // add debug info
                $uploadedImages[] = [
                    'original' => $name,
                    'saved_as' => $newFileName,
                    'relative' => $relativePath,
                    'is_primary' => $isPrimary
                ];
            }
        }
    }

    $pdo->commit();

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
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Error saving property: ' . $e->getMessage(),
        'debug' => [
            'POST' => $_POST,
            'FILES' => $_FILES
        ]
    ]);
}
