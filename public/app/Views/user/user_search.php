<?php
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../components/user_property_card.php';

// Decide if AJAX
$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
    || (!empty($_POST['ajax']) && $_POST['ajax'] == '1')
);

$location = $isAjax ? ($_POST['location'] ?? '') : ($_GET['location'] ?? '');
$property_type = $isAjax ? ($_POST['property_type'] ?? '') : ($_GET['property_type'] ?? '');
$price_range = $isAjax ? ($_POST['price_range'] ?? '') : ($_GET['price_range'] ?? '');
$bedrooms = $isAjax ? ($_POST['bedrooms'] ?? '') : ($_GET['bedrooms'] ?? '');
$bathrooms = $isAjax ? ($_POST['bathrooms'] ?? '') : ($_GET['bathrooms'] ?? '');
$size = $isAjax ? ($_POST['size'] ?? '') : ($_GET['size'] ?? '');

// === Query to fetch properties ===
$sql = "SELECT p.*, pi.image_path 
        FROM properties p
        LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
        WHERE 1=1";
$params = [];
$types = "";

// Apply filters
if ($location !== '') { $sql .= " AND p.location = ?"; $params[] = $location; $types .= "s"; }
if ($property_type !== '') { $sql .= " AND p.property_type = ?"; $params[] = $property_type; $types .= "s"; }
if ($price_range !== '') {
    if ($price_range === '5000000+') $sql .= " AND p.price >= 5000000";
    else {
        $parts = explode('-', $price_range);
        if (count($parts) === 2) {
            $sql .= " AND p.price BETWEEN ? AND ?";
            $params[] = (float)$parts[0];
            $params[] = (float)$parts[1];
            $types .= "dd";
        }
    }
}
if ($bedrooms !== '') { $sql .= " AND p.bedrooms >= ?"; $params[] = (int)$bedrooms; $types .= "i"; }
if ($bathrooms !== '') { $sql .= " AND p.bathrooms >= ?"; $params[] = (int)$bathrooms; $types .= "i"; }
if ($size !== '') {
    if ($size === '200+') $sql .= " AND p.sqm >= 200";
    else {
        $parts = explode('-', $size);
        if (count($parts) === 2) {
            $sql .= " AND p.sqm BETWEEN ? AND ?";
            $params[] = (float)$parts[0];
            $params[] = (float)$parts[1];
            $types .= "dd";
        }
    }
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $bind_names = [];
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) $bind_names[] = &$params[$i];
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}
$stmt->execute();
$result = $stmt->get_result();
$properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// --- If AJAX, return only the property cards HTML ---
if ($isAjax) {
    if (!empty($properties)) {
        foreach ($properties as $property) {
            // Prepare images for render_property_card
            $stmtImg = $conn->prepare("SELECT image_path FROM property_images WHERE property_id = ? ORDER BY id ASC");
            $stmtImg->bind_param("i", $property['id']);
            $stmtImg->execute();
            $resImg = $stmtImg->get_result();
            $images = [];
            while ($row = $resImg->fetch_assoc()) $images[] = '/' . ltrim($row['image_path'], '/');
            $stmtImg->close();
            if (empty($images)) $images[] = '/BatEstateExplorer/assets/images/bg4.jpg';
            $property['images'] = $images;

            render_property_card($property);
        }
    } else {
        echo '<p>No properties match your filters.</p>';
    }
    $conn->close();
    exit; // stop further output
}
?>

<!-- <link rel="stylesheet" href="/BatEstateExplorer/assets/css/user_search.css"> -->

