<?php
session_start();
header('Content-Type: application/json');

// --- Load bootstrap ---
require_once __DIR__ . '/../app/bootstrap.php';

// --- Ensure logged in ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to report a property.']);
    exit;
}

// --- Only accept POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// --- Gather input ---
$reporter_id = (int) $_SESSION['user_id'];
$property_id = null;
$reason = null;
$other_reason = null;
$details = null;

// Check if request is JSON
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);

if (!empty($jsonInput)) {
    $property_id  = isset($jsonInput['property_id']) ? (int) $jsonInput['property_id'] : null;
    $reason       = isset($jsonInput['reason']) ? trim($jsonInput['reason']) : null;
    $other_reason = isset($jsonInput['other_reason']) ? trim($jsonInput['other_reason']) : null;
    $details      = isset($jsonInput['details']) ? trim($jsonInput['details']) : null;
} else {
    // fallback to $_POST (form-data)
    $property_id  = isset($_POST['property_id']) ? (int) $_POST['property_id'] : null;
    $reason       = isset($_POST['reason']) ? trim($_POST['reason']) : null;
    $other_reason = isset($_POST['other_reason']) ? trim($_POST['other_reason']) : null;
    $details      = isset($_POST['details']) ? trim($_POST['details']) : null;
}

// --- Basic validation ---
if (!$property_id || !$reason) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a reason for your report.']);
    exit;
}

// --- Only keep other_reason if reason is "other" ---
if ($reason !== 'other') $other_reason = null;

try {
    // --- Determine DB connection ---
    $dbConn = null;
    if (isset($pdo) && $pdo instanceof PDO) $dbConn = $pdo;
    elseif (isset($db) && method_exists($db, 'getConnection')) $dbConn = $db->getConnection();
    elseif (isset($conn) && $conn instanceof mysqli) $dbConn = $conn;
    else throw new Exception('Database connection not found.');

    // --- Insert report ---
    if ($dbConn instanceof PDO) {
        $stmt = $dbConn->prepare("
            INSERT INTO property_reports (reporter_id, property_id, reason, other_reason, details)
            VALUES (:reporter_id, :property_id, :reason, :other_reason, :details)
        ");
        $stmt->execute([
            ':reporter_id'  => $reporter_id,
            ':property_id'  => $property_id,
            ':reason'       => $reason,
            ':other_reason' => $other_reason,
            ':details'      => $details
        ]);

        // --- Update property flag ---
        $update = $dbConn->prepare("UPDATE properties SET is_reported = 1 WHERE id = :id");
        $update->execute([':id' => $property_id]);

    } elseif ($dbConn instanceof mysqli) {
        $stmt = $dbConn->prepare("
            INSERT INTO property_reports (reporter_id, property_id, reason, other_reason, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iisss', $reporter_id, $property_id, $reason, $other_reason, $details);
        $stmt->execute();

        $update = $dbConn->prepare("UPDATE properties SET is_reported = 1 WHERE id = ?");
        $update->bind_param('i', $property_id);
        $update->execute();
    }

    // --- Success response ---
    echo json_encode(['status' => 'success', 'message' => 'Property report submitted successfully.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
