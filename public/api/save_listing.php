<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/pdo_database.php';

// --- Immediately decode user session ---
$user_data = get_logged_in_user($pdo);
if (!$user_data) {
    echo json_encode(['success'=>false,'error'=>"No logged in user detected."]);
    exit;
}

// RELEASE SESSION LOCK so other AJAX calls can run in parallel
session_write_close();

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
    // -------------------------
    // Check logged-in user & Agent check (Logic retained)
    // -------------------------
    $user_data = get_logged_in_user($pdo);
    if (!$user_data) throw new Exception("No logged in user detected.");
    if (!in_array($user_data['user_type'], ['associate_agent', 'direct_agent'])) {
        throw new Exception("Access denied: only associates or direct agents can save listings.");
    }

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
    $property_dir    = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/property_images/';
    $document_dir    = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/property_documents/'; 
    $db_prefix_img   = 'storage/uploads/property_images/';
    $db_prefix_doc   = 'storage/uploads/property_documents/'; 

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
    $company_prop_id  = null;
    $images        = [];
    $documents_to_insert = []; 
    $projectRoot = realpath(__DIR__ . '/../../');

    // -------------------------
    // Draft check & Data Assignment
    // -------------------------
    $draftId = intval(get_post_data('draft_id', 0));
    $isDraft = $draftId > 0;

    if ($isDraft) {
        // --- Draft Loading Logic (Retained and assumed correct) ---
        $stmt = $pdo->prepare("SELECT * FROM property_drafts WHERE id = ? AND user_id = ?");
        $stmt->execute([$draftId, $user_data['id']]);
        $draft = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$draft) throw new Exception("Draft not found");

        // Use draft data
        $title = $draft['title']; $description = $draft['description']; $price = $draft['price'];
        $location = $draft['location']; $bedrooms = $draft['bedrooms']; $bathrooms = $draft['bathrooms'];
        $lot_size = $draft['lot_size']; $property_type = $draft['property_type'];
        $company_prop_id = $draft['company_prop_id'] ?? null; // ensure it exists

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

        // Move draft document(s)
        if (!empty($draft['property_document'])) {
            $draftDocs = array_filter(explode(',', $draft['property_document'])); 
            foreach($draftDocs as $docPath) {
                $oldDoc = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $docPath);
                if (file_exists($oldDoc)) {
                    $ext = pathinfo($oldDoc, PATHINFO_EXTENSION);
                    $newName = uniqid('doc_', true) . '.' . $ext;
                    $newPath = $document_dir . $newName; 
                    $dbPath = $db_prefix_doc . $newName; 
                    if (!rename($oldDoc, $newPath)) throw new Exception("Failed to move draft document.");
                    $documents_to_insert[] = ['path' => $dbPath, 'name' => basename($docPath), 'type' => $ext];
                }
            }
        }
        $stmtDel = $pdo->prepare("DELETE FROM property_drafts WHERE id = ? AND user_id = ?");
        $stmtDel->execute([$draftId, $user_data['id']]);

    } else {
        // -------------------------
        // Normal listing creation
        // -------------------------
        $title = get_post_data('title'); $description = get_post_data('description');
        $price = floatval(get_post_data('price', 0)); $location = get_post_data('location');
        $bedrooms = intval(get_post_data('bedrooms', 0)); $bathrooms = intval(get_post_data('bathrooms', 0));
        $lot_size = floatval(get_post_data('lot_size', 0)); $property_type = get_post_data('property_type');
        $company_prop_id = get_post_data('company_listing_id') ?: null; // store NULL if empty

        
        // 1. Existing/Preview Images (Handles images already uploaded by JS preview)
        $existingImages = get_post_array('existing_images'); 
        foreach ($existingImages as $imgPath) {
            if (is_string($imgPath) && !empty($imgPath)) {
                // Set primary if $images array is currently empty
                $is_primary = empty($images) ? 1 : 0;
                $images[] = ['path' => $imgPath, 'is_primary' => $is_primary];
            }
        }

        // 2. Upload New Images (CRITICAL REVISION FOR EMPTY SLOTS)
        if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
            // Count array elements to loop through, ignoring the PHP limit (min) is fine here
            $file_count = count($_FILES['images']['tmp_name']); 
            for ($i=0; $i<$file_count; $i++) {
                $error = $_FILES['images']['error'][$i];
                
                // IMPORTANT FIX: Skip if error is UPLOAD_ERR_NO_FILE (4) or UPLOAD_ERR_INI_SIZE (1)
                if ($error === UPLOAD_ERR_NO_FILE || $error !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['images']['tmp_name'][$i])) {
                    // Log other upload errors for debugging, but continue
                    if ($error !== UPLOAD_ERR_NO_FILE && $error !== UPLOAD_ERR_OK) {
                        // Optionally throw an error here for other critical upload failures
                        // throw new Exception("Image upload failed with error code: $error");
                    }
                    continue; 
                }
                
                $tmp = $_FILES['images']['tmp_name'][$i];
                $name = $_FILES['images']['name'][$i];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                if (!in_array($ext,['jpg','jpeg','png','gif'])) continue;
                
                $newFile = uniqid('prop_', true) . '.' . $ext;
                $dest = $property_dir . $newFile;
                $rel = $db_prefix_img . $newFile;
                
                if (!move_uploaded_file($tmp, $dest)) throw new Exception("Failed to upload image: $name");
                
                // Set primary if $images array is currently empty (Handles empty slot at index 0)
                $is_primary = empty($images) ? 1 : 0; 
                $images[] = ['path'=>$rel,'is_primary'=>$is_primary];
            }
        }

        // 3. Upload property documents (Logic retained)
        if (isset($_FILES['property_document']) && !empty($_FILES['property_document']['tmp_name'][0])) {
            $doc_count = count($_FILES['property_document']['tmp_name']);
            $allowed = ['pdf','doc','docx','jpg','jpeg','png'];

            for ($i=0; $i<$doc_count; $i++) {
                $error = $_FILES['property_document']['error'][$i];
                if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['property_document']['tmp_name'][$i])) continue;

                $tmp = $_FILES['property_document']['tmp_name'][$i];
                $name = $_FILES['property_document']['name'][$i];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed)) {
                    $fileName = uniqid('doc_', true) . '.' . $ext;
                    $dest = $document_dir . $fileName; 
                    $dbPath = $db_prefix_doc . $fileName;

                    if (move_uploaded_file($tmp, $dest)) {
                        $documents_to_insert[] = ['path' => $dbPath, 'name' => $name, 'type' => $ext];
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
    
    // The previous issue should now be resolved by correctly handling file upload index 0 error (4)
    if (empty($images)) throw new Exception("Please upload at least one property image."); 


    // -------------------------
    // Insert property (Final logic to save to DB)
    // -------------------------
    $pdo->beginTransaction(); 

    // INSERT INTO properties 
    $stmt = $pdo->prepare("
        INSERT INTO properties
        (title, description, property_type, location, price, bedrooms, bathrooms, lot_size, agent_id, listed_by_agent_id, company_prop_id, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
    ");

    $stmt->execute([
        $title,
        $description,
        $property_type,
        $location,
        $price,
        $bedrooms,
        $bathrooms,
        $lot_size,
        $agent_id,
        $agent_id,
        $company_prop_id
    ]);

    $property_id = $pdo->lastInsertId();

    // Insert images into property_images
    if (!empty($images)) {
        $stmtImg = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_primary, created_at) VALUES (?, ?, ?, NOW())");
        foreach ($images as $img) {
            $stmtImg->execute([$property_id, $img['path'], $img['is_primary']]);
        }
    }

    // Insert documents into property_documents
    if (!empty($documents_to_insert)) {
        $stmtDoc = $pdo->prepare("
            INSERT INTO property_documents (property_id, document_path, document_type, document_name, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        foreach ($documents_to_insert as $doc) {
            $stmtDoc->execute([
                $property_id, 
                $doc['path'], 
                $doc['type'], 
                $doc['name']
            ]);
        }
    }

    // -------------------------
    // Apply Tier Logic (Featured & Duration)
    // -------------------------
    $tier_plan = ucfirst(strtolower(get_post_data('tier_plan', 'Basic'))); // basic, standard, premium, platinum
    $tierDurations = ['Basic'=>30,'Standard'=>45,'Premium'=>60,'Platinum'=>90];
    $tierFeatured  = ['Basic'=>0,'Standard'=>0,'Premium'=>1,'Platinum'=>1];

    $plan_duration = $tierDurations[$tier_plan] ?? 30;
    $is_featured   = $tierFeatured[$tier_plan] ?? 0;
    $featured_until = date('Y-m-d H:i:s', strtotime("+$plan_duration days"));

    // -------------------------
    // Update property with tier & company info
    // -------------------------
    $stmtTier = $pdo->prepare("
        UPDATE properties 
        SET 
            is_featured = ?, 
            featured_until = ?, 
            tier_plan = ?, 
            plan_duration = ?, 
            company_prop_id = ?
        WHERE id = ?
    ");
    $stmtTier->execute([
        $is_featured,
        $featured_until,
        $tier_plan,
        $plan_duration,
        $company_prop_id,
        $property_id
    ]);

    $pdo->commit(); 

    // -------------------------
    // Return updated info in response
    // -------------------------
    echo json_encode([
        'success' => true,
        'message' => 'Property submitted successfully! Awaiting admin approval.',
        'property_id' => $property_id,
        'tier_plan' => $tier_plan,
        'plan_duration' => $plan_duration,
        'is_featured' => $is_featured,
        'featured_until' => $featured_until,
        'company_prop_id' => $company_prop_id
    ]); 

} catch(Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack(); 
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}