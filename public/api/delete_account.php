<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

// Helper function
function jsonResponse($success, $message = '') {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Not logged in.');
}

$userId = $_SESSION['user_id'];

$conn->begin_transaction();
try {
    // Delete saved properties
    $stmt = $conn->prepare("DELETE FROM saved_properties WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // Delete reviews
    $stmt = $conn->prepare("DELETE FROM property_reviews WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    // Destroy session
    session_destroy();

    jsonResponse(true, 'Account deleted successfully.');

} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed to delete account.');
}
