<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // --- Migration-safety check for claim_prop column ---
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

    // --- Get logged-in user ---
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        throw new Exception('User not logged in.');
    }

    // --- Parse JSON body ---
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input.');
    }

    $property_id = (int)($data['property_id'] ?? 0);
    if (!$property_id) {
        throw new Exception('Property ID is required.');
    }

    // --- Retrieve company_prop_id from the property ---
    $stmt = $conn->prepare("SELECT company_prop_id FROM properties WHERE id = ?");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $property = $result->fetch_assoc();
    $stmt->close();

    if (empty($property['company_prop_id'])) {
        echo json_encode(['success' => false, 'message' => 'No company_prop_id found for this property.']);
        exit;
    }

    $company_prop_id = $property['company_prop_id'];

    // Begin transaction
    $conn->begin_transaction();

    // --- Step 1: Mark current property as claimed ---
    $stmt = $conn->prepare("UPDATE properties SET claim_prop = 1 WHERE company_prop_id = ? AND id = ?");
    $stmt->bind_param("si", $company_prop_id, $property_id);
    $stmt->execute();
    $stmt->close();

    // --- Step 2: Delete unclaimed properties with the same company_prop_id ---
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

    // --- Step 3: Notify affected agents ---
    $agents = [];
    if ($deletedCount > 0) {
        $stmt = $conn->prepare("
            SELECT DISTINCT agent_id 
            FROM properties 
            WHERE company_prop_id = ? AND id != ?
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

        $message = "Another agent has successfully closed a sale for company listing ID: {$company_prop_id}. Your related listings have been removed.";

        if (!empty($agents)) {
            $stmt = $conn->prepare("INSERT INTO notices (user_id, company_prop_id, message, isseen) VALUES (?, ?, ?, 0)");
            foreach ($agents as $agent_id) {
                $sub = $conn->prepare("SELECT user_id FROM agents WHERE id = ?");
                $sub->bind_param("i", $agent_id);
                $sub->execute();
                $resSub = $sub->get_result();
                $agent = $resSub->fetch_assoc();
                $sub->close();

                if (!empty($agent['user_id'])) {
                    $stmt->bind_param("iss", $agent['user_id'], $company_prop_id, $message);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Property marked as sold and related listings removed.',
        'deleted' => $deletedCount,
        'notified_agents' => count($agents ?? [])
    ]);

} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
