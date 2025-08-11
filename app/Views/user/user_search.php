<?php
// user_search.php
require_once __DIR__ . '/../../../config/database.php'; // ✅ Correct path

// Get filter values
$location = $_GET['location'] ?? '';
$property_type = $_GET['property_type'] ?? '';
$price_range = $_GET['price_range'] ?? '';

// Base query
$sql = "SELECT * FROM properties WHERE 1=1";
$params = [];
$types = "";

// Filters
if ($location !== '') {
    $sql .= " AND location = ?";
    $params[] = $location;
    $types .= "s";
}
if ($property_type !== '') {
    $sql .= " AND property_type = ?";
    $params[] = $property_type;
    $types .= "s";
}
if ($price_range !== '') {
    if ($price_range === '5000000+') {
        $sql .= " AND price >= 5000000";
    } else {
        [$min, $max] = explode('-', $price_range);
        $sql .= " AND price BETWEEN ? AND ?";
        $params[] = (int) $min;
        $params[] = (int) $max;
        $types .= "ii";
    }
}

// Prepare and execute
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch results into array
$properties = $result->fetch_all(MYSQLI_ASSOC);

// Close resources
$stmt->close();
$conn->close();
?>

<div class="search-container">
    <div class="search">
        <form class="search-form" method="GET" action="user_dashboard.php?view=search_results">
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

            <button type="button" class="search-field" data-field="property_type">
                <span class="label">Property Type</span>
                <span class="value">All Types</span>
                <select name="property_type" id="property_type">
                    <option value="" selected>All Types</option>
                    <option value="house">House</option>
                    <option value="condo">Condominium</option>
                    <option value="land">Land</option>
                </select>
            </button>

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
