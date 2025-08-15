<?php
// save_listing.php
require_once __DIR__ . '/../../config/pdo_database.php';
session_start();

// Redirect location after save
$redirect_url = "/BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile&tab=add_listing";

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect_url");
    exit;
}

// Get logged-in user
$user_data = get_logged_in_user($pdo);
if (!$user_data) {
    header("Location: $redirect_url");
    exit;
}

// Get agent_id linked to this user
$stmtAgent = $pdo->prepare("SELECT id FROM agents WHERE user_id = ?");
$stmtAgent->execute([$user_data['id']]);
$agent = $stmtAgent->fetch();

if (!$agent) {
    header("Location: $redirect_url");
    exit;
}
$agent_id = $agent['id'];

// Collect inputs
$title         = sanitize_input($_POST['title'] ?? '');
$description   = sanitize_input($_POST['description'] ?? '');
$price         = floatval($_POST['price'] ?? 0);
$location      = sanitize_input($_POST['location'] ?? '');
$bedrooms      = intval($_POST['bedrooms'] ?? 0);
$bathrooms     = intval($_POST['bathrooms'] ?? 0);
$sqm           = floatval($_POST['sqm'] ?? 0);
$lot_size      = floatval($_POST['lot_size'] ?? 0);
$property_type = sanitize_input($_POST['property_type'] ?? '');

// Validation (basic required fields)
if (!$title || !$description || !$price || !$location || !$property_type) {
    header("Location: $redirect_url");
    exit;
}

try {
    $pdo->beginTransaction();

    // Handle file uploads
    $image_files = [];
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = __DIR__ . '/../../uploads/listings/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_count = min(count($_FILES['images']['name']), 10);
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        for ($i = 0; $i < $file_count; $i++) {
            $file_name = basename($_FILES['images']['name'][$i]);
            $file_tmp  = $_FILES['images']['tmp_name'][$i];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_ext)) {
                $new_name = uniqid('listing_', true) . '.' . $file_ext;
                $dest_path = $upload_dir . $new_name;

                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $image_files[] = $new_name;
                }
            }
        }
    }

    // Insert listing with images stored as JSON
    $stmt = $pdo->prepare("
        INSERT INTO properties 
        (title, description, property_type, location, price, bedrooms, bathrooms, sqm, lot_size, agent_id, status, images, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', ?, NOW())
    ");
    $stmt->execute([
        $title,
        $description,
        $property_type,
        $location,
        $price,
        $bedrooms,
        $bathrooms,
        $sqm,
        $lot_size,
        $agent_id,
        json_encode($image_files)
    ]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
}

// Redirect back regardless of outcome
header("Location: $redirect_url");
exit;
