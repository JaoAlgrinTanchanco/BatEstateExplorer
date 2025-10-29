<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');
session_start();

try {
    // --- Ensure claim_prop column exists and correct ---
    $check = $conn->query("
        SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'properties'
          AND COLUMN_NAME = 'claim_prop'
    ");

    if ($check && $row = $check->fetch_assoc()) {
        $needsAlter = false;
        if (strtolower($row['COLUMN_TYPE']) !== 'tinyint(1)') $needsAlter = true;
        if ($row['IS_NULLABLE'] !== 'NO') $needsAlter = true;
        if ($row['COLUMN_DEFAULT'] != 0) $needsAlter = true;
        if ($needsAlter) {
            $conn->query("ALTER TABLE properties MODIFY COLUMN claim_prop TINYINT(1) NOT NULL DEFAULT 0;");
        }
    }

    // --- Validate user session ---
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        throw new Exception('User not logged in.');
    }

    // --- Parse JSON input ---
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input.');
    }

    $property_id = (int)($data['property_id'] ?? 0);
    if (!$property_id) {
        throw new Exception('Property ID is required.');
    }

    // --- Get company_prop_id and agent_id of claiming property ---
    $stmt = $conn->prepare("SELECT company_prop_id, agent_id FROM properties WHERE id = ?");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $property = $result->fetch_assoc();
    $stmt->close();

    if (empty($property['company_prop_id'])) {
        throw new Exception('No company_prop_id found for this property.');
    }

    $company_prop_id = $property['company_prop_id'];
    $claiming_agent_id = $property['agent_id'] ?? null;

    if (!$claiming_agent_id) {
        throw new Exception('Claiming agent not found.');
    }

    // --- Retrieve claiming agent's user_id (to be used in notices) ---
    $stmt = $conn->prepare("SELECT user_id FROM agents WHERE id = ?");
    $stmt->bind_param("i", $claiming_agent_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $claiming_agent = $res->fetch_assoc();
    $stmt->close();

    if (empty($claiming_agent['user_id'])) {
        throw new Exception('Claiming agent has no linked user account.');
    }

    $claiming_user_id = (int)$claiming_agent['user_id'];

    $conn->begin_transaction();

    // --- Step 1: Collect all other agents with same company_prop_id ---
    $agents = [];
    $stmt = $conn->prepare("
        SELECT DISTINCT agent_id
        FROM properties
        WHERE company_prop_id = ?
          AND id != ?
          AND claim_prop = 0
    ");
    $stmt->bind_param("si", $company_prop_id, $property_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['agent_id'])) {
            $agents[] = $row['agent_id'];
        }
    }
    $stmt->close();

    // --- Step 2: Mark this property as claimed ---
    $stmt = $conn->prepare("UPDATE properties SET claim_prop = 1 WHERE id = ?");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $stmt->close();

    // --- Step 3: Delete unclaimed duplicates ---
    $stmt = $conn->prepare("
        DELETE FROM properties
        WHERE company_prop_id = ?
          AND id != ?
          AND claim_prop = 0
    ");
    $stmt->bind_param("si", $company_prop_id, $property_id);
    $stmt->execute();
    $deletedCount = $stmt->affected_rows;
    $stmt->close();

    // --- Step 4: Insert notices ---
    $notifiedCount = 0;

    // (a) For the claiming agent
    $successMessage = "You have successfully closed the sale for company listing ID: {$company_prop_id}.";
    $stmt = $conn->prepare("
        INSERT INTO notices (user_id, notice_except, company_prop_id, message, isseen)
        VALUES (?, NULL, ?, ?, 0)
    ");
    $stmt->bind_param("iss", $claiming_user_id, $company_prop_id, $successMessage);
    $stmt->execute();
    $stmt->close();

    // (b) For all other agents — user_id NULL, notice_except = claiming user_id
    if (!empty($agents)) {
        $message = "Another agent has successfully closed a sale for company listing ID: {$company_prop_id}. Your related listings have been removed.";
        $insert = $conn->prepare("
            INSERT INTO notices (user_id, notice_except, company_prop_id, message, isseen)
            VALUES (NULL, ?, ?, ?, 0)
        ");
        foreach ($agents as $agent_id) {
            $insert->bind_param("iss", $claiming_user_id, $company_prop_id, $message);
            $insert->execute();
            $notifiedCount++;
        }
        $insert->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Property marked as sold and related listings removed.',
        'deleted' => $deletedCount,
        'notified_agents' => $notifiedCount
    ]);

} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
