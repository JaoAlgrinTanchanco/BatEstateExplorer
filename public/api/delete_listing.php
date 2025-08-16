<?php
require_once __DIR__ . '/../../config/database.php';

$property_id = (int)($_POST['property_id'] ?? $_GET['id'] ?? 0);

if (!$property_id) {
    $_SESSION['flash_error'] = "Invalid property ID.";
    header("Location: http://localhost/BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
    exit;
}

// Delete property images from storage
$stmtImg = $conn->prepare("SELECT image_path FROM property_images WHERE property_id = ?");
$stmtImg->bind_param("i", $property_id);
$stmtImg->execute();
$resImg = $stmtImg->get_result();
while ($img = $resImg->fetch_assoc()) {
    $path = __DIR__ . '/../../' . $img['image_path'];
    if (file_exists($path)) unlink($path);
}
$stmtImg->close();

// Delete images from DB
$stmt = $conn->prepare("DELETE FROM property_images WHERE property_id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$stmt->close();

// Delete property documents from storage
$stmtDoc = $conn->prepare("SELECT document_path FROM property_documents WHERE property_id = ?");
$stmtDoc->bind_param("i", $property_id);
$stmtDoc->execute();
$resDoc = $stmtDoc->get_result();
while ($doc = $resDoc->fetch_assoc()) {
    $path = __DIR__ . '/../../' . $doc['document_path'];
    if (file_exists($path)) unlink($path);
}
$stmtDoc->close();

// Delete documents from DB
$stmt = $conn->prepare("DELETE FROM property_documents WHERE property_id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$stmt->close();

// Delete the property itself
$stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$stmt->close();

// Redirect back to My Listings tab
header("Location: http://localhost/BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
exit;
