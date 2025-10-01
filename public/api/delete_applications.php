<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';

    if ($status === 'all') {
        // Delete all applications
        $stmt = $conn->prepare("DELETE FROM applications");
        $msg = "All applications have been deleted.";
    } else {
        // Validate status
        if (!in_array($status, ['pending','approved','rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM applications WHERE status = ?");
        $stmt->bind_param("s", $status);
        $msg = ucfirst($status) . " applications removed.";
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $msg]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete applications.']);
    }

    $stmt->close();
    $conn->close();
}
