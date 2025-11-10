<?php
    require_once __DIR__ . '/../../../../config/database.php';
    require_once __DIR__ . '/../../../../components/notification.php';
    require_once __DIR__ . '/../../../../components/user_property_card.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/database/cleanup_database.php';

    //
    // ================================
    // Detect AJAX
    // ================================
    $isAjax = (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) || (!empty($_POST['ajax']) && $_POST['ajax'] === '1');

    $request = $isAjax ? $_POST : $_GET;

    // ================================
    // Fetch Logged-in User's First Name
    // ================================

    // No session_start() — it's already active in your layout/controller
    $userName = 'Guest'; // Default fallback

    if (isset($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];

        $nameSql = "SELECT first_name FROM users WHERE id = ? LIMIT 1";

        if ($nameStmt = $conn->prepare($nameSql)) {
            $nameStmt->bind_param("i", $userId);
            $nameStmt->execute();
            $nameResult = $nameStmt->get_result();

            if ($nameRow = $nameResult->fetch_assoc()) {
                $first = trim($nameRow['first_name'] ?? '');
                if ($first !== '') {
                    $userName = ucfirst($first);
                }
            }

            $nameStmt->close();
        }
    }

    //
    // ================================
    // Filters
    // ================================
    $location      = $request['location']      ?? '';
    $property_type = $request['property_type'] ?? '';
    $price_range   = $request['price_range']   ?? '';
    $bedrooms      = $request['bedrooms']      ?? '';
    $bathrooms     = $request['bathrooms']     ?? '';
    $size          = $request['size']          ?? '';

    //
    // ================================
    // Pagination
    // ================================
    $page   = (isset($request['page']) && is_numeric($request['page'])) ? (int)$request['page'] : 1;
    $limit  = 35;
    $offset = ($page - 1) * $limit;

    //
    // ================================
    // Helper: apply filters to SQL
    // ================================
    function applyFilters(&$sql, &$params, &$types, $location, $property_type, $bedrooms, $bathrooms, $price_range, $size) {
        if ($location !== '') {
            $sql .= " AND p.location = ?";
            $params[] = $location;
            $types   .= "s";
        }
        if ($property_type !== '') {
            $sql .= " AND p.property_type = ?";
            $params[] = $property_type;
            $types   .= "s";
        }
        if ($bedrooms !== '') {
            $sql .= " AND p.bedrooms >= ?";
            $params[] = (int)$bedrooms;
            $types   .= "i";
        }
        if ($bathrooms !== '') {
            $sql .= " AND p.bathrooms >= ?";
            $params[] = (int)$bathrooms;
            $types   .= "i";
        }
        if ($price_range !== '') {
            if ($price_range === '5000000+') {
                $sql .= " AND p.price >= 5000000";
            } elseif (strpos($price_range, '-') !== false) {
                [$min, $max] = array_map('floatval', explode('-', $price_range));
                $sql .= " AND p.price BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
                $types   .= "dd";
            }
        }
        if ($size !== '') {
            if ($size === '200+') {
                $sql .= " AND p.sqm >= 200";
            } elseif (strpos($size, '-') !== false) {
                [$min, $max] = array_map('floatval', explode('-', $size));
                $sql .= " AND p.sqm BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
                $types   .= "dd";
            }
        }
    }

    //
    // ================================
    // Count Query
    // ================================
    $countSql = "SELECT COUNT(*) AS total FROM properties p WHERE p.status IN ('available', 'ongoing_inquiry', 'sold')";
    $params   = [];
    $types    = "";
    applyFilters($countSql, $params, $types, $location, $property_type, $bedrooms, $bathrooms, $price_range, $size);

    $countStmt = $conn->prepare($countSql);
    if ($countStmt === false) {
        echo "<p>Server error.</p>";
        exit;
    }

    if (!empty($params)) {
        $bind = [$types];
        foreach ($params as &$val) $bind[] = &$val;
        call_user_func_array([$countStmt, 'bind_param'], $bind);
    }

    $countStmt->execute();
    $countResult  = $countStmt->get_result();
    $totalResults = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
    $totalPages   = max(1, (int)ceil($totalResults / $limit));

    //
    // ================================
    // Main Query
    // ================================
    $sql = "
        SELECT 
            p.*, 
            p.listed_by_agent_id,
            (
                SELECT image_path 
                FROM property_images 
                WHERE property_id = p.id 
                ORDER BY is_primary DESC, id ASC 
                LIMIT 1
            ) AS image_path
        FROM properties p
        WHERE p.status IN ('available', 'ongoing_inquiry', 'sold')
    ";

    $params = [];
    $types  = "";
    applyFilters($sql, $params, $types, $location, $property_type, $bedrooms, $bathrooms, $price_range, $size);

    $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo "<p>Server error.</p>";
        exit;
    }

    if (!empty($params)) {
        $bind = [$types . "ii"];
        foreach ($params as &$val) $bind[] = &$val;
        $bind[] = &$limit;
        $bind[] = &$offset;
        call_user_func_array([$stmt, 'bind_param'], $bind);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }

    $stmt->execute();
    $result     = $stmt->get_result();
    $properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // ================================
    // Fetch Top Performing Properties (Industry Standard)
    // ================================

    $featuredProperties = [];
    $allowedStatuses = ["available", "sold", "ongoing_inquiry"];
    $statusList = "'" . implode("','", $allowedStatuses) . "'";

    // 🏆 1️⃣ Only include properties that truly qualify as top performing
    $topRatedSql = "
        SELECT 
            p.*,
            ROUND(AVG(r.rating), 2) AS avg_rating,
            COUNT(r.id) AS total_reviews
        FROM properties p
        INNER JOIN property_reviews r ON p.id = r.property_id
        WHERE 
            p.status IN ('available','sold','ongoing_inquiry')
        GROUP BY p.id
        HAVING 
            avg_rating >= 4.0    -- Minimum quality threshold
            AND total_reviews >= 5   -- Minimum credibility threshold
        ORDER BY 
            avg_rating DESC, 
            total_reviews DESC, 
            p.created_at DESC
        LIMIT 3;
    ";

    if ($stmt = $conn->prepare($topRatedSql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $featuredProperties = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }

    // -------------------------------
    // 3️⃣ Fetch All Active Paid Featured Properties
    // -------------------------------
    $excludeIds = array_column($featuredProperties, 'id');
    $excludeStr = !empty($excludeIds)
        ? "AND p.id NOT IN (" . implode(',', array_map('intval', $excludeIds)) . ")"
        : "";

    $paidFeaturedSql = "
        SELECT 
            p.*,
            TIMESTAMPDIFF(DAY, p.created_at, p.featured_until) AS feature_duration
        FROM properties p
        WHERE 
            p.is_featured = 1
            AND p.featured_until > NOW()
            AND p.status IN ($statusList)
            $excludeStr
        ORDER BY 
            feature_duration DESC,    -- longer duration = higher plan
            p.featured_until DESC,    -- most recent renewal first
            p.created_at DESC
    ";

    $paidFeatured = [];
    if ($paidStmt = $conn->prepare($paidFeaturedSql)) {
        $paidStmt->execute();
        $paidResult = $paidStmt->get_result();
        if ($paidResult && $paidResult->num_rows > 0) {
            $paidFeatured = $paidResult->fetch_all(MYSQLI_ASSOC);
        }
        $paidStmt->close();
    } else {
        error_log("❌ Failed to prepare paid featured properties query: " . $conn->error);
    }

    // -------------------------------
    // 4️⃣ Combine Top Rated + Paid Featured
    // -------------------------------
    $featuredProperties = array_merge($featuredProperties, $paidFeatured);

?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/search.css">

<h3 class="greeting">Hello, <?= htmlspecialchars($userName) ?>!</h3>

<!-- Welcome Carousel -->
<div class="welcome-carousel">
  <div class="carousel-track">
    <img src="/BatEstateExplorer/assets/images/bannerL.png" alt="Banner L" class="carousel-banner">
    <img src="/BatEstateExplorer/assets/images/bannerU.png" alt="Banner U" class="carousel-banner">
    <img src="/BatEstateExplorer/assets/images/bannerR.png" alt="Banner R" class="carousel-banner">
  </div>
</div>

<div class="search-container">
    <div class="search">
        <div class="search-form">
            <?php
            $filters = [
                'location' => [
                    'label' => 'Location',
                    'options' => [
                        '' => 'All Locations',
                        'Agoncillo' => 'Agoncillo',
                        'Alitagtag' => 'Alitagtag',
                        'Balayan' => 'Balayan',
                        'Balete' => 'Balete',
                        'Batangas City' => 'Batangas City',
                        'Bauan' => 'Bauan',
                        'Calaca' => 'Calaca',
                        'Calatagan' => 'Calatagan',
                        'Cuenca' => 'Cuenca',
                        'Ibaan' => 'Ibaan',
                        'Laurel' => 'Laurel',
                        'Lemery' => 'Lemery',
                        'Lian' => 'Lian',
                        'Lipa City' => 'Lipa City',
                        'Lobo' => 'Lobo',
                        'Mabini' => 'Mabini',
                        'Malvar' => 'Malvar',
                        'Mataasnakahoy' => 'Mataasnakahoy',
                        'Nasugbu' => 'Nasugbu',
                        'Padre Garcia' => 'Padre Garcia',
                        'Rosario' => 'Rosario',
                        'San Jose' => 'San Jose',
                        'San Juan' => 'San Juan',
                        'San Luis' => 'San Luis',
                        'San Nicolas' => 'San Nicolas',
                        'San Pascual' => 'San Pascual',
                        'Santa Teresita' => 'Santa Teresita',
                        'Santo Tomas' => 'Santo Tomas',
                        'Taal' => 'Taal',
                        'Talisay' => 'Talisay',
                        'Tanauan City' => 'Tanauan City',
                        'Taysan' => 'Taysan',
                        'Tingloy' => 'Tingloy',
                        'Tuy' => 'Tuy'
                    ]
                ],

                'property_type' => [
                    'label' => 'Property Type',
                    'options' => [
                        '' => 'All Type',
                        'Condominium' => 'Condominium',
                        'Apartment' => 'Apartment',
                        'Townhouse' => 'Townhouse',
                        'House and Lot' => 'House and Lot',
                        'Commercial Building' => 'Commercial Building',
                        'Lot Only' => 'Lot Only',
                        'Farm Lot' => 'Farm Lot',
                        'Industrial Lot' => 'Industrial Lot',
                        'Beachfront Property' => 'Beachfront Property',
                        'Resort' => 'Resort',
                        'Hotels and Motels' => 'Hotels and Motels',
                        'Dormitory' => 'Dormitory',
                        'Office Space' => 'Office Space',
                        'Warehouse' => 'Warehouse',
                        'Retail Space' => 'Retail Space',
                        'Mixed-Use Development' => 'Mixed-Use Development',
                        'Luxury Estate' => 'Luxury Estate',
                        'Foreclosed Property' => 'Foreclosed Property',
                        'Subdivision Development' => 'Subdivision Development',
                        'Others' => 'Others'
                    ]
                ],

                'price_range' => [
                    'label' => 'Price Range',
                    'options' => [
                        '' => 'Any Price',
                        '0-500000' => '₱0 - ₱500K',
                        '500000-1500000' => '₱500K - ₱1.5M',
                        '1500000-3000000' => '₱1.5M - ₱3M',
                        '3000000-5000000' => '₱3M - ₱5M',
                        '5000000+' => '₱5M+'
                    ]
                ],

                'bedrooms' => ['label'=>'Bedrooms', 'options'=>[''=>'Any','1'=>'1','2'=>'2','3'=>'3','4'=>'4+']],
                'bathrooms' => ['label'=>'Bathrooms', 'options'=>[''=>'Any','1'=>'1','2'=>'2','3'=>'3','4'=>'4+']],
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

    <?php if (!empty($featuredProperties)): ?>
        <h4>🏆 Top Featured</h4>
        <!-- Featured -->
        <div class="property-grid-x" id="propertyGridX">
            <?php foreach ($featuredProperties as $property):
                $property['data_type'] = $property['property_type'];
                $property['data_size'] = $property['sqm'];
                render_property_card($property);
            endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="properties-grid" id="propertiesGrid">
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property):
                $property['data_type'] = $property['property_type'];
                $property['data_size'] = $property['sqm'];
                render_property_card($property);
            endforeach; ?>
        <?php else: ?>
            <p>No properties available at the moment.</p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php
        // Build base query string without page param
        $query = $_GET;
        unset($query['page']);

        // Previous (disabled if on first page)
        if ($page > 1) {
            $query['page'] = $page - 1;
            echo '<a class="prev" href="?' . http_build_query($query) . '">&laquo; Prev</a>';
        } else {
            echo '<span class="prev disabled">&laquo; Prev</span>';
        }

        // Page numbers
        for ($i = 1; $i <= $totalPages; $i++) {
            $query['page'] = $i;
            $class = $i === $page ? 'active' : '';
            echo '<a class="' . $class . '" href="?' . http_build_query($query) . '">' . $i . '</a>';
        }

        // Next (disabled if on last page)
        if ($page < $totalPages) {
            $query['page'] = $page + 1;
            echo '<a class="next" href="?' . http_build_query($query) . '">Next &raquo;</a>';
        } else {
            echo '<span class="next disabled">Next &raquo;</span>';
        }
        ?>
    </div>

    <?php render_property_card([], true); ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ---------------- Carousel Auto-Spin ----------------
        const track = document.querySelector('.carousel-track');
        const banners = document.querySelectorAll('.carousel-banner');
        const totalBanners = banners.length;
        let index = 0;
        const displayTime = 5000; // milliseconds each banner is displayed

        function showNextBanner() {
            index++;
            if (index >= totalBanners) index = 0;
            track.style.transform = `translateX(-${index * 100}%)`;
        }

        // Start auto-rotation
        let carouselInterval = setInterval(showNextBanner, displayTime);

        // Pause on hover
        document.querySelector('.welcome-carousel').addEventListener('mouseenter', () => {
            clearInterval(carouselInterval);
        });
        document.querySelector('.welcome-carousel').addEventListener('mouseleave', () => {
            carouselInterval = setInterval(showNextBanner, displayTime);
        });

        const filters = ['location', 'property_type', 'price_range', 'bedrooms', 'bathrooms', 'size'];
        const searchBtn = document.getElementById('searchForm1');

        function loadProperties(extra = {}) {
            const formData = new FormData();
            filters.forEach(f => {
                const el = document.getElementById(f);
                if (el) formData.append(f, el.value);
            });
            Object.entries(extra).forEach(([k, v]) => formData.append(k, v));
            formData.append('ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.text())
            .then(html => {
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const newGrid = temp.querySelector('#propertiesGrid');
                const newPagination = temp.querySelector('.pagination');
                if (newGrid && newPagination) {
                    document.getElementById('propertiesGrid').outerHTML = newGrid.outerHTML;
                    document.querySelector('.pagination').outerHTML = newPagination.outerHTML;
                    bindPagination(); // re-bind links after reload
                }
            })
            .catch(err => console.error('Error:', err));
        }

        function bindPagination() {
            document.querySelectorAll('.pagination a').forEach(a => {
                a.addEventListener('click', e => {
                    e.preventDefault();
                    const url = new URL(a.href);
                    const page = url.searchParams.get('page') || 1;
                    loadProperties({ page });
                });
            });
        }

        // --- Filters ---
        filters.forEach(f => {
            const el = document.getElementById(f);
            if (!el) return;
            const span = el.closest('.search-field').querySelector('.value');

            const updateLabel = () => {
                span.textContent = el.value === "" ? span.dataset.default : el.options[el.selectedIndex].text;
            };
            updateLabel();

            el.addEventListener('change', () => {
                updateLabel();
                loadProperties({ page: 1 });
            });
        });

        // --- Reset ---
        if (searchBtn) {
            searchBtn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i>';
            searchBtn.addEventListener('click', e => {
                e.preventDefault();
                filters.forEach(f => {
                    const el = document.getElementById(f);
                    if (el) el.value = '';
                    const span = el.closest('.search-field').querySelector('.value');
                    if (span) span.textContent = span.dataset.default;
                });
                loadProperties({ page: 1 });
            });
        }

        // Initial pagination binding
        bindPagination();

        // === Dynamic Notices Modal with Conditional Icons ===
        (async () => {
            try {
                const res = await fetch("/BatEstateExplorer/public/api/get_unseen_notices.php");
                const data = await res.json();

                if (!data.success || !data.notices.length) return;

                let currentIndex = 0;

                const showNextNotice = async () => {
                if (currentIndex >= data.notices.length) {
                    document.querySelector(".notice-modal")?.remove();
                    return;
                }

                const notice = data.notices[currentIndex];
                document.querySelector(".notice-modal")?.remove();

                // --- Determine icon and color ---
                let iconSvg = "";
                let accentColor = "#22c55e"; // success green
                let iconBg = "#f0fff4";
                let titleText = "Success";
                let contentClass = "success"; // default

                if (notice.message.includes("Another agent")) {
                    accentColor = "#eab308"; // amber
                    iconBg = "#fffbeb";
                    titleText = "Notice";
                    contentClass = "warning";

                    iconSvg = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="72" height="72" fill="none" stroke="${accentColor}" stroke-width="2.5">
                        <circle cx="32" cy="32" r="28" fill="${iconBg}" stroke="${accentColor}"/>
                        <line x1="32" y1="18" x2="32" y2="38" stroke="${accentColor}" stroke-width="5" stroke-linecap="round"/>
                        <circle cx="32" cy="46" r="3.5" fill="${accentColor}"/>
                    </svg>
                    `;
                } else {
                    iconSvg = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="72" height="72" fill="none" stroke="${accentColor}" stroke-width="2.5">
                        <circle cx="32" cy="32" r="28" fill="${iconBg}" stroke="${accentColor}"/>
                        <path d="M20 33l7 7 17-17" stroke="${accentColor}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    `;
                }

                // --- Build Modal ---
                const modal = document.createElement("div");
                modal.className = "notice-modal";
                modal.innerHTML = `
                <div class="notice-content ${contentClass} animate-in">
                    <div class="notice-icon">${iconSvg}</div>
                    <h3>${titleText}</h3>
                    <p>${notice.message}</p>
                    <button class="btn btn-primary">Okay</button>
                </div>
                `;

                document.body.appendChild(modal);

                // --- Button click handler ---
                modal.querySelector(".btn").addEventListener("click", async () => {
                    modal.classList.add("hidden");

                    await fetch("/BatEstateExplorer/public/api/mark_notice_seen.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id: notice.id }),
                    });

                    currentIndex++;
                    setTimeout(showNextNotice, 400);
                });
                };

                showNextNotice();
            } catch (err) {
                console.error("Failed to load notices:", err);
            }
        })();

        // === Property Notices Modal Display ===
        (async () => {
        try {
            const res = await fetch("/BatEstateExplorer/public/api/check_notice.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({})
            });

            const data = await res.json().catch(() => ({}));
            if (!data.success || !Array.isArray(data.notices) || !data.notices.length) return;

            const unseenNotices = data.notices.filter(n => !n.is_read);
            if (!unseenNotices.length) return;

            let currentIndex = 0;

            const showNextNotice = async () => {
            if (currentIndex >= unseenNotices.length) {
                document.querySelector(".property-notice-modal")?.remove();
                return;
            }

            const notice = unseenNotices[currentIndex];
            document.querySelector(".property-notice-modal")?.remove();

            // --- Icon logic ---
            let iconSvg = "", accentColor = "#3b82f6", iconBg = "#eff6ff", titleText = "Information", contentClass = "info";
            switch (notice.notice_type) {
                case "take_down":
                accentColor = "#ef4444";
                iconBg = "#fef2f2";
                titleText = "Property Taken Down";
                contentClass = "danger";
                iconSvg = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="72" height="72" fill="none" stroke="${accentColor}" stroke-width="2.5">
                    <circle cx="32" cy="32" r="28" fill="${iconBg}" stroke="${accentColor}"/>
                    <line x1="20" y1="20" x2="44" y2="44" stroke="${accentColor}" stroke-width="4" stroke-linecap="round"/>
                    <line x1="44" y1="20" x2="20" y2="44" stroke="${accentColor}" stroke-width="4" stroke-linecap="round"/>
                    </svg>`;
                break;
                case "block":
                accentColor = "#f59e0b";
                iconBg = "#fffbeb";
                titleText = "Property Blocked";
                contentClass = "warning";
                iconSvg = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="72" height="72" fill="none" stroke="${accentColor}" stroke-width="2.5">
                    <circle cx="32" cy="32" r="28" fill="${iconBg}" stroke="${accentColor}"/>
                    <path d="M22 22 L42 42 M42 22 L22 42" stroke="${accentColor}" stroke-width="4" stroke-linecap="round"/>
                    </svg>`;
                break;
                default:
                iconSvg = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="72" height="72" fill="none" stroke="${accentColor}" stroke-width="2.5">
                    <circle cx="32" cy="32" r="28" fill="${iconBg}" stroke="${accentColor}"/>
                    <line x1="32" y1="20" x2="32" y2="36" stroke="${accentColor}" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="32" cy="44" r="2.5" fill="${accentColor}"/>
                    </svg>`;
            }

            // --- Modal HTML ---
            const modal = document.createElement("div");
            modal.className = "property-notice-modal";
            modal.innerHTML = `
                <div class="property-notice-content ${contentClass} animate-in">
                <div class="property-notice-icon">${iconSvg}</div>
                <h3>${titleText}</h3>
                <p>${notice.message}</p>
                <small>Date: ${new Date(notice.created_at).toLocaleString()}</small>
                <button class="btn btn-primary mt-3">Okay</button>
                </div>`;
            document.body.appendChild(modal);

            modal.querySelector(".btn").addEventListener("click", async () => {
                modal.classList.add("hidden");
                try {
                const deleteRes = await fetch("/BatEstateExplorer/public/api/delete_property_notice.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id: notice.notice_id })
                });

                const deleteData = await deleteRes.json().catch(() => ({}));
                if (!deleteData.success) console.error("Failed to delete notice:", deleteData);

                } catch (err) {
                console.error("Error deleting notice:", err);
                }

                currentIndex++;
                setTimeout(showNextNotice, 400);
            });
            };

            showNextNotice();

        } catch (err) {
            console.error("Failed to load property notices:", err);
        }
        })();

        const cards = Array.from(document.querySelectorAll("#propertyGridX > .property-card"));

        const labels = [
        "🥇TOP 1 Performing",
        "🥈 TOP 2 Performing",
        "🥉 TOP 3 Performing"
        ];

        const colors = [
        "linear-gradient(135deg, #8b5cf6, #6d28d9)", // Deep royal purple
        "linear-gradient(135deg, #a78bfa, #7c3aed)", // Medium amethyst
        "linear-gradient(135deg, #c4b5fd, #a78bfa)"  // Soft lavender
        ];

        cards.slice(0, 3).forEach((card, index) => {
        if (card.querySelector(".top-badge")) return;

        const badge = document.createElement("div");
        badge.className = "top-badge";
        badge.textContent = labels[index];
        badge.style.background = colors[index];
        card.appendChild(badge);
        });


    });
    // Trigger feature check on page reload
    window.addEventListener('load', () => {
        fetch('/BatEstateExplorer/public/api/feature_check.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(data.message);
                    if (data.expired && data.expired.length > 0) {
                        console.log('Expired properties:', data.expired);
                    }
                } else if (data.error) {
                    console.error('Feature check error:', data.message);
                }
            })
            .catch(err => {
                console.error('Failed to trigger feature check:', err);
            });
    });
    // Trigger duration check on page reload
    window.addEventListener('load', () => {
        fetch('/BatEstateExplorer/public/api/duration_check.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(data.message);
                    if (data.deleted_properties && data.deleted_properties.length > 0) {
                        console.log('Deleted (expired) properties:', data.deleted_properties);
                    } else {
                        console.log('No expired properties found.');
                    }
                } else if (data.error) {
                    console.error('Duration check error:', data.message);
                }
            })
            .catch(err => {
                console.error('Failed to trigger duration check:', err);
            });
    });
</script>
