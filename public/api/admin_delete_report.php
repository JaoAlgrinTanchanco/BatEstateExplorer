<?php
// admin_delete_report.php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not found.']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$reportId = $data['report_id'] ?? null;

if (!$reportId) {
    http_response_code(400);
    echo json_encode(['error' => 'Report ID is required.']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM agent_reports WHERE id = ?");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Report deleted successfully.'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found or already deleted.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to delete report.',
        'details' => $e->getMessage()
    ]);
}
