<?php
// ==================================================
// 1. Ensure user is logged in
// ==================================================
if (!isset($user) || !is_array($user)) die('Access denied.');

require_once __DIR__ . '/../../../../components/notification.php';

// ==================================================
// 2. Build safe first name for welcome card
// ==================================================
$rawFirst = $user['first_name'] ?? null;
if (!$rawFirst && !empty($user['name'])) {
    $parts = preg_split('/\s+/', trim($user['name']));
    $rawFirst = $parts[0] ?? null;
}
if (!$rawFirst && !empty($user['username'])) $rawFirst = $user['username'];
if (!$rawFirst && !empty($user['email'])) $rawFirst = strstr($user['email'], '@', true) ?: $user['email'];
$agentFirst = htmlspecialchars($rawFirst ?: 'Agent', ENT_QUOTES, 'UTF-8');

// ==================================================
// 3. Check DB connection
// ==================================================
if (!isset($conn)) die('DB connection missing.');

// ==================================================
// 4. Fetch properties
// ==================================================
$categoryLimit = 10; // max 10 per category
$specificTowns = ['Lipa City', 'Tanauan City', 'Santo Tomas'];

// --- a) Featured Batangas City ---
$sqlBatangas = "SELECT id, title, location, price, bedrooms, bathrooms, images, created_at
                FROM properties
                WHERE status = 'available' AND location = 'Batangas City'
                ORDER BY created_at DESC
                LIMIT $categoryLimit";
$resultBatangas = $conn->query($sqlBatangas);
$batangasProperties = $resultBatangas ? $resultBatangas->fetch_all(MYSQLI_ASSOC) : [];

// --- b) Town-specific properties ---
$townProperties = [];
foreach ($specificTowns as $town) {
    $escapedTown = $conn->real_escape_string($town);
    $sqlTown = "SELECT id, title, location, price, bedrooms, bathrooms, images, created_at
                FROM properties
                WHERE status = 'available' AND location = '$escapedTown'
                ORDER BY created_at DESC
                LIMIT $categoryLimit";
    $resultTown = $conn->query($sqlTown);
    if ($resultTown && $resultTown->num_rows > 0) {
        while ($property = $resultTown->fetch_assoc()) {
            $townProperties[$town][] = $property;
        }
    }
}

// ==================================================
// 5. Include modular property card
// ==================================================
require_once __DIR__ . '/../../../../components/agent_property_card.php';
?>

<!-- CSS -->
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/associate_home.css">

<!-- Welcome Banner -->
<div class="welcome-card">
    <img src="/BatEstateExplorer/assets/images/Frame 6.png" alt="Welcome Banner" class="welcome-image">
</div>

<!-- Properties Section -->
<section class="properties">
    <div class="container">

        <!-- Featured Batangas City -->
        <?php if (!empty($batangasProperties)): ?>
            <div class="town-section">
                <h3 class="section-title">Batangas City - Featured</h3>
                <div class="town-grid-wrapper">
                    <div class="town-grid">
                        <?php foreach ($batangasProperties as $property): ?>
                            <?php render_agent_property_card($property); ?>
                        <?php endforeach; ?>
                        <!-- See More text link -->
                        <a href="/BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_search&location=Batangas+City" class="see-more-text">See More</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Town-specific Sections -->
        <?php foreach ($specificTowns as $town): ?>
            <?php if (!empty($townProperties[$town])): ?>
                <div class="town-section">
                    <h3 class="section-title"><?= htmlspecialchars($town) ?></h3>
                    <div class="town-grid-wrapper">
                        <div class="town-grid">
                            <?php foreach ($townProperties[$town] as $property): ?>
                                <?php render_agent_property_card($property); ?>
                            <?php endforeach; ?>
                            <!-- See More as text link -->
                            <a href="/BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_search&location=<?= urlencode($town) ?>" class="see-more-text">See More</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

    </div>
</section>

<?php
// Render modal only once
render_agent_property_card([], true);
?>

<!-- Swiper CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css"/>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
