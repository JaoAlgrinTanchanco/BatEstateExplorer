<?php
// Suppress error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if user is logged in and is a direct agent or associate agent
        $is_logged_in = is_logged_in();
        if (!$is_logged_in) {
            throw new Exception('User not logged in');
        }

        $current_user = get_logged_in_user($conn);
        if (!$current_user || ($current_user['user_type'] !== 'direct_agent' && $current_user['user_type'] !== 'associate_agent')) {
            throw new Exception('Unauthorized access');
        }

        // Get input parameters
        $image_id = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
        $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
        
        if (!$image_id || !$property_id) {
            throw new Exception('Missing required parameters');
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
            // Associate agents can delete images from properties in their company
            $property_check = "SELECT p.id, p.title FROM properties p 
                              LEFT JOIN agents a ON p.agent_id = a.id 
                              WHERE p.id = ? AND a.company_id = ?";
            $stmt = mysqli_prepare($conn, $property_check);
            mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent['company_id']);
        } else {
            // Direct agents can only delete images from their own properties
            $property_check = "SELECT id, title FROM properties WHERE id = ? AND agent_id = ?";
            $stmt = mysqli_prepare($conn, $property_check);
            mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent_id);
        }
        
        mysqli_stmt_execute($stmt);
        $property_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($property_result) === 0) {
            throw new Exception('Property not found or access denied');
        }

        // Get image details before deletion
        $image_query = "SELECT image_path FROM property_images WHERE id = ? AND property_id = ?";
        $stmt = mysqli_prepare($conn, $image_query);
        mysqli_stmt_bind_param($stmt, "ii", $image_id, $property_id);
        mysqli_stmt_execute($stmt);
        $image_result = mysqli_stmt_get_result($stmt);
        $image = mysqli_fetch_assoc($image_result);

        if (!$image) {
            throw new Exception('Image not found or access denied');
        }

        $image_path = $image['image_path'];

        // Delete from database
        $delete_query = "DELETE FROM property_images WHERE id = ? AND property_id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "ii", $image_id, $property_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to delete image from database: ' . mysqli_error($conn));
        }

        // Delete file from server
        if (file_exists($image_path)) {
            if (!unlink($image_path)) {
                // Log the error but don't fail the request
                error_log("Failed to delete image file: " . $image_path);
            }
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 