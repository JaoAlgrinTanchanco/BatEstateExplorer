<?php
/**
 * cleanup_accounts.php
 * ✅ Deletes orphaned user-related files (docs, images, profile_images)
 * ✅ DB is the source of truth
 * ✅ Keeps files if referenced by users or non-rejected applications
 * ✅ Deletes files if only referenced by rejected apps or not referenced at all
 * ✅ Cleans up property images if property deleted or owner invalid
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

// from users
$res = mysqli_query($conn, "SELECT id FROM users");
while ($row = mysqli_fetch_assoc($res)) {
    $validUserIds[] = (int)$row['id'];
}

// from applications (non-rejected)
$res = mysqli_query($conn, "SELECT user_id FROM applications WHERE (status != 'rejected' OR status IS NULL)");
while ($row = mysqli_fetch_assoc($res)) {
    if (!empty($row['user_id'])) {
        $validUserIds[] = (int)$row['user_id'];
    }
}
$validUserIds = array_unique(array_filter($validUserIds));

// --- Step 2: Collect file paths from users ---
$userFilePaths = [];
$res = mysqli_query($conn, "SELECT profile_image_path FROM users WHERE profile_image_path IS NOT NULL AND profile_image_path != ''");
while ($row = mysqli_fetch_assoc($res)) {
    $path = trim($row['profile_image_path']);
    if ($path) $userFilePaths[] = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
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

// --- Step 4: Collect property images ---
$propertyFilePaths = [];
$res = mysqli_query($conn, "SELECT id, user_id, agent_id, images FROM properties");
while ($row = mysqli_fetch_assoc($res)) {
    $imagesCol = trim($row['images'] ?? '');
    $uid = (int)($row['user_id'] ?? 0);
    $aid = (int)($row['agent_id'] ?? 0);

    // If property has invalid owner, mark its images for deletion
    $ownerValid = in_array($uid, $validUserIds, true) || in_array($aid, $validUserIds, true);

    if (!empty($imagesCol)) {
        $decoded = json_decode($imagesCol, true);
        if (is_array($decoded)) {
            foreach ($decoded as $p) {
                $abs = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($p, '/');
                if ($ownerValid) {
                    $propertyFilePaths[] = $abs; // keep
                } else {
                    $rejFilePaths[] = $abs; // owner invalid → delete
                }
            }
        }
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

        // Delete if unreferenced or rejected
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
