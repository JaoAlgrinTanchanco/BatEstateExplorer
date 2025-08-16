<?php
require_once __DIR__ . '/../../config/database.php';

if (!isset($_POST['property_id'])) die('Invalid request');

$property_id = (int)$_POST['property_id'];
$title = $_POST['title'];
$location = $_POST['location'];
$price = $_POST['price'];
$bedrooms = $_POST['bedrooms'];
$bathrooms = $_POST['bathrooms'];
$status = $_POST['status'];

// Update property
$stmt = $conn->prepare("
    UPDATE properties 
    SET title = ?, location = ?, price = ?, bedrooms = ?, bathrooms = ?, status = ? 
    WHERE id = ?
");
$stmt->bind_param("ssdiisi", $title, $location, $price, $bedrooms, $bathrooms, $status, $property_id);
$stmt->execute();

if ($stmt->affected_rows >= 0) {
    $_SESSION['flash_success'] = 'Property updated successfully.';
} else {
    $_SESSION['flash_error'] = 'Failed to update property.';
}

$stmt->close();
header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
exit;
