<?php
// user_search.php
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../components/agent_property_card.php';

// Determine request type (AJAX vs normal page load)
$isAjax = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (!empty($_POST['ajax']) && $_POST['ajax'] === '1');

// Get filter values from request
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

// Filters
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
            $sql .= " AND p.price BETWEEN ? AND ?";
            $params[] = (float) trim($parts[0]);
            $params[] = (float) trim($parts[1]);
            $types .= "dd";
        }
    }
}
if ($bedrooms !== '') {
    $sql .= " AND p.bedrooms >= ?";
    $params[] = (int) $bedrooms;
    $types .= "i";
}
if ($bathrooms !== '') {
    $sql .= " AND p.bathrooms >= ?";
    $params[] = (int) $bathrooms;
    $types .= "i";
}
if ($size !== '') {
    if ($size === '200+') {
        $sql .= " AND p.sqm >= 200";
    } else {
        $parts = explode('-', $size);
        if (count($parts) === 2) {
            $sql .= " AND p.sqm BETWEEN ? AND ?";
            $params[] = (float) trim($parts[0]);
            $params[] = (float) trim($parts[1]);
            $types .= "dd";
        }
    }
}

$sql .= " ORDER BY p.created_at DESC";

// Prepare statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo "<p>Server error (prepare failed).</p>";
    exit;
}

// Bind parameters if any
if (!empty($params)) {
    $bind_names = [$types];
    foreach ($params as $key => &$value) {
        $bind_names[] = &$value;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

// Execute query
$stmt->execute();
$result = $stmt->get_result();
$properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// --- DO NOT close the connection here ---
// $stmt->close();
// $conn->close();

// Now we can safely render cards
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/associate_search.css">

<!-- Keep this part inside the same file for initial load -->
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


    <div class="properties-grid" id="propertiesGrid">
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property): ?>
                <?php render_agent_property_card($property); ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>

    <?php
    // Render the modal only once
    render_agent_property_card([], true);
    ?>


<!-- Swiper CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css"/>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script src="/BatEstateExplorer/assets/js/agent_property_card_logic.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Update the displayed value when a select changes
    document.querySelectorAll('.search-field select').forEach(function (selectEl) {
        const valueSpan = selectEl.closest('.search-field').querySelector('.value');

        // Set initial display
        valueSpan.textContent = selectEl.value === "" 
            ? valueSpan.dataset.default 
            : selectEl.options[selectEl.selectedIndex].text;

        // Update on change
        selectEl.addEventListener('change', function () {
            const span = this.closest('.search-field').querySelector('.value');
            span.textContent = this.value === "" 
                ? span.dataset.default 
                : this.options[this.selectedIndex].text;
        });
    });

    // Handle search button click
    const searchBtn = document.getElementById('searchForm1');
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            const params = ['location', 'property_type', 'price_range', 'bedrooms', 'bathrooms', 'size']
                .reduce((obj, id) => {
                    obj[id] = document.getElementById(id).value;
                    return obj;
                }, {});

            const query = new URLSearchParams(params).toString();

            // Fetch filtered data (API handles it)
            fetch('/BatEstateExplorer/public/api/get_properties.php?' + query)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not OK');
                    return res.json();
                })
                .then(data => {
                    const grid = document.getElementById('propertiesGrid');
                    if (!data.properties || data.properties.length === 0) {
                        grid.innerHTML = '<p>No properties available at the moment.</p>';
                    } else {
                        console.log('Properties fetched:', data.properties.length);
                    }
                })
                .catch(err => console.error('Fetch error:', err));
        });
    }
});
</script>
