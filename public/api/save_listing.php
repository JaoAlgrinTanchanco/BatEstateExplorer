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
    // Directories
    // -------------------------
    $property_dir = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/property_images/';
    $draft_dir    = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/draft/';
    $db_prefix    = 'storage/uploads/property_images/';
    if (!is_dir($property_dir)) mkdir($property_dir, 0777, true);

    // -------------------------
    // Check if publishing a draft
    // -------------------------
    $draftId = intval($_POST['draft_id'] ?? 0);
    $isDraft = $draftId > 0;
    $images = []; // store image paths for DB

    if ($isDraft) {
        // Fetch draft
        $stmt = $pdo->prepare("SELECT * FROM property_drafts WHERE id = ? AND user_id = ?");
        $stmt->execute([$draftId, $user_data['id']]);
        $draft = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$draft) throw new Exception("Draft not found");

        $title         = $draft['title'];
        $description   = $draft['description'];
        $price         = $draft['price'];
        $location      = $draft['location'];
        $bedrooms      = $draft['bedrooms'];
        $bathrooms     = $draft['bathrooms'];
        $sqm           = $draft['sqm'] ?? 0;
        $lot_size      = $draft['lot_size'];
        $property_type = $draft['property_type'];

        $draftImages = !empty($draft['image_path']) ? array_filter(explode(',', $draft['image_path'])) : [];

        $projectRoot = realpath(__DIR__ . '/../../');

        foreach ($draftImages as $idx => $imgPath) {
            $oldPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imgPath);
            if (!file_exists($oldPath)) continue;

            $ext = pathinfo($oldPath, PATHINFO_EXTENSION);
            $newName = uniqid('prop_', true) . '.' . $ext;
            $newPath = $property_dir . $newName;
            $dbPath  = $db_prefix . $newName;

            if (!rename($oldPath, $newPath)) throw new Exception("Failed to move draft image: $imgPath");
            $images[] = ['path' => $dbPath, 'is_primary' => $idx === 0 ? 1 : 0];

            // Extra safety: remove original if still exists
            if (file_exists($oldPath)) unlink($oldPath);
        }

        // Delete draft record from DB
        $stmtDel = $pdo->prepare("DELETE FROM property_drafts WHERE id = ? AND user_id = ?");
        $stmtDel->execute([$draftId, $user_data['id']]);
    } else {
        // -------------------------
        // Normal listing creation
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

        foreach ($existingImages as $idx => $imgPath) {
            $images[] = ['path' => $imgPath, 'is_primary' => $idx === 0 ? 1 : 0];
        }

        if (isset($_FILES['images']) && !empty($_FILES['images']['tmp_name'][0])) {
            $file_count = min(count($_FILES['images']['tmp_name']), 10);
            for ($i = 0; $i < $file_count; $i++) {
                $tmp   = $_FILES['images']['tmp_name'][$i];
                $name  = $_FILES['images']['name'][$i];
                $error = $_FILES['images']['error'][$i];
                if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif'])) continue;

                $newFile = uniqid('prop_', true) . '.' . $ext;
                $dest = $property_dir . $newFile;
                $rel  = $db_prefix . $newFile;

                if (!move_uploaded_file($tmp, $dest)) throw new Exception("Failed to upload image: $name");
                $images[] = ['path' => $rel, 'is_primary' => empty($images) && $i === 0 ? 1 : 0];
            }
        }

        if (empty($images)) throw new Exception("Please upload at least one property image.");
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
    // Insert images
    // -------------------------
    $stmtImg = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_primary, created_at) VALUES (?, ?, ?, NOW())");
    foreach ($images as $img) {
        $stmtImg->execute([$property_id, $img['path'], $img['is_primary']]);
    }

    $pdo->commit();

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
