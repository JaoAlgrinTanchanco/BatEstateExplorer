<?php
// DEBUG MODE
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/pdo_database.php';
session_start();

// ✅ Get logged-in user
$user_data = get_logged_in_user($pdo);
if (!$user_data) {
    die("❌ No logged in user detected.");
}

// ✅ Only associates can save listings
if ($user_data['user_type'] !== 'associate_agent') {
    die("❌ Access denied: only associates can save listings. Your type is: " . $user_data['user_type']);
}

try {
    // ✅ Fetch or create agent row in `agents`
    $stmt = $pdo->prepare("SELECT id FROM agents WHERE user_id = ?");
    $stmt->execute([$user_data['id']]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        $stmtInsert = $pdo->prepare("INSERT INTO agents (user_id, created_at) VALUES (?, NOW())");
        $stmtInsert->execute([$user_data['id']]);
        $agent_id = $pdo->lastInsertId();
        echo "🆕 Agent record created for user_id {$user_data['id']} → Agent ID: {$agent_id}<br>";
    } else {
        $agent_id = $agent['id'];
        echo "✅ Using existing agent record: Agent ID {$agent_id}<br>";
    }

    // ✅ Collect form data
    $title         = $_POST['title'] ?? '';
    $description   = $_POST['description'] ?? '';
    $price         = floatval($_POST['price'] ?? 0);
    $location      = $_POST['location'] ?? '';
    $bedrooms      = intval($_POST['bedrooms'] ?? 0);
    $bathrooms     = intval($_POST['bathrooms'] ?? 0);
    $sqm           = floatval($_POST['sqm'] ?? 0);
    $lot_size      = floatval($_POST['lot_size'] ?? 0);
    $property_type = $_POST['property_type'] ?? '';

    $pdo->beginTransaction();

    // ✅ Insert property linked to agents.id
    $stmt = $pdo->prepare("
        INSERT INTO properties
        (title, description, property_type, location, price, bedrooms, bathrooms, sqm, lot_size, agent_id, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $title, $description, $property_type, $location, $price,
        $bedrooms, $bathrooms, $sqm, $lot_size, $agent_id
    ]);

    $property_id = $pdo->lastInsertId();
    echo "✅ Property inserted with ID: {$property_id}<br>";

    // ✅ Handle images
    $upload_dir = 'C:\\xampp\\htdocs\\BatEstateExplorer\\storage\\uploads\\property_images\\';
    if (!is_dir($upload_dir)) {
        throw new Exception("❌ Upload directory missing: " . $upload_dir);
    }
    if (!is_writable($upload_dir)) {
        throw new Exception("❌ Upload directory not writable: " . $upload_dir);
    }

    if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name']) && $_FILES['images']['tmp_name'][0] !== '') {
        $file_count = min(count($_FILES['images']['tmp_name']), 10);
        echo "📸 Processing {$file_count} images<br>";

        for ($i = 0; $i < $file_count; $i++) {
            $tmp   = $_FILES['images']['tmp_name'][$i];
            $name  = $_FILES['images']['name'][$i];
            $error = $_FILES['images']['error'][$i];

            if ($error !== UPLOAD_ERR_OK) {
                echo "❌ Error $error for file {$name}<br>";
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                echo "❌ Invalid file extension for {$name}<br>";
                continue;
            }

            $newFileName = uniqid() . '.' . $ext;
            $destination = $upload_dir . $newFileName;

            if (move_uploaded_file($tmp, $destination)) {
                echo "✅ Uploaded {$name} → {$newFileName}<br>";
                $relativePath = 'storage/uploads/property_images/' . $newFileName;

                $stmtImg = $pdo->prepare("INSERT INTO property_images (property_id, image_path) VALUES (?, ?)");
                $stmtImg->execute([$property_id, $relativePath]);
                echo "✅ Image saved in DB: {$relativePath}<br>";
            } else {
                echo "❌ Failed to move {$name} to {$destination}<br>";
            }
        }
    } else {
        echo "⚠ No images uploaded.<br>";
    }

    $pdo->commit();
    echo "✅ Transaction committed.<br>";

} catch (Exception $e) {
    $pdo->rollBack();
    die("❌ ERROR: " . $e->getMessage());
}
