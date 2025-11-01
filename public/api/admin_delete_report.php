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
$reportId   = $data['report_id'] ?? null;
$reportType = $data['report_type'] ?? null; // expected: 'agent', 'property', 'user'

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
        // fallback to user_reports to avoid invalid table names
        $table = 'user_reports';
        break;
}

try {
    // ✅ Use prepared statement
    $stmt = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => ucfirst($reportType ?: 'User') . ' report deleted successfully.'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found or already deleted.']);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to delete report.',
        'details' => $e->getMessage()
    ]);
}
