<?php
// Suppress error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if user is logged in and is a direct agent
        $is_logged_in = is_logged_in();
        if (!$is_logged_in) {
            throw new Exception('User not logged in');
        }

        $current_user = get_logged_in_user($conn);
        if (!$current_user || $current_user['user_type'] !== 'direct_agent') {
            throw new Exception('Unauthorized access');
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['image_id']) || !isset($input['property_id'])) {
            throw new Exception('Missing required parameters');
        }

        $image_id = intval($input['image_id']);
        $property_id = intval($input['property_id']);

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