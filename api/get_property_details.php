<?php
// Suppress error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// Log errors to file instead of output
ini_set('log_errors', 1);
ini_set('error_log', 'debug_errors.log');

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

// Handle GET request to get property details
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if requesting all properties (for search pages)
    if (isset($_GET['all']) && $_GET['all'] == '1') {
        // Get all available properties with images for search display
        $query = "SELECT p.*, u.first_name, u.last_name, 
                  (SELECT pi.image_path FROM property_images pi WHERE pi.property_id = p.id LIMIT 1) as main_image
                  FROM properties p 
                  LEFT JOIN agents a ON p.agent_id = a.id
                  LEFT JOIN users u ON a.user_id = u.id 
                  WHERE p.status = 'available'
                  ORDER BY p.created_at DESC";
        
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $properties = [];
            while ($property = mysqli_fetch_assoc($result)) {
                $properties[] = $property;
            }
            echo json_encode($properties);
        } else {
            echo json_encode([]);
        }
        exit;
    }
    
    $property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($property_id) {
        // Get agent ID and company info
        $agent_query = "SELECT a.id, a.company_id FROM agents a WHERE a.user_id = ?";
        $stmt = mysqli_prepare($conn, $agent_query);
        mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
        mysqli_stmt_execute($stmt);
        $agent_result = mysqli_stmt_get_result($stmt);
        $agent = mysqli_fetch_assoc($agent_result);
        
        if (!$agent) {
            echo json_encode(['success' => false, 'message' => 'Agent record not found']);
            exit;
        }
        
        $agent_id = $agent['id'];
        
        // Get property details - for associate agents, allow access to company properties
        if ($current_user['user_type'] === 'associate_agent') {
            // Associate agents can view properties from their company
            $query = "SELECT p.* FROM properties p 
                      LEFT JOIN agents a ON p.agent_id = a.id 
                      WHERE p.id = ? AND a.company_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent['company_id']);
        } else {
            // Direct agents can only view their own properties
            $query = "SELECT * FROM properties WHERE id = ? AND agent_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent_id);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $property = mysqli_fetch_assoc($result);
            
            // Get property images
            $images_query = "SELECT id, image_path FROM property_images WHERE property_id = ? ORDER BY created_at ASC";
            $stmt = mysqli_prepare($conn, $images_query);
            mysqli_stmt_bind_param($stmt, "i", $property_id);
            mysqli_stmt_execute($stmt);
            $images_result = mysqli_stmt_get_result($stmt);
            $images = [];
            while ($image = mysqli_fetch_assoc($images_result)) {
                $images[] = $image;
            }
            
            // Get property documents
            $documents_query = "SELECT id, document_path FROM property_documents WHERE property_id = ? ORDER BY created_at ASC";
            $stmt = mysqli_prepare($conn, $documents_query);
            mysqli_stmt_bind_param($stmt, "i", $property_id);
            mysqli_stmt_execute($stmt);
            $documents_result = mysqli_stmt_get_result($stmt);
            $documents = [];
            while ($document = mysqli_fetch_assoc($documents_result)) {
                $documents[] = $document;
            }
            
            // Combine all data
            $property_data = array_merge($property, [
                'images' => $images,
                'documents' => $documents
            ]);
            
            echo json_encode(['success' => true, 'property' => $property_data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Property not found or access denied']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 