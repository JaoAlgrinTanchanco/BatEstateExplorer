<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

$propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
if (!$propertyId) {
    echo json_encode(['success' => false, 'error' => 'Property ID required']);
    exit;
}

// Fetch reviews for the property
$sql = "
    SELECT pr.rating, pr.review_text, pr.created_at, u.first_name, u.last_name
    FROM property_reviews pr
    JOIN users u ON pr.user_id = u.id
    WHERE pr.property_id = ?
    ORDER BY pr.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $propertyId);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = [
        'user_name' => $row['first_name'] . ' ' . $row['last_name'],
        'rating' => (int)$row['rating'],
        'review_text' => $row['review_text'] ?? '',
        'created_at' => $row['created_at'],
    ];
}

echo json_encode(['success' => true, 'reviews' => $reviews]);
