<?php
/**
 * cleanup_accounts.php
 * ✅ Deletes orphaned files (docs, images, profile_images, property_images)
 * ✅ DB is the source of truth
 * ✅ Keeps files if referenced by users or non-rejected applications
 * ✅ Deletes files if only referenced by rejected apps or not referenced at all
 * ✅ Deletes property images if property or owner is invalid
 */

require_once __DIR__ . '/../config/database.php';

// --- Directories to clean ---
$dirs = [
    $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/documents/',
    $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/images/',
    $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/profile_images/',
    $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/property_images/'
];

// --- Step 1: Collect valid user IDs from users & applications (non-rejected) ---
$validUserIds = [];

// From users
$res = mysqli_query($conn, "SELECT id FROM users");
while ($row = mysqli_fetch_assoc($res)) {
    $validUserIds[] = (int)$row['id'];
}

// From applications (non-rejected)
$res = mysqli_query($conn, "SELECT user_id FROM applications WHERE (status != 'rejected' OR status IS NULL)");
while ($row = mysqli_fetch_assoc($res)) {
    if (!empty($row['user_id'])) {
        $validUserIds[] = (int)$row['user_id'];
    }
}

$validUserIds = array_unique(array_filter($validUserIds));

// --- Step 2: Collect file paths from users ---
$userFilePaths = [];
$res = mysqli_query($conn, "
    SELECT 
        profile_image_path,
        broker_license_path,
        prc_license_path,
        resume_path,
        valid_id_path,
        additional_docs_path
    FROM users
");
while ($row = mysqli_fetch_assoc($res)) {
    foreach ($row as $col => $val) {
        if (empty($val)) continue;
        if ($col === 'additional_docs_path' && str_starts_with(trim($val), '[')) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                foreach ($decoded as $p) {
                    $userFilePaths[] = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($p, '/');
                }
            }
        } else {
            $userFilePaths[] = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($val, '/');
        }
    }
}

// --- Step 3: Collect file paths from applications ---
$appFilePaths = [];
$rejFilePaths = [];

$appColumns = [
    'broker_license_path',
    'prc_license_path',
    'resume_path',
    'valid_id_path',
    'additional_docs_path',
    'profile_image_path'
];

$res = mysqli_query($conn, "SELECT " . implode(',', $appColumns) . ", status FROM applications");
while ($row = mysqli_fetch_assoc($res)) {
    foreach ($appColumns as $col) {
        if (empty($row[$col])) continue;

        $paths = [];
        if ($col === 'additional_docs_path' && str_starts_with(trim($row[$col]), '[')) {
            $decoded = json_decode($row[$col], true);
            if (is_array($decoded)) {
                foreach ($decoded as $p) {
                    $paths[] = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($p, '/');
                }
            }
        } else {
            $paths[] = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($row[$col], '/');
        }

        foreach ($paths as $p) {
            if ($row['status'] === 'rejected') {
                $rejFilePaths[] = $p;
            } else {
                $appFilePaths[] = $p;
            }
        }
    }
}

// --- Step 4: Collect property image paths ---
$propertyFilePaths = [];
$res = mysqli_query($conn, "
    SELECT pi.image_path, p.user_id, p.agent_id
    FROM property_images pi
    LEFT JOIN properties p ON p.id = pi.property_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $img = trim($row['image_path'] ?? '');
    $uid = (int)($row['user_id'] ?? 0);
    $aid = (int)($row['agent_id'] ?? 0);

    if (empty($img)) continue;

    $absPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($img, '/');
    $ownerValid = in_array($uid, $validUserIds, true) || in_array($aid, $validUserIds, true);

    if ($ownerValid) {
        $propertyFilePaths[] = $absPath; // Keep if valid owner
    } else {
        $rejFilePaths[] = $absPath; // Mark for deletion if owner invalid
    }
}

// --- Step 5: Build lookups ---
$userLookup = array_flip(array_unique($userFilePaths));
$appLookup  = array_flip(array_unique($appFilePaths));
$propLookup = array_flip(array_unique($propertyFilePaths));
$rejLookup  = array_flip(array_unique($rejFilePaths));

$deletedCount = 0;

// --- Step 6: Scan directories and delete orphans ---
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;

        $fullPath = str_replace(['\\', '//'], '/', $file->getPathname());

        // Keep file if referenced in users, valid apps, or valid properties
        if (isset($userLookup[$fullPath]) || isset($appLookup[$fullPath]) || isset($propLookup[$fullPath])) {
            continue;
        }

        // Delete if unreferenced or marked rejected
        if (!isset($userLookup[$fullPath]) && !isset($appLookup[$fullPath]) && !isset($propLookup[$fullPath])) {
            if (@unlink($fullPath)) {
                error_log("[Cleanup] Deleted orphaned file: $fullPath");
                $deletedCount++;
            }
        }
    }
}

error_log("[Cleanup] Completed. Deleted $deletedCount orphaned files.");
echo "✅ Cleanup completed. Deleted $deletedCount orphaned files.\n";
?>
