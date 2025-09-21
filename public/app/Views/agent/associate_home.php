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

<style>
/* Category spacing & font */
.town-section {
    margin-bottom: 3rem;
}
.town-section h3.section-title {
    font-size: 1.9rem; /* larger font */
    margin-bottom: 1rem;
}

/* Horizontal scrolling row */
/* Horizontal scrolling row with fixed card width */
.town-grid-wrapper {
    display: flex;
    overflow-x: auto;
    scroll-behavior: smooth;
    gap: 1.5rem;
    padding-bottom: 0.5rem;
}

.town-grid-wrapper::-webkit-scrollbar { display: none; }
.town-grid-wrapper { -ms-overflow-style: none; scrollbar-width: none; }

.town-grid {
    display: flex; /* use flex instead of grid */
    gap: 1.5rem;
}

/* Force all cards to have same width for consistency */
.town-grid .property-card {
    flex: 0 0 250px; /* fixed width */
    max-width: 250px;
}


/* "See More" text link inside horizontal scroll */
.town-grid .see-more-text {
    flex: 0 0 250px; /* same width as property cards */
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #111;
    text-decoration: none; /* remove underline */
    border: 1px dashed transparent; /* invisible by default */
    padding: 0.5rem 0;
    cursor: pointer;
    transition: color 0.2s ease, border 0.2s ease;
}

.town-grid .see-more-text:hover {
    border: 2px dashed #000; /* black broken line border */
    background: #c9c9c9;
    color: #000; /* dimmed color */
    border-radius: 20px;
    
}

</style>

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
                        <!-- See More as text link -->
                        <a href="/all-properties.php?location=Batangas+City" class="see-more-text">See More</a>
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
                            <a href="/all-properties.php?location=<?= urlencode($town) ?>" class="see-more-text">See More</a>
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
