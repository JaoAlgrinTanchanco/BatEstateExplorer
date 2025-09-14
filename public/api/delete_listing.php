<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

$property_id = (int)($_POST['property_id'] ?? $_GET['id'] ?? 0);

// 🛑 Validate property ID
if (!$property_id) {
    $_SESSION['flash_error'] = "Invalid property ID.";
    redirectAfterDelete($conn);
    exit;
}

// ================================
// Delete property images from storage
// ================================
$stmtImg = $conn->prepare("SELECT image_path FROM property_images WHERE property_id = ?");
$stmtImg->bind_param("i", $property_id);
$stmtImg->execute();
$resImg = $stmtImg->get_result();
while ($img = $resImg->fetch_assoc()) {
    $path = __DIR__ . '/../../' . $img['image_path'];
    if (file_exists($path)) unlink($path);
}
$stmtImg->close();

$stmt = $conn->prepare("DELETE FROM property_images WHERE property_id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$stmt->close();

// ================================
// Delete property documents from storage
// ================================
$stmtDoc = $conn->prepare("SELECT document_path FROM property_documents WHERE property_id = ?");
$stmtDoc->bind_param("i", $property_id);
$stmtDoc->execute();
$resDoc = $stmtDoc->get_result();
while ($doc = $resDoc->fetch_assoc()) {
    $path = __DIR__ . '/../../' . $doc['document_path'];
    if (file_exists($path)) unlink($path);
}
$stmtDoc->close();

$stmt = $conn->prepare("DELETE FROM property_documents WHERE property_id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$stmt->close();

// ================================
// Delete the property itself
// ================================
$stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$stmt->close();

// ================================
// Redirect dynamically
// ================================
redirectAfterDelete($conn);

function redirectAfterDelete($conn) {
    // Detect current agent from session
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        header("Location: http://localhost/BatEstateExplorer/public/login.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT company_id FROM agents WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $agent = $res->fetch_assoc();
    $stmt->close();

    if ($agent && $agent['company_id'] > 0) {
        // Associate agent
        header("Location: http://localhost/BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile&tab=my_listings");
    } else {
        // Direct agent
        header("Location: http://localhost/BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings");
    }
    exit;
}
