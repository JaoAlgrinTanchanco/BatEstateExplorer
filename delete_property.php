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

// Handle POST request to delete property
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
        $property_check = "SELECT id, title FROM properties WHERE id = ? AND agent_id = ?";
        $stmt = mysqli_prepare($conn, $property_check);
        mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent_id);
        mysqli_stmt_execute($stmt);
        $property_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($property_result) === 0) {
            throw new Exception('Property not found or access denied');
        }
        
        $property = mysqli_fetch_assoc($property_result);
        $property_title = $property['title'];
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Delete property images from server and database
            $images_query = "SELECT image_path FROM property_images WHERE property_id = ?";
            $stmt = mysqli_prepare($conn, $images_query);
            mysqli_stmt_bind_param($stmt, "i", $property_id);
            mysqli_stmt_execute($stmt);
            $images_result = mysqli_stmt_get_result($stmt);
            
            while ($image = mysqli_fetch_assoc($images_result)) {
                // Delete file from server
                if (file_exists($image['image_path'])) {
                    unlink($image['image_path']);
                }
            }
            
            // Delete property documents from server and database
            $documents_query = "SELECT document_path FROM property_documents WHERE property_id = ?";
            $stmt = mysqli_prepare($conn, $documents_query);
            mysqli_stmt_bind_param($stmt, "i", $property_id);
            mysqli_stmt_execute($stmt);
            $documents_result = mysqli_stmt_get_result($stmt);
            
            while ($document = mysqli_fetch_assoc($documents_result)) {
                // Delete file from server
                if (file_exists($document['document_path'])) {
                    unlink($document['document_path']);
                }
            }
            
            // Delete from property_images table
            $delete_images = "DELETE FROM property_images WHERE property_id = ?";
            $stmt = mysqli_prepare($conn, $delete_images);
            mysqli_stmt_bind_param($stmt, "i", $property_id);
            mysqli_stmt_execute($stmt);
            
            // Delete from property_documents table
            $delete_documents = "DELETE FROM property_documents WHERE property_id = ?";
            $stmt = mysqli_prepare($conn, $delete_documents);
            mysqli_stmt_bind_param($stmt, "i", $property_id);
            mysqli_stmt_execute($stmt);
            
            // Delete the property
            $delete_property = "DELETE FROM properties WHERE id = ? AND agent_id = ?";
            $stmt = mysqli_prepare($conn, $delete_property);
            mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to delete property: ' . mysqli_error($conn));
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Return success response
            echo json_encode([
                'success' => true, 
                'message' => "Property '$property_title' has been deleted successfully"
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 