<?php
// DEBUG MODE
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/pdo_database.php';
session_start();

// Get logged-in user
$user_data = get_logged_in_user($pdo);
if (!$user_data) {
    die("❌ No logged in user detected.");
}

// Ensure only direct agents can use this
if ($user_data['user_type'] !== 'direct_agent') {
    die("❌ Access denied: only direct agents can save listings.");
}

// ✅ Get or create agent.id from agents table
$stmt = $pdo->prepare("SELECT id FROM agents WHERE user_id = ?");
$stmt->execute([$user_data['id']]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agent) {
    // 🔧 Auto-create agent record for direct agent
    $stmtInsert = $pdo->prepare("INSERT INTO agents (user_id, created_at) VALUES (?, NOW())");
    $stmtInsert->execute([$user_data['id']]);

    $agent_id = $pdo->lastInsertId();
    echo "🆕 Agent record created for user_id {$user_data['id']} → Agent ID: $agent_id<br>";
} else {
    $agent_id = $agent['id'];
    echo "✅ Agent ID (from agents table): $agent_id<br>";
}

// Collect form data
$title         = $_POST['title'] ?? '';
$description   = $_POST['description'] ?? '';
$price         = floatval($_POST['price'] ?? 0);
$location      = $_POST['location'] ?? '';
$bedrooms      = intval($_POST['bedrooms'] ?? 0);
$bathrooms     = intval($_POST['bathrooms'] ?? 0);
$sqm           = floatval($_POST['sqm'] ?? 0);
$lot_size      = floatval($_POST['lot_size'] ?? 0);
$property_type = $_POST['property_type'] ?? '';

try {
    $pdo->beginTransaction();

    // ✅ Insert property using agent_id
    $stmt = $pdo->prepare("
        INSERT INTO properties
        (title, description, property_type, location, price, bedrooms, bathrooms, sqm, lot_size, agent_id, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())
    ");
    $stmt->execute([
        $title, $description, $property_type, $location, $price,
        $bedrooms, $bathrooms, $sqm, $lot_size, $agent_id
    ]);

    $property_id = $pdo->lastInsertId();
    echo "✅ Property inserted with ID: $property_id<br>";

    // Path to uploads
    $upload_dir = 'C:\\xampp\\htdocs\\BatEstateExplorer\\storage\\uploads\\property_images\\';
    echo "📂 Upload directory: $upload_dir<br>";

    if (!is_dir($upload_dir)) {
        error_log("❌ Upload directory does not exist: " . $upload_dir);
    } elseif (!is_writable($upload_dir)) {
        error_log("❌ Upload directory is not writable: " . $upload_dir);
    } else {
        error_log("✅ Upload directory ready: " . $upload_dir);
    }

    echo '<pre>'; print_r($_FILES); echo '</pre>';

    if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name']) && $_FILES['images']['tmp_name'][0] !== '') {
        $file_count = min(count($_FILES['images']['tmp_name']), 10);
        echo "📸 Number of files to process: $file_count<br>";

        for ($i = 0; $i < $file_count; $i++) {
            $tmp   = $_FILES['images']['tmp_name'][$i];
            $name  = $_FILES['images']['name'][$i];
            $error = $_FILES['images']['error'][$i];

            echo "🔍 File $i: name=$name, tmp=$tmp, error=$error<br>";

            if ($error !== UPLOAD_ERR_OK) {
                echo "❌ Upload error code $error for file $name<br>";
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                echo "❌ Invalid file extension for $name<br>";
                continue;
            }

            $newFileName = uniqid() . '.' . $ext;
            $destination = $upload_dir . $newFileName;

            if (move_uploaded_file($tmp, $destination)) {
                echo "✅ File moved to $destination<br>";
                $relativePath = 'storage/uploads/property_images/' . $newFileName;

                $stmtImg = $pdo->prepare("INSERT INTO property_images (property_id, image_path) VALUES (?, ?)");
                $stmtImg->execute([$property_id, $relativePath]);
                echo "✅ DB record inserted for image: $relativePath<br>";
            } else {
                echo "❌ Failed to move $name to $destination<br>";
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
