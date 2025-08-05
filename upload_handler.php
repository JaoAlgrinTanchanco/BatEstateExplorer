<?php
session_start();
require_once 'config/database.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = 'backend/uploads/';
    $documents_dir = $upload_dir . 'documents/';
    $images_dir = $upload_dir . 'images/';
    
    // Create directories if they don't exist
    if (!file_exists($documents_dir)) {
        mkdir($documents_dir, 0755, true);
    }
    if (!file_exists($images_dir)) {
        mkdir($images_dir, 0755, true);
    }
    
    $response = [];
    $uploaded_files = [];
    
    // Process each uploaded file
    foreach ($_FILES as $field_name => $file_data) {
        if ($file_data['error'] === UPLOAD_ERR_OK) {
            $file_name = $file_data['name'];
            $file_tmp = $file_data['tmp_name'];
            $file_size = $file_data['size'];
            $file_type = $file_data['type'];
            
            // Validate file type
            $allowed_types = [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                'application/pdf', 'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            if (!in_array($file_type, $allowed_types)) {
                $response['errors'][] = "Invalid file type for $field_name. Allowed: JPG, PNG, PDF, DOC, DOCX";
                continue;
            }
            
            // Validate file size (5MB max)
            if ($file_size > 5 * 1024 * 1024) {
                $response['errors'][] = "File too large for $field_name. Maximum size: 5MB";
                continue;
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
            
            // Determine upload directory based on file type
            if (strpos($file_type, 'image/') === 0) {
                $upload_path = $images_dir . $unique_filename;
            } else {
                $upload_path = $documents_dir . $unique_filename;
            }
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $uploaded_files[$field_name] = [
                    'original_name' => $file_name,
                    'saved_name' => $unique_filename,
                    'path' => $upload_path,
                    'size' => $file_size,
                    'type' => $file_type
                ];
            } else {
                $response['errors'][] = "Failed to upload $field_name";
            }
        } else {
            $response['errors'][] = "Upload error for $field_name: " . $file_data['error'];
        }
    }
    
    if (!empty($uploaded_files)) {
        $response['success'] = true;
        $response['files'] = $uploaded_files;
        $response['message'] = 'Files uploaded successfully';
    } else {
        $response['success'] = false;
        $response['message'] = 'No files were uploaded successfully';
    }
    
    echo json_encode($response);
    exit;
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $file_path = $input['file_path'] ?? '';
    
    if (file_exists($file_path) && unlink($file_path)) {
        echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete file']);
    }
    exit;
}

// Default response for unsupported methods
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?> 