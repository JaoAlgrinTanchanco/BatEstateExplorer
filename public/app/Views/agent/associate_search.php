<?php
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../components/agent_property_card.php';

// Detect AJAX
$isAjax = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (!empty($_POST['ajax']) && $_POST['ajax'] === '1');

// Get filter values
$request = $isAjax ? $_POST : $_GET;
$location      = $request['location'] ?? '';
$property_type = $request['property_type'] ?? '';
$price_range   = $request['price_range'] ?? '';
$bedrooms      = $request['bedrooms'] ?? '';
$bathrooms     = $request['bathrooms'] ?? '';
$size          = $request['size'] ?? '';

// Build SQL query
$sql = "SELECT p.*, pi.image_path
        FROM properties p
        LEFT JOIN property_images pi 
          ON p.id = pi.property_id AND pi.is_primary = 1
        WHERE 1=1";

$params = [];
$types = "";

// Apply filters only if set
if ($location !== '') { $sql .= " AND p.location = ?"; $params[] = $location; $types .= "s"; }
if ($property_type !== '') { $sql .= " AND p.property_type = ?"; $params[] = $property_type; $types .= "s"; }
if ($bedrooms !== '') { $sql .= " AND p.bedrooms >= ?"; $params[] = (int)$bedrooms; $types .= "i"; }
if ($bathrooms !== '') { $sql .= " AND p.bathrooms >= ?"; $params[] = (int)$bathrooms; $types .= "i"; }

// Price filter
if ($price_range !== '') {
    if ($price_range === '5000000+') {
        $sql .= " AND p.price >= 5000000";
    } elseif (strpos($price_range, '-') !== false) {
        [$min, $max] = array_map('floatval', explode('-', $price_range));
        $sql .= " AND p.price BETWEEN ? AND ?";
        $params[] = $min;
        $params[] = $max;
        $types .= "dd";
    }
}

// Size filter
if ($size !== '') {
    if ($size === '200+') {
        $sql .= " AND p.sqm >= 200";
    } elseif (strpos($size, '-') !== false) {
        [$min, $max] = array_map('floatval', explode('-', $size));
        $sql .= " AND p.sqm BETWEEN ? AND ?";
        $params[] = $min;
        $params[] = $max;
        $types .= "dd";
    }
}

$sql .= " ORDER BY p.created_at DESC";

// Prepare statement
$stmt = $conn->prepare($sql);
if ($stmt === false) { echo "<p>Server error.</p>"; exit; }

// Bind parameters
if (!empty($params)) {
    $bind_names = [$types];
    foreach ($params as &$val) $bind_names[] = &$val;
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

// Execute
$stmt->execute();
$result = $stmt->get_result();
$properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/associate_search.css">

<div class="search-container">
    <div class="search">
        <div class="search-form">
            <?php
            $filters = [
                'location' => ['label'=>'Location', 'options'=>[''=>'All Locations','Batangas City'=>'Batangas City','Lipa City'=>'Lipa City','Tanauan City'=>'Tanauan City']],
                'property_type' => ['label'=>'Property Type', 'options'=>[''=>'All Types','Property'=>'Property']],
                'price_range' => ['label'=>'Price Range', 'options'=>[''=>'Any Price','0-1000000'=>'₱0 - ₱1M','1000000-5000000'=>'₱1M - ₱5M','5000000+'=>'₱5M+']],
                'bedrooms' => ['label'=>'Bedrooms', 'options'=>[''=>'Any','1'=>'1+','2'=>'2+','3'=>'3+','4'=>'4+']],
                'bathrooms' => ['label'=>'Bathrooms', 'options'=>[''=>'Any','1'=>'1+','2'=>'2+','3'=>'3+','4'=>'4+']],
                'size' => ['label'=>'Size (sqm)', 'options'=>[''=>'Any Size','0-50'=>'Up to 50 sqm','50-100'=>'50-100 sqm','100-200'=>'100-200 sqm','200+'=>'200+ sqm']]
            ];

            foreach ($filters as $id => $data):
            ?>
                <button type="button" class="search-field" data-field="<?= $id ?>">
                    <span class="label"><?= $data['label'] ?></span>
                    <span class="value" data-default="<?= reset($data['options']) ?>"><?= ${$id} !== '' ? htmlspecialchars(${$id}) : reset($data['options']) ?></span>
                    <select name="<?= $id ?>" id="<?= $id ?>">
                        <?php foreach($data['options'] as $val => $text): ?>
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

    <div class="properties-grid" id="propertiesGrid">
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property):
                $property['data_type'] = $property['property_type'];
                $property['data_size'] = $property['sqm'];
                render_agent_property_card($property);
            endforeach; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>

    <?php render_agent_property_card([], true); ?>
