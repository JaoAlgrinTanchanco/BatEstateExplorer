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

// Handle POST request to add property
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get agent ID
        $agent_query = "SELECT id FROM agents WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $agent_query);
        mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
        mysqli_stmt_execute($stmt);
        $agent_result = mysqli_stmt_get_result($stmt);
        $agent = mysqli_fetch_assoc($agent_result);
        
        if (!$agent) {
            throw new Exception('Agent record not found');
        }
        
        $agent_id = $agent['id'];
        
        // Validate required fields
        $required_fields = ['title', 'description', 'location', 'property_type', 'price', 'bedrooms', 'bathrooms', 'sqm'];
        
        // Validate property_type values
        $allowed_property_types = ['Property', 'Lot'];
        if (!in_array($_POST['property_type'], $allowed_property_types)) {
            throw new Exception('Invalid property type. Only Property and Lot are allowed.');
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
        $features = isset($_POST['features']) ? mysqli_real_escape_string($conn, $_POST['features']) : '';
        
        // Handle file uploads
        $image_paths = [];
        $document_paths = [];
        
        // Create upload directories
        $uploads_dir = 'uploads/';
        $images_dir = $uploads_dir . 'property_images/';
        $documents_dir = $uploads_dir . 'property_documents/';
        
        if (!file_exists($images_dir)) {
            mkdir($images_dir, 0755, true);
        }
        if (!file_exists($documents_dir)) {
            mkdir($documents_dir, 0755, true);
        }
        
        // Process images
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $allowed_image_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['images']['type'][$i];
                    
                    if (!in_array($file_type, $allowed_image_types)) {
                        throw new Exception('Only JPG, PNG, and GIF images are allowed');
                    }
                    
                    $file_size = $_FILES['images']['size'][$i];
                    if ($file_size > 5 * 1024 * 1024) { // 5MB limit
                        throw new Exception('Image file size must be less than 5MB');
                    }
                    
                    $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $file_path = $images_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $file_path)) {
                        $image_paths[] = $file_path;
                    } else {
                        throw new Exception('Failed to upload image');
                    }
                }
            }
        }
        
        // Process documents
        if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
            $allowed_doc_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
            
            for ($i = 0; $i < count($_FILES['documents']['name']); $i++) {
                if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['documents']['type'][$i];
                    
                    if (!in_array($file_type, $allowed_doc_types)) {
                        throw new Exception('Only JPG, PNG, GIF, and PDF documents are allowed');
                    }
                    
                    $file_size = $_FILES['documents']['size'][$i];
                    if ($file_size > 10 * 1024 * 1024) { // 10MB limit
                        throw new Exception('Document file size must be less than 10MB');
                    }
                    
                    $file_extension = pathinfo($_FILES['documents']['name'][$i], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $file_path = $documents_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['documents']['tmp_name'][$i], $file_path)) {
                        $document_paths[] = $file_path;
                    } else {
                        throw new Exception('Failed to upload document');
                    }
                }
            }
        }
        
        // Insert property into database
        $query = "INSERT INTO properties (title, description, property_type, location, price, bedrooms, bathrooms, sqm, lot_size, agent_id, status, created_at, updated_at) 
                  VALUES ('$title', '$description', '$property_type', '$location', $price, $bedrooms, $bathrooms, $sqm, $lot_size, $agent_id, 'available', NOW(), NOW())";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception('Failed to save property: ' . mysqli_error($conn));
        }
        
        $property_id = mysqli_insert_id($conn);
        
        // Save image paths to property_images table
        if (!empty($image_paths)) {
            foreach ($image_paths as $image_path) {
                $image_query = "INSERT INTO property_images (property_id, image_path, created_at) VALUES (?, ?, NOW())";
                $image_stmt = mysqli_prepare($conn, $image_query);
                mysqli_stmt_bind_param($image_stmt, "is", $property_id, $image_path);
                mysqli_stmt_execute($image_stmt);
            }
        }
        
        // Save document paths to property_documents table
        if (!empty($document_paths)) {
            foreach ($document_paths as $document_path) {
                $doc_query = "INSERT INTO property_documents (property_id, document_path, created_at) VALUES (?, ?, NOW())";
                $doc_stmt = mysqli_prepare($conn, $doc_query);
                mysqli_stmt_bind_param($doc_stmt, "is", $property_id, $document_path);
                mysqli_stmt_execute($doc_stmt);
            }
        }
        
        // Return success response
        echo json_encode([
            'success' => true, 
            'message' => 'Property added successfully',
            'property_id' => $property_id,
            'images_count' => count($image_paths),
            'documents_count' => count($document_paths)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 