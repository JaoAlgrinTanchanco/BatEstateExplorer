<?php
session_start();
require_once 'config/database.php';

// Set JSON content type header
header('Content-Type: application/json');

// Handle GET request to get public property details
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($property_id) {
        // Get property details with agent information including company
        $query = "SELECT p.*, 
                         u.first_name, u.last_name, u.phone, u.email, u.user_type,
                         a.broker_id, a.license_number, a.experience_years, a.specialization, a.bio, a.company_id,
                         c.name as company_name
                  FROM properties p 
                  LEFT JOIN agents a ON p.agent_id = a.id
                  LEFT JOIN users u ON a.user_id = u.id 
                  LEFT JOIN companies c ON a.company_id = c.id
                  WHERE p.id = ? AND p.status = 'available'";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $property_id);
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
            echo json_encode(['success' => false, 'message' => 'Property not found or not available']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 