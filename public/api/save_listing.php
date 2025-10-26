<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/pdo_database.php';

// --- Function to retrieve data from $_POST or return a default ---
function get_post_data($key, $default = '') {
    return trim($_POST[$key] ?? $default);
}
// --- Function to retrieve array data from $_POST ---
function get_post_array($key) {
    // Ensure we get an array, even if the key is missing or not array-formatted
    return is_array($_POST[$key] ?? null) ? $_POST[$key] : [];
}

try {
    // ... (User check and Agent check remain the same) ...

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
    // Initialize data variables
    // -------------------------
    $title         = '';
    $description   = '';
    $price         = 0.0;
    $location      = '';
    $bedrooms      = 0;
    $bathrooms     = 0;
    $lot_size      = 0.0;
    $property_type = '';
    $images        = [];
    $documentPath  = null; 

    // -------------------------
    // Draft check & Data Assignment
    // -------------------------
    $draftId = intval(get_post_data('draft_id', 0));
    $isDraft = $draftId > 0;
    $projectRoot = realpath(__DIR__ . '/../../');

    if ($isDraft) {
        // ... (Draft loading and file moving logic remains the same and is correct) ...
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
        $title         = get_post_data('title');
        $description   = get_post_data('description');
        $price         = floatval(get_post_data('price', 0));
        $location      = get_post_data('location');
        $bedrooms      = intval(get_post_data('bedrooms', 0));
        $bathrooms     = intval(get_post_data('bathrooms', 0));
        $lot_size      = floatval(get_post_data('lot_size', 0));
        $property_type = get_post_data('property_type');
        
        // --- CRITICAL FIX: Ensure 'existing_images' from the preview are read into $images
        // The frontend MUST send existing file paths in this POST array field.
        $existingImages = get_post_array('existing_images'); 

        // Populate the $images array with existing paths first
        foreach ($existingImages as $idx => $imgPath) {
            // Note: If the form is not a draft, these are likely temporary/preview images
            // that were uploaded previously and whose paths are being retained by the form.
            $images[] = ['path' => $imgPath, 'is_primary' => $idx === 0 ? 1 : 0];
        }

        // Upload new images
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
                $is_primary = empty($images) && $i === 0 ? 1 : 0;
                $images[] = ['path'=>$rel,'is_primary'=>$is_primary];
            }
        }

        // CORRECTED: Upload property document (Handling multiple files)
        if (isset($_FILES['property_document']) && !empty($_FILES['property_document']['tmp_name'][0])) {
            $doc_count = count($_FILES['property_document']['tmp_name']);
            $allowed = ['pdf','doc','docx','jpg','jpeg','png'];

            for ($i=0; $i<$doc_count; $i++) {
                $tmp = $_FILES['property_document']['tmp_name'][$i];
                $name = $_FILES['property_document']['name'][$i];
                $error = $_FILES['property_document']['error'][$i];

                if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed)) {
                    $fileName = uniqid('doc_', true) . '.' . $ext;
                    $dest = $document_dir . $fileName;
                    if (move_uploaded_file($tmp, $dest)) {
                        if ($documentPath === null) {
                            $documentPath = $db_prefix_doc . $fileName;
                        }
                    }
                }
            }
        }
    }
    
    // -------------------------
    // CRITICAL VALIDATION (Server-Side)
    // -------------------------
    if (empty($title)) throw new Exception("Title is required.");
    if (empty($description)) throw new Exception("Description is required.");
    if (empty($property_type)) throw new Exception("Property type is required.");
    if (empty($location)) throw new Exception("Location is required.");
    if ($price <= 0) throw new Exception("Price must be greater than zero.");
    // This validation check should now correctly count images from 'existing_images' and new uploads.
    if (empty($images)) throw new Exception("Please upload at least one property image."); 


    // -------------------------
    // Insert property (Updated with property_document)
    // -------------------------
    $pdo->beginTransaction(); // Start transaction for atomicity

    $stmt = $pdo->prepare("
        INSERT INTO properties
        (title, description, property_type, location, price, bedrooms, bathrooms, lot_size, agent_id, status, property_document, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
    ");
    
    // Execute the statement, including $documentPath as the last bound parameter
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
    
    $pdo->commit(); // Commit transaction on success

    echo json_encode([
        'success'=>true,
        'message'=>'Property submitted successfully! Awaiting admin approval.',
        'property_id'=>$property_id
    ]);

} catch(Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack(); // Rollback on failure
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}