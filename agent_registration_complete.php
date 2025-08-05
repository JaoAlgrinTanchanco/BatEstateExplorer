<?php
// Complete agent registration system
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Add debugging
error_log("=== AGENT REGISTRATION START ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . $_SERVER['CONTENT_TYPE'] ?? 'not set');



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
    error_log("POST request received - starting processing");
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Get all form data
        $first_name = isset($_POST['first_name']) ? sanitize_input($conn, $_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_input($conn, $_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_input($conn, $_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $user_type = isset($_POST['user_type']) ? sanitize_input($conn, $_POST['user_type']) : '';
        $phone = isset($_POST['phone']) ? sanitize_input($conn, $_POST['phone']) : '';
        $address = isset($_POST['address']) ? sanitize_input($conn, $_POST['address']) : '';
        
        // Professional information
        $broker_id = isset($_POST['broker_id']) ? sanitize_input($conn, $_POST['broker_id']) : '';
        $prc_number = isset($_POST['prc_number']) ? sanitize_input($conn, $_POST['prc_number']) : '';
        $experience_years = isset($_POST['experience_years']) ? sanitize_input($conn, $_POST['experience_years']) : '';
        $specializations = isset($_POST['specializations']) ? sanitize_input($conn, $_POST['specializations']) : '';
        $experience_details = isset($_POST['experience_details']) ? sanitize_input($conn, $_POST['experience_details']) : '';
        
        // Educational background
        $education = isset($_POST['education']) ? sanitize_input($conn, $_POST['education']) : '';
        $school = isset($_POST['school']) ? sanitize_input($conn, $_POST['school']) : '';
        $course = isset($_POST['course']) ? sanitize_input($conn, $_POST['course']) : '';
        $graduation_year = isset($_POST['graduation_year']) ? sanitize_input($conn, $_POST['graduation_year']) : '';
        
        // Certifications and training
        $certifications = isset($_POST['certifications']) ? sanitize_input($conn, $_POST['certifications']) : '';
        $training = isset($_POST['training']) ? sanitize_input($conn, $_POST['training']) : '';
        
        // Handle file uploads - simplified version
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
        
        // Define allowed file types - IMAGES ONLY
        $allowed_types = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif'
        ];
        
        // File paths to store in database
        // Function to handle single file upload
        function handleFileUpload($file, $prefix, $documents_dir, $images_dir, $allowed_types) {
            // Log file upload attempt
            error_log("Processing file: " . $file['name'] . " (type: " . $file['type'] . ", size: " . $file['size'] . ")");
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                if (in_array($file['type'], $allowed_types) && $file['size'] <= 5 * 1024 * 1024) {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = $prefix . '_' . uniqid() . '.' . $extension;
                    $upload_path = (strpos($file['type'], 'image/') === 0) ? $images_dir : $documents_dir;
                    $filepath = $upload_path . $filename;
                    
                    error_log("Attempting to move file to: " . $filepath);
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        error_log("File uploaded successfully: " . $filepath);
                        return $filepath;
                    } else {
                        error_log("Failed to move uploaded file to: " . $filepath);
                        return null;
                    }
                } else {
                    error_log("File validation failed - type: " . $file['type'] . ", size: " . $file['size']);
                    return null;
                }
            } else {
                error_log("File upload error: " . $file['error']);
                return null;
            }
        }
        
        // Handle individual files with better error handling
        $broker_license_path = null;
        $prc_license_path = null;
        $resume_path = null;
        $valid_id_path = null;
        $additional_docs_path = null;
        
        // Handle file uploads (make them optional for now)
        if (isset($_FILES['broker_license']) && $_FILES['broker_license']['error'] !== UPLOAD_ERR_NO_FILE) {
            $broker_license_path = handleFileUpload($_FILES['broker_license'], 'broker_license', $documents_dir, $images_dir, $allowed_types);
            // Don't throw exception if upload fails, just log it
            if (!$broker_license_path) {
                error_log("Failed to upload broker license file");
            }
        }
        
        if (isset($_FILES['prc_license']) && $_FILES['prc_license']['error'] !== UPLOAD_ERR_NO_FILE) {
            $prc_license_path = handleFileUpload($_FILES['prc_license'], 'prc_license', $documents_dir, $images_dir, $allowed_types);
            // Don't throw exception if upload fails, just log it
            if (!$prc_license_path) {
                error_log("Failed to upload PRC license file");
            }
        }
        
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] !== UPLOAD_ERR_NO_FILE) {
            $resume_path = handleFileUpload($_FILES['resume'], 'resume', $documents_dir, $images_dir, $allowed_types);
            // Don't throw exception if upload fails, just log it
            if (!$resume_path) {
                error_log("Failed to upload resume file");
            }
        }
        
        if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] !== UPLOAD_ERR_NO_FILE) {
            $valid_id_path = handleFileUpload($_FILES['valid_id'], 'valid_id', $documents_dir, $images_dir, $allowed_types);
            // Don't throw exception if upload fails, just log it
            if (!$valid_id_path) {
                error_log("Failed to upload valid ID file");
            }
        }
        
        // Handle additional documents
        if (isset($_FILES['additional_docs']) && is_array($_FILES['additional_docs']['tmp_name'])) {
            $additional_files = [];
            foreach ($_FILES['additional_docs']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['additional_docs']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['additional_docs']['name'][$key],
                        'type' => $_FILES['additional_docs']['type'][$key],
                        'tmp_name' => $tmp_name,
                        'size' => $_FILES['additional_docs']['size'][$key],
                        'error' => $_FILES['additional_docs']['error'][$key]
                    ];
                    
                    $filepath = handleFileUpload($file, 'additional', $documents_dir, $images_dir, $allowed_types);
                    if ($filepath) {
                        $additional_files[] = $filepath;
                    }
                }
            }
            if (!empty($additional_files)) {
                $additional_docs_path = implode(',', $additional_files);
            }
        }
        
        // Company information (for associate agents) - handle properly
        $company_id = null;
        if (isset($_POST['company_id']) && !empty($_POST['company_id'])) {
            $company_id = (int)$_POST['company_id'];
            
            // Validate that the company exists
            $query = "SELECT id FROM companies WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $company_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) == 0) {
                // Company doesn't exist, set to null for direct agents
                if ($user_type === 'direct_agent') {
                    $company_id = null;
                } else {
                    throw new Exception('Selected company does not exist');
                }
            }
        }
        
        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($user_type)) {
            throw new Exception('All required fields must be filled');
        }
        
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            throw new Exception('Email already exists');
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Create comprehensive bio from all information
            $bio = "=== PROFESSIONAL QUALIFICATIONS ===\n";
            $bio .= "Broker ID: " . $broker_id . "\n";
            $bio .= "PRC License Number: " . $prc_number . "\n";
            $bio .= "Years of Experience: " . $experience_years . "\n";
            $bio .= "Specializations: " . $specializations . "\n\n";
            
            $bio .= "=== WORK EXPERIENCE ===\n";
            $bio .= $experience_details . "\n\n";
            
            $bio .= "=== EDUCATIONAL BACKGROUND ===\n";
            $bio .= "Highest Education: " . $education . "\n";
            $bio .= "School/University: " . $school . "\n";
            $bio .= "Course/Degree: " . $course . "\n";
            $bio .= "Year Graduated: " . $graduation_year . "\n\n";
            
            if (!empty($certifications)) {
                $bio .= "=== CERTIFICATIONS ===\n";
                $bio .= $certifications . "\n\n";
            }
            
            if (!empty($training)) {
                $bio .= "=== TRAINING PROGRAMS ===\n";
                $bio .= $training . "\n\n";
            }
            
            // Store all data in applications table (no user account created yet)
            $query = "INSERT INTO applications (user_id, first_name, last_name, email, password_hash, phone, address, education, school, course, graduation_year, certifications, additional_docs_path, agent_type, company_id, broker_id, license_number, experience_years, specialization, bio, status, admin_notes, training, broker_license_path, prc_license_path, resume_path, valid_id_path) 
                     VALUES (NULL, '$first_name', '$last_name', '$email', '$password_hash', '$phone', '$address', '$education', '$school', '$course', '$graduation_year', '$certifications', '$additional_docs_path', '$user_type', $company_id, '$broker_id', '$prc_number', $experience_years, '$specializations', '$bio', 'pending', NULL, '$training', '$broker_license_path', '$prc_license_path', '$resume_path', '$valid_id_path')";
            
            if (!mysqli_query($conn, $query)) {
                throw new Exception('Failed to create application: ' . mysqli_error($conn));
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            error_log("Transaction committed successfully");
            
            $response['success'] = true;
            $response['message'] = 'Application submitted successfully! Your application will be reviewed by admin.';
            $response['application_id'] = mysqli_insert_id($conn);
            
            error_log("Response prepared: " . json_encode($response));
            
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Exception caught: " . $e->getMessage());
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    error_log("About to send response");
    echo json_encode($response);
    error_log("Response sent");
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid request method'
]);
exit;
?> 