<?php
header('Content-Type: application/json');
require_once 'config/database.php';

try {
    // Get properties from database
    $query = "SELECT p.*, u.first_name, u.last_name 
              FROM properties p 
              LEFT JOIN users u ON p.agent_id = u.id 
              WHERE p.status = 'active' 
              ORDER BY p.created_at DESC 
              LIMIT 20";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }
    
    $properties = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format the property data
        $property = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'property_type' => $row['property_type'],
            'location' => $row['location'],
            'price' => $row['price'],
            'bedrooms' => $row['bedrooms'],
            'bathrooms' => $row['bathrooms'],
            'sqm' => $row['sqm'],
            'lot_size' => $row['lot_size'],
            'image' => $row['image_path'] ?: 'Pictures/bg4.jpg',
            'agent_name' => $row['first_name'] && $row['last_name'] ? 
                           $row['first_name'] . ' ' . $row['last_name'] : 'Unknown Agent',
            'features' => [
                $row['bedrooms'] > 0 ? $row['bedrooms'] . ' BR' : null,
                $row['bathrooms'] > 0 ? $row['bathrooms'] . ' BA' : null,
                $row['sqm'] > 0 ? $row['sqm'] . ' sqm' : null
            ]
        ];
        
        // Remove null features
        $property['features'] = array_filter($property['features']);
        
        $properties[] = $property;
    }
    
    echo json_encode($properties);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
