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

$property_id       = intval($_POST['property_id']);
$title             = $_POST['title'] ?? '';
$description       = $_POST['description'] ?? '';
$property_type     = $_POST['property_type'] ?? '';
$location          = $_POST['location'] ?? '';
$price             = floatval($_POST['price'] ?? 0);
$bedrooms          = intval($_POST['bedrooms'] ?? 0);
$bathrooms         = intval($_POST['bathrooms'] ?? 0);
$sqm               = floatval($_POST['sqm'] ?? 0);
$lot_size          = floatval($_POST['lot_size'] ?? 0);
$existing_images   = $_POST['existing_images'] ?? [];
$remove_images     = $_POST['remove_images'] ?? [];
$primary_image     = $_POST['primary_image'] ?? null;
$listing_type      = $_POST['listing_type'] ?? 'owned';
$sold_by_email     = $_POST['sold_by_email'] ?? null;
$sold_by_agent_id  = null;

try {
    $pdo->beginTransaction();

    // 🔹 Fetch current property
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$property) {
        throw new Exception("Property not found.");
    }

    // ✅ Preserve current status unless overridden by backend
    $status = $property['status'];

    // 🔹 If listing marked as sold by another agent
    if ($listing_type === 'sold_by' && !empty($sold_by_email)) {
        $stmtAgent = $pdo->prepare("
            SELECT a.id 
            FROM agents a
            JOIN users u ON u.id = a.user_id
            WHERE u.email = ? 
              AND u.user_type = 'associate_agent'
            LIMIT 1
        ");
        $stmtAgent->execute([$sold_by_email]);
        $agent = $stmtAgent->fetch(PDO::FETCH_ASSOC);

        if ($agent) {
            $sold_by_agent_id = $agent['id'];
            $status = 'sold'; // ✅ Force to sold
        } else {
            throw new Exception("Selling agent not found.");
        }
    }

    // 🔹 Update property details (status not touched unless sold_by)
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
            status = ?, 
            sold_by_agent_id = ?, 
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
        $status,
        $sold_by_agent_id,
        $property_id
    ]);

    // 🔹 Handle removals
    if (!empty($remove_images)) {
        foreach ($remove_images as $img_path) {
            $full_path = __DIR__ . '/../../' . $img_path;
            if (file_exists($full_path)) unlink($full_path);

            $stmtDel = $pdo->prepare("DELETE FROM property_images WHERE property_id = ? AND image_path = ?");
            $stmtDel->execute([$property_id, $img_path]);
        }
    }

    // 🔹 Upload new images
    if (isset($_FILES['new_images']) && is_array($_FILES['new_images']['tmp_name'])) {
        $upload_dir = 'C:\\xampp\\htdocs\\BatEstateExplorer\\storage\\uploads\\property_images\\';
        $file_count = min(count($_FILES['new_images']['tmp_name']), 10);

        for ($i = 0; $i < $file_count; $i++) {
            $tmp   = $_FILES['new_images']['tmp_name'][$i];
            $name  = $_FILES['new_images']['name'][$i];
            $error = $_FILES['new_images']['error'][$i];

            if ($error !== UPLOAD_ERR_OK || $tmp === '') continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif'])) continue;

            $newFileName = uniqid() . '.' . $ext;
            $destination = $upload_dir . $newFileName;

            if (move_uploaded_file($tmp, $destination)) {
                $relativePath = 'storage/uploads/property_images/' . $newFileName;
                $stmtImg = $pdo->prepare("INSERT INTO property_images (property_id, image_path) VALUES (?, ?)");
                $stmtImg->execute([$property_id, $relativePath]);
            }
        }
    }

    // 🔹 Update primary image
    if ($primary_image) {
        $stmtReset = $pdo->prepare("UPDATE property_images SET is_primary = 0 WHERE property_id = ?");
        $stmtReset->execute([$property_id]);

        $stmtPrimary = $pdo->prepare("UPDATE property_images SET is_primary = 1 WHERE property_id = ? AND image_path = ?");
        $stmtPrimary->execute([$property_id, $primary_image]);
    }

    $pdo->commit();

    // ✅ Success notification
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Property updated successfully.'
    ];
    redirectWithAgentType('my_listings');

} catch (Exception $e) {
    $pdo->rollBack();

    // ❌ Error notification
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
    $userType = 'direct_agent'; // default

    if ($userId) {
        $stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['user_type'])) {
            $userType = $row['user_type'];
        }
    }

    // Map user_type → correct dashboard view
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
