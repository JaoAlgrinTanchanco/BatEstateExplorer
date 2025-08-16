<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

if (!isset($_SESSION['user'])) {
    die('Access denied.');
}

$user = $_SESSION['user'];

$property_id = (int)($_GET['id'] ?? 0);
if (!$property_id) {
    $_SESSION['flash_error'] = "Invalid property ID.";
    header("Location: ../controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
    exit;
}

// Get agent record
$stmtAgent = $conn->prepare("SELECT id FROM agents WHERE user_id = ?");
$stmtAgent->bind_param("i", $user['id']);
$stmtAgent->execute();
$res = $stmtAgent->get_result();
$agent = $res ? $res->fetch_assoc() : null;
$stmtAgent->close();

if (!$agent) {
    $_SESSION['flash_error'] = "Agent not found.";
    header("Location: ../controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
    exit;
}

// Check ownership
$stmt = $conn->prepare("SELECT * FROM properties WHERE id = ? AND (agent_id = ? OR sold_by_agent_id = ?)");
$stmt->bind_param("iii", $property_id, $agent['id'], $agent['id']);
$stmt->execute();
$res = $stmt->get_result();
$property = $res->fetch_assoc();
$stmt->close();

if (!$property) {
    $_SESSION['flash_error'] = "Property not found or you do not have permission.";
    header("Location: ../controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
    exit;
}

// Delete property images from storage
$stmtImg = $conn->prepare("SELECT image_path FROM property_images WHERE property_id = ?");
$stmtImg->bind_param("i", $property_id);
$stmtImg->execute();
$resImg = $stmtImg->get_result();
while ($img = $resImg->fetch_assoc()) {
    $path = '../../' . $img['image_path'];
    if (file_exists($path)) unlink($path);
}
$stmtImg->close();

// Delete images from DB
$stmt = $conn->prepare("DELETE FROM property_images WHERE property_id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$stmt->close();

// Delete property documents
$stmtDoc = $conn->prepare("SELECT document_path FROM property_document WHERE property_id = ?");
$stmtDoc->bind_param("i", $property_id);
$stmtDoc->execute();
$resDoc = $stmtDoc->get_result();
while ($doc = $resDoc->fetch_assoc()) {
    $path = '../../' . $doc['document_path'];
    if (file_exists($path)) unlink($path);
}
$stmtDoc->close();

// Delete documents from DB
$stmt = $conn->prepare("DELETE FROM property_document WHERE property_id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$stmt->close();

// Delete the property itself
$stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$stmt->close();

$_SESSION['flash_success'] = "Property deleted successfully.";
header("Location: ../controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
exit;