<!-- === Search Form === -->
<div class="search-container">
    <div class="search">
        <div class="search-form">
            <!-- Location -->
            <button type="button" class="search-field" data-field="location">
                <span class="label">Location</span>
                <span class="value" data-default="All Locations"><?= $location !== '' ? htmlspecialchars($location) : 'All Locations' ?></span>
                <select name="location" id="location">
                    <option value="" <?= $location === '' ? 'selected' : '' ?>>All Locations</option>
                    <option value="Batangas City" <?= $location === 'Batangas City' ? 'selected' : '' ?>>Batangas City</option>
                    <option value="Lipa City" <?= $location === 'Lipa City' ? 'selected' : '' ?>>Lipa City</option>
                    <option value="Tanauan City" <?= $location === 'Tanauan City' ? 'selected' : '' ?>>Tanauan City</option>
                </select>
            </button>

            <!-- Property Type -->
            <button type="button" class="search-field" data-field="property_type">
                <span class="label">Property Type</span>
                <span class="value" data-default="All Types"><?= $property_type !== '' ? htmlspecialchars($property_type) : 'All Types' ?></span>
                <select name="property_type" id="property_type">
                    <option value="" <?= $property_type === '' ? 'selected' : '' ?>>All Types</option>
                    <option value="house" <?= $property_type === 'house' ? 'selected' : '' ?>>House</option>
                    <option value="condo" <?= $property_type === 'condo' ? 'selected' : '' ?>>Condominium</option>
                    <option value="land" <?= $property_type === 'land' ? 'selected' : '' ?>>Land</option>
                </select>
            </button>

            <!-- Price Range -->
            <button type="button" class="search-field" data-field="price_range">
                <span class="label">Price Range</span>
                <span class="value" data-default="Any Price"><?= $price_range !== '' ? htmlspecialchars($price_range) : 'Any Price' ?></span>
                <select name="price_range" id="price_range">
                    <option value="" <?= $price_range === '' ? 'selected' : '' ?>>Any Price</option>
                    <option value="0-1000000" <?= $price_range === '0-1000000' ? 'selected' : '' ?>>Under ₱1M</option>
                    <option value="1000000-5000000" <?= $price_range === '1000000-5000000' ? 'selected' : '' ?>>₱1M - ₱5M</option>
                    <option value="5000000+" <?= $price_range === '5000000+' ? 'selected' : '' ?>>₱5M+</option>
                </select>
            </button>

            <!-- Bedrooms -->
            <button type="button" class="search-field" data-field="bedrooms">
                <span class="label">Bedrooms</span>
                <span class="value" data-default="Any"><?= $bedrooms !== '' ? htmlspecialchars($bedrooms) . '+' : 'Any' ?></span>
                <select name="bedrooms" id="bedrooms">
                    <option value="" <?= $bedrooms === '' ? 'selected' : '' ?>>Any</option>
                    <option value="1" <?= $bedrooms === '1' ? 'selected' : '' ?>>1+</option>
                    <option value="2" <?= $bedrooms === '2' ? 'selected' : '' ?>>2+</option>
                    <option value="3" <?= $bedrooms === '3' ? 'selected' : '' ?>>3+</option>
                    <option value="4" <?= $bedrooms === '4' ? 'selected' : '' ?>>4+</option>
                </select>
            </button>

            <!-- Bathrooms -->
            <button type="button" class="search-field" data-field="bathrooms">
                <span class="label">Bathrooms</span>
                <span class="value" data-default="Any"><?= $bathrooms !== '' ? htmlspecialchars($bathrooms) . '+' : 'Any' ?></span>
                <select name="bathrooms" id="bathrooms">
                    <option value="" <?= $bathrooms === '' ? 'selected' : '' ?>>Any</option>
                    <option value="1" <?= $bathrooms === '1' ? 'selected' : '' ?>>1+</option>
                    <option value="2" <?= $bathrooms === '2' ? 'selected' : '' ?>>2+</option>
                    <option value="3" <?= $bathrooms === '3' ? 'selected' : '' ?>>3+</option>
                    <option value="4" <?= $bathrooms === '4' ? 'selected' : '' ?>>4+</option>
                </select>
            </button>

            <!-- Size -->
            <button type="button" class="search-field" data-field="size">
                <span class="label">Size (sqm)</span>
                <span class="value" data-default="Any Size"><?= $size !== '' ? htmlspecialchars($size) : 'Any Size' ?></span>
                <select name="size" id="size">
                    <option value="" <?= $size === '' ? 'selected' : '' ?>>Any Size</option>
                    <option value="0-50" <?= $size === '0-50' ? 'selected' : '' ?>>Up to 50 sqm</option>
                    <option value="50-100" <?= $size === '50-100' ? 'selected' : '' ?>>50 - 100 sqm</option>
                    <option value="100-200" <?= $size === '100-200' ? 'selected' : '' ?>>100 - 200 sqm</option>
                    <option value="200+" <?= $size === '200+' ? 'selected' : '' ?>>200+ sqm</option>
                </select>
            </button>

            <!-- Submit -->
            <button id="searchForm1" class="user-search-submit" aria-label="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
    </div>
    <!-- === Properties Grid === -->
    <div class="properties-grid" id="propertiesGrid">
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property): ?>
                <?php
                    // Prepare images for render_property_card
                    $stmtImg = $conn->prepare("SELECT image_path FROM property_images WHERE property_id = ? ORDER BY id ASC");
                    $stmtImg->bind_param("i", $property['id']);
                    $stmtImg->execute();
                    $resImg = $stmtImg->get_result();
                    $images = [];
                    while ($row = $resImg->fetch_assoc()) $images[] = '/' . ltrim($row['image_path'], '/');
                    $stmtImg->close();
                    if (empty($images)) $images[] = '/BatEstateExplorer/assets/images/bg4.jpg';
                    $property['images'] = $images;

                    render_property_card($property);
                ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>
</div>


<?php $conn->close(); ?>

<script src="/BatEstateExplorer/assets/js/user_search.js"></script>
