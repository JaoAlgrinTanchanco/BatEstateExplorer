<?php
// Suppress error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'config/database.php';

// Set JSON content type header immediately
header('Content-Type: application/json');

// Check if user is logged in and is a direct agent
$is_logged_in = is_logged_in();
$current_user = null;
$is_direct_agent = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_direct_agent = ($current_user && $current_user['user_type'] === 'direct_agent');
}

// Redirect if not logged in or not a direct agent
if (!$is_logged_in || !$is_direct_agent) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Handle POST request to update property
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
        
        if (!$property_id) {
            throw new Exception('Property ID is required');
        }
        
        // Verify the property belongs to this agent
        $agent_query = "SELECT a.id FROM agents a WHERE a.user_id = ?";
        $stmt = mysqli_prepare($conn, $agent_query);
        mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
        mysqli_stmt_execute($stmt);
        $agent_result = mysqli_stmt_get_result($stmt);
        $agent = mysqli_fetch_assoc($agent_result);
        
        if (!$agent) {
            throw new Exception('Agent record not found');
        }
        
        $agent_id = $agent['id'];
        
        // Check if property belongs to this agent
        $property_check = "SELECT id FROM properties WHERE id = ? AND agent_id = ?";
        $stmt = mysqli_prepare($conn, $property_check);
        mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent_id);
        mysqli_stmt_execute($stmt);
        $property_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($property_result) === 0) {
            throw new Exception('Property not found or access denied');
        }
        
        // Validate required fields
        $required_fields = ['title', 'description', 'location', 'property_type', 'price', 'bedrooms', 'bathrooms', 'sqm'];
        
        // Validate property_type values
        $allowed_property_types = ['Property', 'Lot'];
        if (!in_array($_POST['property_type'], $allowed_property_types)) {
            throw new Exception('Invalid property type. Only Property and Lot are allowed.');
        }
        
        // Validate status values
        $allowed_statuses = ['available', 'sold'];
        if (!in_array($_POST['status'], $allowed_statuses)) {
            throw new Exception('Invalid status. Only Available and Sold are allowed.');
        }
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Sanitize input
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $property_type = mysqli_real_escape_string($conn, $_POST['property_type']);
        $price = (float)$_POST['price'];
        $bedrooms = (int)$_POST['bedrooms'];
        $bathrooms = (int)$_POST['bathrooms'];
        $sqm = (float)$_POST['sqm'];
        $lot_size = isset($_POST['lot_size']) ? (float)$_POST['lot_size'] : 0;
        $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'available';
        $features = isset($_POST['features']) ? mysqli_real_escape_string($conn, $_POST['features']) : '';
        
        // Update property in database
        $query = "UPDATE properties SET 
                  title = '$title', description = '$description', property_type = '$property_type', location = '$location', 
                  price = $price, bedrooms = $bedrooms, bathrooms = $bathrooms, sqm = $sqm, lot_size = $lot_size, 
                  status = '$status', updated_at = NOW() 
                  WHERE id = $property_id AND agent_id = $agent_id";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception('Failed to update property: ' . mysqli_error($conn));
        }
        
        // Handle new image uploads
        $new_image_paths = [];
        if (isset($_FILES['new_images']) && is_array($_FILES['new_images']['name'])) {
            $uploads_dir = 'uploads/';
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
                        throw new Exception('Failed to upload new image');
                    }
                }
            }
        }
        
        // Handle new document uploads
        $new_document_paths = [];
        if (isset($_FILES['new_documents']) && is_array($_FILES['new_documents']['name'])) {
            $uploads_dir = 'uploads/';
            $documents_dir = $uploads_dir . 'property_documents/';
            
            if (!file_exists($documents_dir)) {
                mkdir($documents_dir, 0755, true);
            }
            
            $allowed_doc_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
            
            for ($i = 0; $i < count($_FILES['new_documents']['name']); $i++) {
                if ($_FILES['new_documents']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['new_documents']['type'][$i];
                    
                    if (!in_array($file_type, $allowed_doc_types)) {
                        throw new Exception('Only JPG, PNG, GIF, and PDF documents are allowed');
                    }
                    
                    $file_size = $_FILES['new_documents']['size'][$i];
                    if ($file_size > 10 * 1024 * 1024) { // 10MB limit
                        throw new Exception('Document file size must be less than 10MB');
                    }
                    
                    $file_extension = pathinfo($_FILES['new_documents']['name'][$i], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $file_path = $documents_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['new_documents']['tmp_name'][$i], $file_path)) {
                        $new_document_paths[] = $file_path;
                    } else {
                        throw new Exception('Failed to upload new document');
                    }
                }
            }
        }
        
        // Save new images to property_images table
        if (!empty($new_image_paths)) {
            foreach ($new_image_paths as $image_path) {
                $image_query = "INSERT INTO property_images (property_id, image_path, created_at) VALUES (?, ?, NOW())";
                $image_stmt = mysqli_prepare($conn, $image_query);
                mysqli_stmt_bind_param($image_stmt, "is", $property_id, $image_path);
                mysqli_stmt_execute($image_stmt);
            }
        }
        
        // Save new documents to property_documents table
        if (!empty($new_document_paths)) {
            foreach ($new_document_paths as $document_path) {
                $doc_query = "INSERT INTO property_documents (property_id, document_path, created_at) VALUES (?, ?, NOW())";
                $doc_stmt = mysqli_prepare($conn, $doc_query);
                mysqli_stmt_bind_param($doc_stmt, "is", $property_id, $document_path);
                mysqli_stmt_execute($doc_stmt);
            }
        }
        
        // Handle image deletions
        if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $image_id) {
                // Get image path before deletion
                $get_image = "SELECT image_path FROM property_images WHERE id = ? AND property_id = ?";
                $stmt = mysqli_prepare($conn, $get_image);
                mysqli_stmt_bind_param($stmt, "ii", $image_id, $property_id);
                mysqli_stmt_execute($stmt);
                $image_result = mysqli_stmt_get_result($stmt);
                $image_data = mysqli_fetch_assoc($image_result);
                
                if ($image_data) {
                    // Delete file from server
                    if (file_exists($image_data['image_path'])) {
                        unlink($image_data['image_path']);
                    }
                    
                    // Delete from database
                    $delete_image = "DELETE FROM property_images WHERE id = ? AND property_id = ?";
                    $stmt = mysqli_prepare($conn, $delete_image);
                    mysqli_stmt_bind_param($stmt, "ii", $image_id, $property_id);
                    mysqli_stmt_execute($stmt);
                }
            }
        }
        
        // Handle document deletions
        if (isset($_POST['delete_documents']) && is_array($_POST['delete_documents'])) {
            foreach ($_POST['delete_documents'] as $doc_id) {
                // Get document path before deletion
                $get_doc = "SELECT document_path FROM property_documents WHERE id = ? AND property_id = ?";
                $stmt = mysqli_prepare($conn, $get_doc);
                mysqli_stmt_bind_param($stmt, "ii", $doc_id, $property_id);
                mysqli_stmt_execute($stmt);
                $doc_result = mysqli_stmt_get_result($stmt);
                $doc_data = mysqli_fetch_assoc($doc_result);
                
                if ($doc_data) {
                    // Delete file from server
                    if (file_exists($doc_data['document_path'])) {
                        unlink($doc_data['document_path']);
                    }
                    
                    // Delete from database
                    $delete_doc = "DELETE FROM property_documents WHERE id = ? AND property_id = ?";
                    $stmt = mysqli_prepare($conn, $delete_doc);
                    mysqli_stmt_bind_param($stmt, "ii", $doc_id, $property_id);
                    mysqli_stmt_execute($stmt);
                }
            }
        }
        
        // Return success response
        echo json_encode([
            'success' => true, 
            'message' => 'Property updated successfully',
            'property_id' => $property_id,
            'new_images_count' => count($new_image_paths),
            'new_documents_count' => count($new_document_paths)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 