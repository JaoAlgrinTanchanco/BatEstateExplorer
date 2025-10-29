<?php
    require_once __DIR__ . '/../../../../config/database.php';
    require_once __DIR__ . '/../../../../components/notification.php';
    require_once __DIR__ . '/../../../../components/agent_property_card.php';
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
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/search.css">

<h3 class="greeting">Hello, <?= htmlspecialchars($userName) ?>!</h3>

<!-- Welcome Banner -->
<div class="welcome-card">
    <img src="/BatEstateExplorer/assets/images/Frame 6.png" alt="Welcome Banner" class="welcome-image">
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
                        'Property' => 'Property',
                        'Lot' => 'Lot'
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

    <?php render_agent_property_card([], true); ?>
</div>

<!-- Notice Modal -->
<div id="noticeModal" class="notice-modal hidden">
  <div class="notice-content">
    <h3>Important Notice</h3>
    <p id="noticeMessage"></p>
    <button id="noticeOkBtn" class="btn btn-primary">Okay</button>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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
    });
    // === Notices Modal Logic ===
    document.addEventListener("DOMContentLoaded", async () => {
    try {
        // Fetch unseen notices
        const res = await fetch("/BatEstateExplorer/public/api/get_unseen_notices.php");
        const data = await res.json();

        if (data.success && data.notices.length > 0) {
        const modal = document.getElementById("noticeModal");
        const msgEl = document.getElementById("noticeMessage");
        const okBtn = document.getElementById("noticeOkBtn");

        let currentIndex = 0;

        const showNextNotice = async () => {
            // If no more notices, hide modal
            if (currentIndex >= data.notices.length) {
            modal.classList.add("hidden");
            return;
            }

            const notice = data.notices[currentIndex];
            msgEl.textContent = notice.message;
            modal.classList.remove("hidden");

            okBtn.onclick = async () => {
            modal.classList.add("hidden");

            // Mark this notice as seen
            await fetch("/BatEstateExplorer/public/api/mark_notice_seen.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: notice.id }),
            });

            currentIndex++;
            // Small delay before showing the next one (for smoother UX)
            setTimeout(showNextNotice, 300);
            };
        };

        // Start showing notices
        showNextNotice();
        }
    } catch (err) {
        console.error("Failed to load notices:", err);
    }
    });
</script>
