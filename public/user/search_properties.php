<?php
// public/search_properties.php
require_once __DIR__ . '/../app/config/database.php';

// Check if AJAX request (optional, but good practice)
$isAjax = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (!empty($_GET['ajax']) && $_GET['ajax'] == '1');

// Accept incoming GET params (for fetch GET requests)
$location = $_GET['location'] ?? '';
$property_type = $_GET['property_type'] ?? '';
$price_range = $_GET['price_range'] ?? '';
$bedrooms = $_GET['bedrooms'] ?? '';
$bathrooms = $_GET['bathrooms'] ?? '';
$size = $_GET['size'] ?? '';

// Build query and params (same logic as yours)
$sql = "
    SELECT p.*, pi.image_path
    FROM properties p
    LEFT JOIN property_images pi 
        ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE 1=1
";
$params = [];
$types = "";

if ($location !== '') {
    $sql .= " AND p.location = ?";
    $params[] = $location;
    $types .= "s";
}

if ($property_type !== '') {
    $sql .= " AND p.property_type = ?";
    $params[] = $property_type;
    $types .= "s";
}

if ($price_range !== '') {
    if ($price_range === '5000000+') {
        $sql .= " AND p.price >= 5000000";
    } else {
        $parts = explode('-', $price_range);
        if (count($parts) === 2) {
            $min = (float) $parts[0];
            $max = (float) $parts[1];
            $sql .= " AND p.price BETWEEN ? AND ?";
            $params[] = $min;
            $params[] = $max;
            $types .= "dd";
        }
    }
}

if ($bedrooms !== '') {
    $sql .= " AND p.bedrooms >= ?";
    $params[] = (int)$bedrooms;
    $types .= "i";
}

if ($bathrooms !== '') {
    $sql .= " AND p.bathrooms >= ?";
    $params[] = (int)$bathrooms;
    $types .= "i";
}

if ($size !== '') {
    if ($size === '200+') {
        $sql .= " AND p.sqm >= 200";
    } else {
        $parts = explode('-', $size);
        if (count($parts) === 2) {
            $min_sqm = (float) $parts[0];
            $max_sqm = (float) $parts[1];
            $sql .= " AND p.sqm BETWEEN ? AND ?";
            $params[] = $min_sqm;
            $params[] = $max_sqm;
            $types .= "dd";
        }
    }
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo "Server error (prepare failed).";
    exit;
}

if (!empty($params)) {
    $bind_names = [];
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

$stmt->execute();
$result = $stmt->get_result();
$properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
$conn->close();

// Output only the HTML for the properties grid (for AJAX)
if (!empty($properties)) {
    foreach ($properties as $property) {
        ?>
        <div class="property-card">
            <div class="property-image">
                <?php if (!empty($property['image_path'])): ?>
                    <img src="<?= htmlspecialchars($property['image_path']) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                <?php else: ?>
                    <img src="assets/images/default-property.jpg" alt="No image available">
                <?php endif; ?>
            </div>
            <div class="property-content">
                <h3><?= htmlspecialchars($property['title']) ?></h3>
                <p class="property-location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?></p>
                <p class="property-price">₱<?= number_format($property['price'], 2) ?></p>
                <div class="property-features">
                    <span><i class="fas fa-bed"></i> <?= (int)$property['bedrooms'] ?> Beds</span>
                    <span><i class="fas fa-bath"></i> <?= (int)$property['bathrooms'] ?> Baths</span>
                </div>
                <a href="property_details.php?id=<?= (int)$property['id'] ?>" class="btn btn-outline">View Details</a>
            </div>
        </div>
        <?php
    }
} else {
    echo "<p>No properties available at the moment.</p>";
}
