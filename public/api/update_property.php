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
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
    exit;
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
$status            = $_POST['status'] ?? 'available';
$existing_images   = $_POST['existing_images'] ?? [];
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

    // 🔹 Find agent ID if listing_type is 'sold_by'
    if ($listing_type === 'sold_by' && !empty($sold_by_email)) {
        $stmtAgent = $pdo->prepare("
            SELECT a.id, u.email 
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
            $status = 'sold'; // ✅ Automatically set to sold
        } else {
            throw new Exception("Selling agent not found.");
        }
    } else {
        // Ensure status is valid ENUM
        $allowed_status = ['pending','available','rejected','sold'];
        if (!in_array($status, $allowed_status)) {
            $status = 'pending';
        }
    }

    // 🔹 Update property details
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

    // 🔹 Handle images
    $stmtCurrent = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ?");
    $stmtCurrent->execute([$property_id]);
    $current_images = $stmtCurrent->fetchAll(PDO::FETCH_COLUMN);
    $stmtCurrent->closeCursor();

    foreach ($current_images as $img_path) {
        if (!in_array($img_path, $existing_images)) {
            $full_path = __DIR__ . '/../../' . $img_path;
            if (file_exists($full_path)) unlink($full_path);
            $stmtDel = $pdo->prepare("DELETE FROM property_images WHERE property_id = ? AND image_path = ?");
            $stmtDel->execute([$property_id, $img_path]);
        }
    }

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

    $pdo->commit();

    // ✅ Success notification
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Property updated successfully.'
    ];
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();

    // ❌ Error notification
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Failed to update property: ' . $e->getMessage()
    ];
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
    exit;
}
