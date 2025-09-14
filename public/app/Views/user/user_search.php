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

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/user_search.css">

<!-- === Search Form === -->
<div class="search-container">
    <div class="search">
        <div class="search-form">
            <?php
            $filters = [
                'location' => [
                    'label' => 'Location',
                    'options' => [
                        '' => 'All Locations',
                        'Agoncillo'=>'Agoncillo','Alitagtag'=>'Alitagtag','Balayan'=>'Balayan','Balete'=>'Balete',
                        'Batangas City'=>'Batangas City','Bauan'=>'Bauan','Calaca'=>'Calaca','Calatagan'=>'Calatagan',
                        'Cuenca'=>'Cuenca','Ibaan'=>'Ibaan','Laurel'=>'Laurel','Lemery'=>'Lemery','Lian'=>'Lian',
                        'Lipa City'=>'Lipa City','Lobo'=>'Lobo','Mabini'=>'Mabini','Malvar'=>'Malvar',
                        'Mataasnakahoy'=>'Mataasnakahoy','Nasugbu'=>'Nasugbu','Padre Garcia'=>'Padre Garcia',
                        'Rosario'=>'Rosario','San Jose'=>'San Jose','San Juan'=>'San Juan','San Luis'=>'San Luis',
                        'San Nicolas'=>'San Nicolas','San Pascual'=>'San Pascual','Santa Teresita'=>'Santa Teresita',
                        'Santo Tomas'=>'Santo Tomas','Taal'=>'Taal','Talisay'=>'Talisay','Tanauan City'=>'Tanauan City',
                        'Taysan'=>'Taysan','Tingloy'=>'Tingloy','Tuy'=>'Tuy'
                    ]
                ],
                'property_type' => [
                    'label'=>'Property Type',
                    'options'=>[''=>'All Types','Property'=>'property','Lot'=>'Lot']
                ],
                'price_range' => [
                    'label'=>'Price Range',
                    'options'=>[
                        ''=>'Any Price',
                        '0-500000'=>'₱0 - ₱500K',
                        '500000-1500000'=>'₱500K - ₱1.5M',
                        '1500000-3000000'=>'₱1.5M - ₱3M',
                        '3000000-5000000'=>'₱3M - ₱5M',
                        '5000000+'=>'₱5M+'
                    ]
                ],
                'bedrooms'=>['label'=>'Bedrooms','options'=>[''=>'Any','1'=>'1','2'=>'2','3'=>'3','4'=>'4+']],
                'bathrooms'=>['label'=>'Bathrooms','options'=>[''=>'Any','1'=>'1','2'=>'2','3'=>'3','4'=>'4+']],
                'size'=>['label'=>'Size (sqm)','options'=>[''=>'Any Size','0-50'=>'Up to 50 sqm','50-100'=>'50-100 sqm','100-200'=>'100-200 sqm','200+'=>'200+ sqm']]
            ];

            foreach ($filters as $id => $data):
            ?>
                <button type="button" class="search-field" data-field="<?= $id ?>">
                    <span class="label"><?= $data['label'] ?></span>
                    <span class="value" data-default="<?= reset($data['options']) ?>">
                        <?= ${$id} !== '' ? htmlspecialchars(${$id}) : reset($data['options']) ?>
                    </span>
                    <select name="<?= $id ?>" id="<?= $id ?>">
                        <?php foreach ($data['options'] as $val => $text): ?>
                            <option value="<?= $val ?>" <?= ${$id} === $val ? 'selected' : '' ?>><?= $text ?></option>
                        <?php endforeach; ?>
                    </select>
                </button>
            <?php endforeach; ?>

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
