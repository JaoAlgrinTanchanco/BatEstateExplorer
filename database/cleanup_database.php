<?php
/**
 * cleanup_database.php
 * ✅ Ensures DB integrity by removing orphaned records
 * ✅ Deletes records referencing deleted or rejected users
 * ✅ Deletes orphaned property records (images, drafts, docs, reviews)
 * ✅ Supports dry-run simulation
 */

require_once __DIR__ . '/../config/database.php';

// 🔧 Toggle simulation mode (true = preview, false = actual delete)
$simulate = true;

// --- Step 1: Collect valid user IDs ---
$validUserIds = [];
$res = mysqli_query($conn, "SELECT id FROM users");
while ($row = mysqli_fetch_assoc($res)) {
    $validUserIds[] = (int)$row['id'];
}

// --- Step 2: Collect valid applicant user_ids (exclude rejected) ---
$validApplicantIds = [];
$res = mysqli_query($conn, "SELECT user_id FROM applications WHERE status != 'rejected' AND user_id IS NOT NULL");
while ($row = mysqli_fetch_assoc($res)) {
    $validApplicantIds[] = (int)$row['user_id'];
}

// --- Step 3: Build lookup sets ---
$validIds = array_unique(array_merge($validUserIds, $validApplicantIds));
$validSet = array_flip($validIds);

// Helper to check if a user ID is valid
function isValidUser($id, $validSet) {
    return isset($validSet[$id]);
}

// --- Step 4: Delete rows referencing invalid users ---
function cleanupByUserRef(mysqli $conn, string $table, string $column, array $validSet, bool $simulate) {
    $deleted = 0;
    $ids = [];
    $res = mysqli_query($conn, "SELECT id, $column FROM $table");
    while ($row = mysqli_fetch_assoc($res)) {
        $uid = (int)$row[$column];
        if (!isset($validSet[$uid])) {
            $ids[] = (int)$row['id'];
        }
    }

    if (!empty($ids)) {
        $idList = implode(',', $ids);
        $deleted = count($ids);
        if ($simulate) {
            echo "[SIMULATE] Would delete $deleted rows from $table referencing invalid users.\n";
        } else {
            mysqli_query($conn, "DELETE FROM $table WHERE id IN ($idList)");
            echo "[CLEANUP] Deleted $deleted rows from $table referencing invalid users.\n";
        }
    }
    return $deleted;
}

// --- Step 5: Cleanup user-dependent tables ---
$totalDeleted = 0;
$totalDeleted += cleanupByUserRef($conn, 'agents', 'user_id', $validSet, $simulate);
$totalDeleted += cleanupByUserRef($conn, 'transactions', 'user_id', $validSet, $simulate);
$totalDeleted += cleanupByUserRef($conn, 'messages', 'sender_id', $validSet, $simulate);
$totalDeleted += cleanupByUserRef($conn, 'messages', 'receiver_id', $validSet, $simulate);
$totalDeleted += cleanupByUserRef($conn, 'property_drafts', 'user_id', $validSet, $simulate);
$totalDeleted += cleanupByUserRef($conn, 'property_reviews', 'user_id', $validSet, $simulate);

// --- Step 6: Cleanup properties owned by invalid users ---
$invalidPropIds = [];
$res = mysqli_query($conn, "SELECT id, user_id FROM properties");
while ($row = mysqli_fetch_assoc($res)) {
    $uid = (int)$row['user_id'];
    if (!isset($validSet[$uid])) {
        $invalidPropIds[] = (int)$row['id'];
    }
}

if (!empty($invalidPropIds)) {
    $propList = implode(',', $invalidPropIds);
    $count = count($invalidPropIds);
    if ($simulate) {
        echo "[SIMULATE] Would delete $count invalid properties (no valid owner).\n";
    } else {
        mysqli_query($conn, "DELETE FROM properties WHERE id IN ($propList)");
        echo "[CLEANUP] Deleted $count invalid properties.\n";
    }

    // --- Cascade delete property_images, property_documents, property_reviews ---
    foreach (['property_images', 'property_documents', 'property_reviews'] as $tbl) {
        if ($simulate) {
            echo "[SIMULATE] Would delete from $tbl where property_id in ($propList)\n";
        } else {
            mysqli_query($conn, "DELETE FROM $tbl WHERE property_id IN ($propList)");
            echo "[CLEANUP] Deleted from $tbl orphaned by deleted properties.\n";
        }
    }
}

// --- Step 7: Cleanup orphaned property records ---
$validProps = [];
$res = mysqli_query($conn, "SELECT id FROM properties");
while ($row = mysqli_fetch_assoc($res)) {
    $validProps[] = (int)$row['id'];
}
$propSet = array_flip($validProps);

function cleanupByPropRef(mysqli $conn, string $table, string $column, array $propSet, bool $simulate) {
    $deleted = 0;
    $ids = [];
    $res = mysqli_query($conn, "SELECT id, $column FROM $table");
    while ($row = mysqli_fetch_assoc($res)) {
        $pid = (int)$row[$column];
        if (!isset($propSet[$pid])) {
            $ids[] = (int)$row['id'];
        }
    }

    if (!empty($ids)) {
        $idList = implode(',', $ids);
        $deleted = count($ids);
        if ($simulate) {
            echo "[SIMULATE] Would delete $deleted rows from $table referencing missing properties.\n";
        } else {
            mysqli_query($conn, "DELETE FROM $table WHERE id IN ($idList)");
            echo "[CLEANUP] Deleted $deleted rows from $table referencing missing properties.\n";
        }
    }
    return $deleted;
}

// Cleanup all property-dependent tables
$totalDeleted += cleanupByPropRef($conn, 'property_images', 'property_id', $propSet, $simulate);
$totalDeleted += cleanupByPropRef($conn, 'property_documents', 'property_id', $propSet, $simulate);
$totalDeleted += cleanupByPropRef($conn, 'property_reviews', 'property_id', $propSet, $simulate);

echo "\n✅ Cleanup completed. Total affected rows: $totalDeleted\n";
?>
