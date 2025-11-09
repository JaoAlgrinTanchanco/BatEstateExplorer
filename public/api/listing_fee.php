<?php
    session_start();
    header('Content-Type: application/json');
    require_once __DIR__ . '/../../config/database.php';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $property_type = trim($_POST['property_type'] ?? '');
    $tier_plan = trim($_POST['tier_plan'] ?? 'Basic');
    $listing_id = intval($_POST['listing_id'] ?? 0);

    if (!$property_type) {
        echo json_encode(['success' => false, 'error' => 'Missing property type']);
        exit;
    }

    // Normalize tier plan
    $tier_plan = ucfirst(strtolower($tier_plan)); 
    $tier_prices = ['Basic'=>399,'Standard'=>699,'Premium'=>1199,'Platinum'=>1799];
    $tierCost = $tier_prices[$tier_plan] ?? 399;

    $tierDurations = ['Basic'=>30,'Standard'=>45,'Premium'=>60,'Platinum'=>90];
    $tierFeatured  = ['Basic'=>0,'Standard'=>0,'Premium'=>1,'Platinum'=>1];

    $tierDuration = $tierDurations[$tier_plan];
    $isFeatured   = $tierFeatured[$tier_plan];

    $baseFee = $tierCost;
    $vat = round($tierCost * 0.12, 2);
    $totalDeduction = round($tierCost + $vat, 2);

    $conn->begin_transaction();

    try {
        // Lock agent wallet
        $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $agent = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$agent) throw new Exception("Agent not found");
        $agentBalance = (float)$agent['wallet_balance'];

        if ($agentBalance < $totalDeduction) {
            throw new Exception("Insufficient wallet balance. Required: PHP $totalDeduction");
        }

        // Deduct from agent wallet
        $newAgentBalance = $agentBalance - $totalDeduction;
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $stmt->bind_param("di", $newAgentBalance, $user_id);
        $stmt->execute();
        $stmt->close();

        // Lock admin wallet
        $stmt = $conn->prepare("SELECT id, wallet_balance FROM users WHERE user_type = 'admin' LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$admin) throw new Exception("Admin not found");

        // Add total to admin wallet
        $newAdminBalance = (float)$admin['wallet_balance'] + $totalDeduction;
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $stmt->bind_param("di", $newAdminBalance, $admin['id']);
        $stmt->execute();
        $stmt->close();

        // Record transaction
        $property = "Listing Fee ({$property_type}, {$tier_plan} Plan)";
        $status = 'completed';
        $method = 'wallet';

        // Record transaction with property_id and recipient_id (admin)
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, recipient_id, property_id, property, amount, status, method, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $propertyDesc = "Listing Fee ({$property_type}, {$tier_plan} Plan)";
        $stmt->bind_param("iiisdss", $user_id, $admin['id'], $listing_id, $propertyDesc, $totalDeduction, $status, $method);
        $stmt->execute();
        $stmt->close();

        // Record mirrored transaction for admin (admin as user_id, agent as recipient)
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, recipient_id, property_id, property, amount, status, method, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $mirroredAmount = $totalDeduction; // positive for admin side
        $stmt->bind_param("iiisdss", $admin['id'], $user_id, $listing_id, $propertyDesc, $mirroredAmount, $status, $method);
        $stmt->execute();
        $stmt->close();

        // -----------------------------
        // Update the property record
        // -----------------------------
        $featuredUntil = date('Y-m-d H:i:s', strtotime("+{$tierDuration} days"));
        $stmt = $conn->prepare("
            UPDATE properties
            SET is_featured = ?, featured_until = ?, tier_plan = ?, plan_duration = ?
            WHERE id = ?
        ");
        $stmt->bind_param("issii", $isFeatured, $featuredUntil, $tier_plan, $tierDuration, $listing_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'new_balance' => $newAgentBalance,
            'base_fee' => $baseFee,
            'vat' => $vat,
            'tier_cost' => $tierCost,
            'total_deduction' => $totalDeduction,
            'is_featured' => $isFeatured,
            'featured_until' => $featuredUntil,
            'plan_duration' => $tierDuration
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
?>
