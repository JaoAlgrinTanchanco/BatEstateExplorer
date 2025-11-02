<?php
// File: public/api/get_agent_reports.php
header('Content-Type: application/json');
require_once '../../config/database.php'; // Adjust path as needed

// Validate agent_id
if (!isset($_GET['agent_id']) || !is_numeric($_GET['agent_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing or invalid agent_id'
    ]);
    exit;
}

$agent_id = (int)$_GET['agent_id'];

// Fetch blocked reports for this agent
$sql = "
    SELECT 
        id,
        reporter_id,
        agent_id,
        reason,
        other_reason,
        duration,
        blockage_date,
        details,
        evidence_path,
        status,
        admin_notes,
        created_at,
        updated_at
    FROM agent_reports
    WHERE agent_id = ? AND status = 'blocked'
    ORDER BY blockage_date DESC
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Database prepare failed: ' . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $agent_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$report = mysqli_fetch_assoc($result);

if (!$report) {
    echo json_encode([
        'success' => false,
        'error' => 'No blocked reports found for this agent'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'report' => $report
    ]);
}
exit;
