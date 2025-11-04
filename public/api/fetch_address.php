<?php
require_once __DIR__ . '/../../config/pdo_database.php';

header('Content-Type: application/json');

$level = $_GET['level'] ?? '';
$parent_code = $_GET['parent'] ?? '';

try {
    switch ($level) {
        case 'region':
            // Fetch all regions
            $sql = "SELECT `COL 1` AS code, `COL 2` AS name 
                    FROM psgc 
                    WHERE `COL 4` = 'Reg'
                    ORDER BY name ASC";
            $stmt = $pdo->query($sql);
            break;

        case 'province':
            // Fetch provinces under a region
            $sql = "SELECT `COL 1` AS code, `COL 2` AS name 
                    FROM psgc 
                    WHERE `COL 4` = 'Prov' 
                    AND LEFT(`COL 1`, 2) = LEFT(:parent, 2)
                    ORDER BY name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['parent' => $parent_code]);
            break;

        case 'city':
            // Fetch cities/municipalities under a province
            $sql = "SELECT `COL 1` AS code, `COL 2` AS name 
                    FROM psgc 
                    WHERE (`COL 4` = 'City' OR `COL 4` = 'Mun') 
                    AND LEFT(`COL 1`, 4) = LEFT(:parent, 4)
                    ORDER BY name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['parent' => $parent_code]);
            break;

        case 'barangay':
            // Fetch barangays under a city/municipality
            $sql = "SELECT `COL 1` AS code, `COL 2` AS name 
                    FROM psgc 
                    WHERE `COL 4` = 'Bgy' 
                    AND LEFT(`COL 1`, 6) = LEFT(:parent, 6)
                    ORDER BY name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['parent' => $parent_code]);
            break;

        default:
            echo json_encode([]);
            exit;
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
