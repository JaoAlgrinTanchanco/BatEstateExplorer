<?php
// user_search.php
require_once __DIR__ . '/../../../config/database.php';

// Get filter values safely
$location = $_GET['location'] ?? '';
$property_type = $_GET['property_type'] ?? '';
$price_range = $_GET['price_range'] ?? '';
$bedrooms = $_GET['bedrooms'] ?? '';
$bathrooms = $_GET['bathrooms'] ?? '';
$size = $_GET['size'] ?? '';

// Base query with join to get primary image
$sql = "
    SELECT p.*, pi.image_path
    FROM properties p
    LEFT JOIN property_images pi 
        ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE 1=1
";
$params = [];
$types = "";

// Location
if ($location !== '') {
    $sql .= " AND p.location = ?";
    $params[] = $location;
    $types .= "s";
}

// Property type
if ($property_type !== '') {
    $sql .= " AND p.property_type = ?";
    $params[] = $property_type;
    $types .= "s";
}

// Price range
if ($price_range !== '') {
    if ($price_range === '5000000+') {
        $sql .= " AND p.price >= 5000000";
    } else {
        [$min, $max] = explode('-', $price_range);
        $sql .= " AND p.price BETWEEN ? AND ?";
        $params[] = (int)$min;
        $params[] = (int)$max;
        $types .= "ii";
    }
}

// Bedrooms
if ($bedrooms !== '') {
    $sql .= " AND p.bedrooms >= ?";
    $params[] = (int)$bedrooms;
    $types .= "i";
}

// Bathrooms
if ($bathrooms !== '') {
    $sql .= " AND p.bathrooms >= ?";
    $params[] = (int)$bathrooms;
    $types .= "i";
}

// Size (sqm)
if ($size !== '') {
    if ($size === '200+') {
        $sql .= " AND p.sqm >= 200";
    } else {
        [$min_sqm, $max_sqm] = explode('-', $size);
        $sql .= " AND p.sqm BETWEEN ? AND ?";
        $params[] = (float)$min_sqm;
        $params[] = (float)$max_sqm;
        $types .= "dd";
    }
}

$sql .= " ORDER BY p.created_at DESC";

// Prepare and execute
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$properties = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>


<div class="search-container">
    <div class="search">
        <form class="search-form" method="GET" action="user_dashboard.php?view=search_results">
    <!-- Location -->
    <button type="button" class="search-field" data-field="location">
        <span class="label">Location</span>
        <span class="value">All Locations</span>
        <select name="location" id="location">
            <option value="" selected>All Locations</option>
            <option value="Batangas City">Batangas City</option>
            <option value="Lipa City">Lipa City</option>
            <option value="Tanauan City">Tanauan City</option>
        </select>
    </button>

    <!-- Property Type -->
    <button type="button" class="search-field" data-field="location">
        <span class="label">Location</span>
        <span class="value" data-default="All Locations">All Locations</span>
        <select name="location" id="location">
            <option value="" selected>All Locations</option>
            <option value="Batangas City">Batangas City</option>
            <option value="Lipa City">Lipa City</option>
            <option value="Tanauan City">Tanauan City</option>
        </select>
    </button>

    <!-- Price Range -->
    <button type="button" class="search-field" data-field="price_range">
        <span class="label">Price Range</span>
        <span class="value">Any Price</span>
        <select name="price_range" id="price_range">
            <option value="" selected>Any Price</option>
            <option value="0-1000000">Under ₱1M</option>
            <option value="1000000-5000000">₱1M - ₱5M</option>
            <option value="5000000+">₱5M+</option>
        </select>
    </button>

    <!-- Bedrooms -->
    <button type="button" class="search-field" data-field="bedrooms">
        <span class="label">Bedrooms</span>
        <span class="value">Any</span>
        <select name="bedrooms" id="bedrooms">
            <option value="" selected>Any</option>
            <option value="1">1+</option>
            <option value="2">2+</option>
            <option value="3">3+</option>
            <option value="4">4+</option>
        </select>
    </button>

    <!-- Bathrooms -->
    <button type="button" class="search-field" data-field="bathrooms">
        <span class="label">Bathrooms</span>
        <span class="value">Any</span>
        <select name="bathrooms" id="bathrooms">
            <option value="" selected>Any</option>
            <option value="1">1+</option>
            <option value="2">2+</option>
            <option value="3">3+</option>
            <option value="4">4+</option>
        </select>
    </button>

    <!-- Size -->
    <button type="button" class="search-field" data-field="size">
        <span class="label">Size (sqm)</span>
        <span class="value">Any Size</span>
        <select name="size" id="size">
            <option value="" selected>Any Size</option>
            <option value="0-50">Up to 50 sqm</option>
            <option value="50-100">50 - 100 sqm</option>
            <option value="100-200">100 - 200 sqm</option>
            <option value="200+">200+ sqm</option>
        </select>
    </button>

    <!-- Submit -->
    <button type="submit" class="search-submit" aria-label="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
    </button>
</form>

    </div>

    <div class="properties-grid">
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property): ?>
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
                        <p class="property-location">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?>
                        </p>
                        <p class="property-price">₱<?= number_format($property['price'], 2) ?></p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> <?= (int) $property['bedrooms'] ?> Beds</span>
                            <span><i class="fas fa-bath"></i> <?= (int) $property['bathrooms'] ?> Baths</span>
                        </div>
                        <a href="property_details.php?id=<?= (int) $property['id'] ?>" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.search-container {
    position: relative;
    width: 100%;
    min-height: calc(100vh - 120px); /* subtract header/footer if any */
    display: grid;
    grid-template-rows: auto 1fr; /* search bar then results */
    align-items: start;
    padding: 20px;
    box-sizing: border-box;
}
.search {
    padding-top: 60px;
    padding-bottom: 60px;
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.search-field select').forEach(function (selectEl) {
        selectEl.addEventListener('change', function () {
            // Find the .value span in the same .search-field button
            const valueSpan = this.closest('.search-field').querySelector('.value');

            // If no selection or empty, reset to default text
            if (this.value === "") {
                valueSpan.textContent = valueSpan.dataset.default || 'Any';
            } else {
                valueSpan.textContent = this.options[this.selectedIndex].text;
            }
        });
    });
});
</script>
