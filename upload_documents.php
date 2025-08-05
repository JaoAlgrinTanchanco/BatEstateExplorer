<?php
// File upload handler for agent documents
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once 'config/database.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
        
        if (!$application_id) {
            throw new Exception('Application ID is required');
        }
        
        // Verify application exists
        $query = "SELECT id FROM applications WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 0) {
            throw new Exception('Application not found');
        }
        
        // Handle file uploads
        if (isset($_FILES) && !empty($_FILES)) {
            $upload_dir = 'backend/uploads/documents/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $uploaded_files = [];
            
            // Process uploaded files
            foreach ($_FILES as $field_name => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $new_filename = $application_id . '_' . $field_name . '_' . time() . '.' . $file_extension;
                    $file_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        $uploaded_files[] = [
                            'field' => $field_name,
                            'filename' => $new_filename,
                            'path' => $file_path
                        ];
                    }
                }
            }
            
            $response['success'] = true;
            $response['message'] = 'Documents uploaded successfully!';
            $response['uploaded_files'] = $uploaded_files;
            
        } else {
            throw new Exception('No files were uploaded');
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid request method'
]);
exit;
?> 