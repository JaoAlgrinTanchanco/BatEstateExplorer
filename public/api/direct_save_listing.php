<?php
session_start();
require_once __DIR__ . '/../../config/pdo_database.php';

// Get logged-in user
$user_data = get_logged_in_user($pdo);
if (!$user_data) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'You must be logged in to add a property.'
    ];
    header("Location: ../../auth/login.php");
    exit;
}

// Ensure only direct agents can use this
if ($user_data['user_type'] !== 'direct_agent') {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Access denied: only direct agents can add listings.'
    ];
    header("Location: ../../index.php");
    exit;
}

// ✅ Get or create agent.id from agents table
$stmt = $pdo->prepare("SELECT id FROM agents WHERE user_id = ?");
$stmt->execute([$user_data['id']]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agent) {
    $stmtInsert = $pdo->prepare("INSERT INTO agents (user_id, created_at) VALUES (?, NOW())");
    $stmtInsert->execute([$user_data['id']]);
    $agent_id = $pdo->lastInsertId();
} else {
    $agent_id = $agent['id'];
}

// Collect form data
$title         = $_POST['title'] ?? '';
$description   = $_POST['description'] ?? '';
$price         = floatval($_POST['price'] ?? 0);
$location      = $_POST['location'] ?? '';
$bedrooms      = intval($_POST['bedrooms'] ?? 0);
$bathrooms     = intval($_POST['bathrooms'] ?? 0);
$sqm           = floatval($_POST['sqm'] ?? 0);
$lot_size      = floatval($_POST['lot_size'] ?? 0);
$property_type = $_POST['property_type'] ?? '';

try {
    $pdo->beginTransaction();

    // ✅ Insert property using agent_id
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

    // Handle uploads
    $upload_dir = 'C:\\xampp\\htdocs\\BatEstateExplorer\\storage\\uploads\\property_images\\';

    if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name']) && $_FILES['images']['tmp_name'][0] !== '') {
        $file_count = min(count($_FILES['images']['tmp_name']), 10);

        for ($i = 0; $i < $file_count; $i++) {
            $tmp   = $_FILES['images']['tmp_name'][$i];
            $name  = $_FILES['images']['name'][$i];
            $error = $_FILES['images']['error'][$i];

            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                continue;
            }

            $newFileName = uniqid() . '.' . $ext;
            $destination = $upload_dir . $newFileName;

            if (move_uploaded_file($tmp, $destination)) {
                $relativePath = 'storage/uploads/property_images/' . $newFileName;

                $stmtImg = $pdo->prepare("INSERT INTO property_images (property_id, image_path) VALUES (?, ?)");
                $stmtImg->execute([$property_id, $relativePath]);
            }
        }
    }

    $pdo->commit();

    // ✅ Notification for agent
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Your property has been submitted and is pending admin approval.'
    ];
    header("Location: ../agent_dashboard.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Something went wrong: ' . $e->getMessage()
    ];
    header("Location: ../agent_dashboard.php");
    exit;
}
