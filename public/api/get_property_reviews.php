<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../app/bootstrap.php';

// Validate property id
$property_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($property_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid property ID']);
    exit;
}

// Fetch reviews for the property
$sql = "
    SELECT r.rating, r.review_text, r.created_at, u.first_name, u.last_name
    FROM property_reviews r
    INNER JOIN users u ON r.user_id = u.id
    WHERE r.property_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$res = $stmt->get_result();

$reviews = [];
$totalRating = 0;
while ($row = $res->fetch_assoc()) {
    $reviews[] = [
        'user' => $row['first_name'] . ' ' . $row['last_name'],
        'rating' => (int)$row['rating'],
        'review_text' => $row['review_text'],
        'created_at' => $row['created_at']
    ];
    $totalRating += (int)$row['rating'];
}
$stmt->close();

$avgRating = count($reviews) > 0 ? round($totalRating / count($reviews), 1) : 0;

echo json_encode([
    'success' => true,
    'average_rating' => $avgRating,
    'reviews' => $reviews
]);