</div>

<script src="/BatEstateExplorer/assets/js/agent_property_card_logic.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Update select labels
    document.querySelectorAll('.search-field select').forEach(selectEl => {
        const valueSpan = selectEl.closest('.search-field').querySelector('.value');
        const update = () => {
            valueSpan.textContent = selectEl.value === "" ? valueSpan.dataset.default : selectEl.options[selectEl.selectedIndex].text;
        };
        update();
        selectEl.addEventListener('change', update);
    });

    // Front-end filter function
    const filterProperties = () => {
        const location = document.getElementById('location').value.toLowerCase();
        const property_type = document.getElementById('property_type').value.toLowerCase();
        const price_range = document.getElementById('price_range').value;
        const bedrooms = document.getElementById('bedrooms').value;
        const bathrooms = document.getElementById('bathrooms').value;
        const size = document.getElementById('size').value;

        document.querySelectorAll('#propertiesGrid .property-card').forEach(card => {
            let show = true;

            const cardLocation = card.querySelector('.property-location')?.textContent.toLowerCase() || '';
            const cardPrice = parseFloat((card.querySelector('.property-price')?.textContent || '0').replace(/[₱,]/g,'')) || 0;
            const cardBedrooms = parseInt(card.querySelector('.property-features span:first-child')?.textContent) || 0;
            const cardBathrooms = parseInt(card.querySelector('.property-features span:nth-child(2)')?.textContent) || 0;
            const cardSize = parseInt(card.dataset.size) || 0;
            const cardType = (card.dataset.type || '').toLowerCase();

            // Location filter
            if(location && !cardLocation.includes(location)) show = false;

            // Type filter
            if(property_type && cardType !== property_type) show = false;

            // Price filter
            if(price_range){
                if(price_range.includes('-')){
                    let [min,max] = price_range.split('-').map(Number);
                    if(cardPrice < min || cardPrice > max) show = false;
                } else if(price_range.endsWith('+')){
                    let min = parseInt(price_range);
                    if(cardPrice < min) show = false;
                }
            }

            // Bedrooms
            if(bedrooms && cardBedrooms < parseInt(bedrooms)) show = false;

            // Bathrooms
            if(bathrooms && cardBathrooms < parseInt(bathrooms)) show = false;

            // Size
            if(size){
                if(size.includes('-')){
                    let [min,max] = size.split('-').map(Number);
                    if(cardSize < min || cardSize > max) show = false;
                } else if(size.endsWith('+')){
                    let min = parseInt(size);
                    if(cardSize < min) show = false;
                }
            }

            card.style.display = show ? '' : 'none';
        });

        // Show message if no cards visible
        const anyVisible = [...document.querySelectorAll('#propertiesGrid .property-card')].some(c=>c.style.display !== 'none');
        const grid = document.getElementById('propertiesGrid');
        if(!anyVisible){
            grid.querySelector('.no-results') 
                ? grid.querySelector('.no-results').style.display = ''
                : grid.insertAdjacentHTML('beforeend','<p class="no-results">No properties match your filters.</p>');
        } else {
            const msg = grid.querySelector('.no-results');
            if(msg) msg.style.display = 'none';
        }
    };

    // Bind filter on click
    document.getElementById('searchForm1').addEventListener('click', filterProperties);
});
</script>
