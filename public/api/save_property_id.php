<?php
    session_start();
    header('Content-Type: application/json');
    require_once __DIR__ . '/../../config/database.php';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    try {
        $conn->begin_transaction();

        // 1) Find the most recent property created by this agent
        $stmt = $conn->prepare("
            SELECT id 
            FROM properties 
            WHERE agent_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $property = $res->fetch_assoc();
        $stmt->close();

        if (!$property) {
            throw new Exception("No property found for this agent.");
        }

        $property_id = intval($property['id']);

        // 2) Update all listing fee transactions without a property_id for this agent
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET property_id = ? 
            WHERE user_id = ? AND property_id IS NULL AND property LIKE 'Listing Fee%'
        ");
        $stmt->bind_param("ii", $property_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => "Property ID #$property_id linked to listing fee successfully.",
            'property_id' => $property_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
?>
