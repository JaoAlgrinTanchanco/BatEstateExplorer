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
    $property_dir  = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/property_images/';
    $document_dir  = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/documents/';
    $db_prefix_img = 'storage/uploads/property_images/';
    $db_prefix_doc = 'storage/uploads/documents/';

    if (!is_dir($property_dir)) mkdir($property_dir, 0777, true);
    if (!is_dir($document_dir)) mkdir($document_dir, 0777, true);

    // -------------------------
    // Draft check
    // -------------------------
    $draftId = intval($_POST['draft_id'] ?? 0);
    $isDraft = $draftId > 0;
    $images = [];
    $documentPath = null;

    if ($isDraft) {
        $stmt = $pdo->prepare("SELECT * FROM property_drafts WHERE id = ? AND user_id = ?");
        $stmt->execute([$draftId, $user_data['id']]);
        $draft = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$draft) throw new Exception("Draft not found");

        // Use draft data
        $title         = $draft['title'];
        $description   = $draft['description'];
        $price         = $draft['price'];
        $location      = $draft['location'];
        $bedrooms      = $draft['bedrooms'];
        $bathrooms     = $draft['bathrooms'];
        $lot_size      = $draft['lot_size'];
        $property_type = $draft['property_type'];

        // Move draft images
        $draftImages = !empty($draft['image_path']) ? array_filter(explode(',', $draft['image_path'])) : [];
        $projectRoot = realpath(__DIR__ . '/../../');
        foreach ($draftImages as $idx => $imgPath) {
            $oldPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imgPath);
            if (!file_exists($oldPath)) continue;
            $ext = pathinfo($oldPath, PATHINFO_EXTENSION);
            $newName = uniqid('prop_', true) . '.' . $ext;
            $newPath = $property_dir . $newName;
            $dbPath = $db_prefix_img . $newName;
            if (!rename($oldPath, $newPath)) throw new Exception("Failed to move draft image: $imgPath");
            $images[] = ['path' => $dbPath, 'is_primary' => $idx === 0 ? 1 : 0];
        }

        // Move draft document
        if (!empty($draft['property_document'])) {
            $oldDoc = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $draft['property_document']);
            if (file_exists($oldDoc)) {
                $ext = pathinfo($oldDoc, PATHINFO_EXTENSION);
                $newName = uniqid('doc_', true) . '.' . $ext;
                $newPath = $document_dir . $newName;
                $dbPath = $db_prefix_doc . $newName;
                if (!rename($oldDoc, $newPath)) throw new Exception("Failed to move draft document.");
                $documentPath = $dbPath;
            }
        }

        // Delete draft
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
        $lot_size      = floatval($_POST['lot_size'] ?? 0);
        $property_type = trim($_POST['property_type'] ?? '');
        $existingImages = $_POST['existing_images'] ?? [];

        foreach ($existingImages as $idx => $imgPath) {
            $images[] = ['path' => $imgPath, 'is_primary' => $idx === 0 ? 1 : 0];
        }

        // Upload images
        if (isset($_FILES['images']) && !empty($_FILES['images']['tmp_name'][0])) {
            $file_count = min(count($_FILES['images']['tmp_name']), 10);
            for ($i=0; $i<$file_count; $i++) {
                $tmp = $_FILES['images']['tmp_name'][$i];
                $name = $_FILES['images']['name'][$i];
                $error = $_FILES['images']['error'][$i];
                if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext,['jpg','jpeg','png','gif'])) continue;
                $newFile = uniqid('prop_', true) . '.' . $ext;
                $dest = $property_dir . $newFile;
                $rel = $db_prefix_img . $newFile;
                if (!move_uploaded_file($tmp, $dest)) throw new Exception("Failed to upload image: $name");
                $images[] = ['path'=>$rel,'is_primary'=>empty($images)&&$i===0?1:0];
            }
        }

        if (empty($images)) throw new Exception("Please upload at least one property image.");

        // Upload property document
        if (isset($_FILES['property_document']) && !empty($_FILES['property_document']['tmp_name'])) {
            $doc = $_FILES['property_document'];
            $ext = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','doc','docx','jpg','jpeg','png'];
            if (in_array($ext,$allowed)) {
                $fileName = uniqid('doc_', true) . '.' . $ext;
                $dest = $document_dir . $fileName;
                if (move_uploaded_file($doc['tmp_name'],$dest)) {
                    $documentPath = $db_prefix_doc . $fileName;
                }
            }
        }
    }

    // -------------------------
    // Insert property
    // -------------------------
    $stmt = $pdo->prepare("
        INSERT INTO properties
        (title, description, property_type, location, price, bedrooms, bathrooms, lot_size, agent_id, status, property_document, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
    ");
    $stmt->execute([
        $title, $description, $property_type, $location, $price,
        $bedrooms, $bathrooms, $lot_size, $agent_id, $documentPath
    ]);
    $property_id = $pdo->lastInsertId();

    // -------------------------
    // Insert images
    // -------------------------
    if (!empty($images)) {
        $stmtImg = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_primary, created_at) VALUES (?, ?, ?, NOW())");
        foreach ($images as $img) {
            $stmtImg->execute([$property_id, $img['path'], $img['is_primary']]);
        }
    }

    echo json_encode([
        'success'=>true,
        'message'=>'Property submitted successfully! Awaiting admin approval.',
        'property_id'=>$property_id
    ]);

} catch(Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
