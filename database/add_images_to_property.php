<?php
// Suppress error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'config/database.php';

// Set JSON content type header immediately
header('Content-Type: application/json');

// Check if user is logged in and is a direct agent or associate agent
$is_logged_in = is_logged_in();
$current_user = null;
$is_agent = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_agent = ($current_user && ($current_user['user_type'] === 'direct_agent' || $current_user['user_type'] === 'associate_agent'));
}

// Redirect if not logged in or not an agent
if (!$is_logged_in || !$is_agent) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Handle POST request to add images to property
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
        
        if (!$property_id) {
            throw new Exception('Property ID is required');
        }
        
        // Verify the property belongs to this agent or their company
        $agent_query = "SELECT a.id, a.company_id FROM agents a WHERE a.user_id = ?";
        $stmt = mysqli_prepare($conn, $agent_query);
        mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
        mysqli_stmt_execute($stmt);
        $agent_result = mysqli_stmt_get_result($stmt);
        $agent = mysqli_fetch_assoc($agent_result);
        
        if (!$agent) {
            throw new Exception('Agent record not found');
        }
        
        $agent_id = $agent['id'];
        
        // Check if property belongs to this agent or their company
        if ($current_user['user_type'] === 'associate_agent') {
            // Associate agents can add images to properties from their company
            $property_check = "SELECT p.id, p.title FROM properties p 
                              LEFT JOIN agents a ON p.agent_id = a.id 
                              WHERE p.id = ? AND a.company_id = ?";
            $stmt = mysqli_prepare($conn, $property_check);
            mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent['company_id']);
        } else {
            // Direct agents can only add images to their own properties
            $property_check = "SELECT id, title FROM properties WHERE id = ? AND agent_id = ?";
            $stmt = mysqli_prepare($conn, $property_check);
            mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent_id);
        }
        
        mysqli_stmt_execute($stmt);
        $property_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($property_result) === 0) {
            throw new Exception('Property not found or access denied');
        }
        
        $property = mysqli_fetch_assoc($property_result);
        $property_title = $property['title'];
        
        // Handle new image uploads
        $new_image_paths = [];
        if (isset($_FILES['new_images']) && is_array($_FILES['new_images']['name'])) {
            $uploads_dir = 'storage/uploads/';
            $images_dir = $uploads_dir . 'property_images/';
            
            if (!file_exists($images_dir)) {
                mkdir($images_dir, 0755, true);
            }
            
            $allowed_image_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            
            for ($i = 0; $i < count($_FILES['new_images']['name']); $i++) {
                if ($_FILES['new_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['new_images']['type'][$i];
                    
                    if (!in_array($file_type, $allowed_image_types)) {
                        throw new Exception('Only JPG, PNG, and GIF images are allowed');
                    }
                    
                    $file_size = $_FILES['new_images']['size'][$i];
                    if ($file_size > 5 * 1024 * 1024) { // 5MB limit
                        throw new Exception('Image file size must be less than 5MB');
                    }
                    
                    $file_extension = pathinfo($_FILES['new_images']['name'][$i], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $file_path = $images_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['new_images']['tmp_name'][$i], $file_path)) {
                        $new_image_paths[] = $file_path;
                    } else {
                        throw new Exception('Failed to upload image');
                    }
                }
            }
        }
        
        if (empty($new_image_paths)) {
            throw new Exception('No images were uploaded');
        }
        
        // Save new images to property_images table
        foreach ($new_image_paths as $image_path) {
            $image_query = "INSERT INTO property_images (property_id, image_path, created_at) VALUES (?, ?, NOW())";
            $image_stmt = mysqli_prepare($conn, $image_query);
            mysqli_stmt_bind_param($image_stmt, "is", $property_id, $image_path);
            
            if (!mysqli_stmt_execute($image_stmt)) {
                throw new Exception('Failed to save image to database: ' . mysqli_error($conn));
            }
        }
        
        // Return success response
        echo json_encode([
            'success' => true, 
            'message' => count($new_image_paths) . ' image(s) added successfully to "' . $property_title . '"',
            'images_added' => count($new_image_paths)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 