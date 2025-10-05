<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/pdo_database.php';

try {
    // 1. Check user session
    $user_data = get_logged_in_user($pdo);
    if (!$user_data) throw new Exception("User not logged in");

    // 2. Get draft ID
    $draft_id = intval($_POST['draft_id'] ?? 0);
    if ($draft_id <= 0) throw new Exception("Invalid draft ID");

    // 3. Fetch draft record
    $stmt = $pdo->prepare("SELECT * FROM property_drafts WHERE id = ?");
    $stmt->execute([$draft_id]);
    $draft = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$draft) throw new Exception("Draft not found");

    // 4. Ensure agent record exists
    $stmtAgent = $pdo->prepare("SELECT id FROM agents WHERE user_id = ?");
    $stmtAgent->execute([$user_data['id']]);
    $agent = $stmtAgent->fetch(PDO::FETCH_ASSOC);

    $agent_id = $agent ? $agent['id'] : null;

    if (!$agent_id) {
        $stmtInsertAgent = $pdo->prepare("INSERT INTO agents (user_id, created_at) VALUES (?, NOW())");
        $stmtInsertAgent->execute([$user_data['id']]);
        $agent_id = $pdo->lastInsertId();
    }

    // 5. Start transaction
    $pdo->beginTransaction();

    // 6. Insert property using draft data
    $stmtInsert = $pdo->prepare("
        INSERT INTO properties 
        (title, description, property_type, location, price, bedrooms, bathrooms, lot_size, agent_id, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmtInsert->execute([
        $draft['title'],
        $draft['description'],
        $draft['property_type'],
        $draft['location'],
        $draft['price'],
        $draft['bedrooms'],
        $draft['bathrooms'],
        $draft['lot_size'],
        $agent_id
    ]);
    $property_id = $pdo->lastInsertId();

    // 7. Move draft images to property_images
    $draft_image_dir = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/draft/';
    $property_image_dir = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/property_images/';
    $db_path_prefix = 'storage/uploads/property_images/';

    if (!is_dir($property_image_dir)) mkdir($property_image_dir, 0777, true);

    $images = json_decode($draft['images'] ?? '[]', true);
    if (!is_array($images)) $images = [];

    $stmtInsertImg = $pdo->prepare("
        INSERT INTO property_images (property_id, image_path, is_primary, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    foreach ($images as $index => $imgPath) {
        $old_path = 'C:/xampp/htdocs/BatEstateExplorer/' . $imgPath;

        if (!file_exists($old_path)) continue;

        $ext = pathinfo($old_path, PATHINFO_EXTENSION);
        $new_name = uniqid('prop_', true) . '.' . $ext;
        $new_path = $property_image_dir . $new_name;
        $db_path = $db_path_prefix . $new_name;

        if (!rename($old_path, $new_path)) {
            throw new Exception("Failed to move image: " . $imgPath);
        }

        $is_primary = ($index === 0) ? 1 : 0;
        $stmtInsertImg->execute([$property_id, $db_path, $is_primary]);
    }

    // 8. Delete draft record
    $stmtDelDraft = $pdo->prepare("DELETE FROM property_drafts WHERE id = ?");
    $stmtDelDraft->execute([$draft_id]);

    // 9. Commit
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Draft published successfully!',
        'property_id' => $property_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
