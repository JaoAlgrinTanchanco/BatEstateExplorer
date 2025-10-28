<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/pdo_database.php';
session_start();

if (!isset($_POST['property_id'])) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Invalid request.'
    ];
    redirectWithAgentType('my_listings');
}

$property_id     = intval($_POST['property_id']);
$title           = $_POST['title'] ?? '';
$description     = $_POST['description'] ?? '';
$property_type   = $_POST['property_type'] ?? '';
$location        = $_POST['location'] ?? '';
$price           = floatval($_POST['price'] ?? 0);
$bedrooms        = intval($_POST['bedrooms'] ?? 0);
$bathrooms       = intval($_POST['bathrooms'] ?? 0);
$sqm             = floatval($_POST['sqm'] ?? 0);
$lot_size        = floatval($_POST['lot_size'] ?? 0);
$existing_images = $_POST['existing_images'] ?? [];
$remove_images   = $_POST['remove_images'] ?? [];
$primary_image   = $_POST['primary_image'] ?? null;

try {
    $pdo->beginTransaction();

    // Fetch current property
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        throw new Exception("Property not found.");
    }

    // Preserve current status and agent relationship
    $status = $property['status'];
    $listed_by_agent_id = $property['listed_by_agent_id'];

    // --- Update property details (status not changed) ---
    $stmtUpdate = $pdo->prepare("
        UPDATE properties SET
            title = ?, 
            description = ?, 
            property_type = ?, 
            location = ?, 
            price = ?, 
            bedrooms = ?, 
            bathrooms = ?, 
            sqm = ?, 
            lot_size = ?, 
            listed_by_agent_id = ?, 
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmtUpdate->execute([
        $title,
        $description,
        $property_type,
        $location,
        $price,
        $bedrooms,
        $bathrooms,
        $sqm,
        $lot_size,
        $listed_by_agent_id,
        $property_id
    ]);

    // --- Handle image removals ---
    if (!empty($remove_images)) {
        foreach ($remove_images as $img_path) {
            $full_path = __DIR__ . '/../../' . $img_path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }

            $stmtDel = $pdo->prepare("DELETE FROM property_images WHERE property_id = ? AND image_path = ?");
            $stmtDel->execute([$property_id, $img_path]);
        }
    }

    // --- Upload new images ---
    if (isset($_FILES['new_images']) && is_array($_FILES['new_images']['tmp_name'])) {
        $upload_dir = 'C:\\xampp\\htdocs\\BatEstateExplorer\\storage\\uploads\\property_images\\';
        $file_count = min(count($_FILES['new_images']['tmp_name']), 10);

        for ($i = 0; $i < $file_count; $i++) {
            $tmp   = $_FILES['new_images']['tmp_name'][$i];
            $name  = $_FILES['new_images']['name'][$i];
            $error = $_FILES['new_images']['error'][$i];

            if ($error !== UPLOAD_ERR_OK || $tmp === '') continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) continue;

            $newFileName = uniqid() . '.' . $ext;
            $destination = $upload_dir . $newFileName;

            if (move_uploaded_file($tmp, $destination)) {
                $relativePath = 'storage/uploads/property_images/' . $newFileName;
                $stmtImg = $pdo->prepare("INSERT INTO property_images (property_id, image_path) VALUES (?, ?)");
                $stmtImg->execute([$property_id, $relativePath]);
            }
        }
    }

    // --- Update primary image ---
    if ($primary_image) {
        $stmtReset = $pdo->prepare("UPDATE property_images SET is_primary = 0 WHERE property_id = ?");
        $stmtReset->execute([$property_id]);

        $stmtPrimary = $pdo->prepare("UPDATE property_images SET is_primary = 1 WHERE property_id = ? AND image_path = ?");
        $stmtPrimary->execute([$property_id, $primary_image]);
    }

    $pdo->commit();

    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Property updated successfully.'
    ];
    redirectWithAgentType('my_listings');

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Failed to update property: ' . $e->getMessage()
    ];
    redirectWithAgentType('my_listings');
}

// ==========================
// Helper: Redirect by user_type
// ==========================
function redirectWithAgentType($tab = 'my_listings') {
    global $pdo;

    $userId = $_SESSION['user_id'] ?? null;
    $userType = 'direct_agent';

    if ($userId) {
        $stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['user_type'])) {
            $userType = $row['user_type'];
        }
    }

    switch ($userType) {
        case 'associate_agent':
            $view = 'associate_profile';
            break;
        case 'direct_agent':
        default:
            $view = 'direct_profile';
            break;
    }

    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view={$view}&tab={$tab}");
    exit;
}
