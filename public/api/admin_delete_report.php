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

$data = json_decode(file_get_contents('php://input'), true);
$reportId   = isset($data['report_id']) ? (int)$data['report_id'] : null;
$reportType = $data['report_type'] ?? null; // expected: 'agent', 'property', 'user', 'account'

if (!$reportId) {
    http_response_code(400);
    echo json_encode(['error' => 'Report ID is required.']);
    exit;
}

// ✅ Determine correct table
switch ($reportType) {
    case 'agent':
        $table = 'agent_reports';
        break;
    case 'property':
        $table = 'property_reports';
        break;
    case 'user':
    case 'account':
        $table = 'user_reports';
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid report type.']);
        exit;
}

try {
    // 🔍 Check if the report actually exists
    $checkStmt = $conn->prepare("SELECT id FROM {$table} WHERE id = ?");
    $checkStmt->bind_param("i", $reportId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Report not found or already deleted.',
            'debug' => [
                'report_id' => $reportId,
                'table' => $table,
                'report_type' => $reportType
            ]
        ]);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();

    // ✅ Perform deletion
    $stmt = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => ucfirst($reportType) . ' report deleted successfully.',
            'deleted_id' => $reportId,
            'table' => $table
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'error' => 'Report not found or already deleted.',
            'debug' => [
                'report_id' => $reportId,
                'table' => $table,
                'report_type' => $reportType
            ]
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to delete report.',
        'details' => $e->getMessage(),
        'debug' => [
            'report_id' => $reportId,
            'table' => $table,
            'report_type' => $reportType
        ]
    ]);
}
