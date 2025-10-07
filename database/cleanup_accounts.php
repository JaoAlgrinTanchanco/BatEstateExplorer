<?php
/**
 * cleanup_accounts.php
 * DB is the source of truth
 * Keeps files if:
 *     - user exists in users table
 *     - user has a non-rejected application (pending or approved)
 * Deletes files if:
 *     - user exists only in applications table and status is rejected
 *     - user exists in both tables but application is rejected
 *     - file not referenced at all
 * Deletes property images if owner invalid
 * Handles property drafts (keeps if valid user + referenced, deletes orphaned)
 */

require_once __DIR__ . '/../config/database.php';

// --- Directories to clean ---
$dirs = [
    $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/documents/',
    $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/images/',
    $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/profile_images/',
    $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/property_images/',
    $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/draft/' // ✅ Added draft directory
];

// --- Step 1: Identify valid user IDs (those we KEEP) ---
$validUserIds = [];
$rejectedUserIds = [];

// 1. All users from users table → always valid
$res = mysqli_query($conn, "SELECT id FROM users");
while ($row = mysqli_fetch_assoc($res)) {
    $validUserIds[] = (int)$row['id'];
}

// 2. Applications: mark users with pending/approved apps as valid, rejected as rejectable
$res = mysqli_query($conn, "SELECT user_id, status FROM applications WHERE user_id IS NOT NULL");
while ($row = mysqli_fetch_assoc($res)) {
    $uid = (int)$row['user_id'];
    $status = strtolower(trim($row['status'] ?? ''));

    if ($status === 'pending' || $status === 'approved' || $status === '') {
        $validUserIds[] = $uid;
    } elseif ($status === 'rejected') {
        $rejectedUserIds[] = $uid;
    }
}

// Unique
$validUserIds = array_unique(array_filter($validUserIds));
$rejectedUserIds = array_unique(array_filter($rejectedUserIds));

// --- Step 2: Gather file references from users table ---
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

// --- Step 3: Gather file references from applications ---
$appFilePaths = []; // keep
$rejFilePaths = []; // delete

$appColumns = [
    'broker_license_path',
    'prc_license_path',
    'resume_path',
    'valid_id_path',
    'additional_docs_path',
    'profile_image_path'
];

$res = mysqli_query($conn, "SELECT " . implode(',', $appColumns) . ", status, user_id FROM applications");

while ($row = mysqli_fetch_assoc($res)) {
    $status = strtolower(trim($row['status'] ?? ''));
    $uid = (int)($row['user_id'] ?? 0);

    $paths = [];
    foreach ($appColumns as $col) {
        if (empty($row[$col])) continue;

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
    }

    // Determine whether to keep or delete based on status + user validity
    foreach ($paths as $p) {
        if (
            in_array($uid, $validUserIds, true) && 
            ($status === 'pending' || $status === 'approved' || $status === '')
        ) {
            $appFilePaths[] = $p; // keep
        } elseif ($status === 'rejected' && !in_array($uid, $validUserIds, true)) {
            $rejFilePaths[] = $p; // delete
        }
    }
}

// --- Step 4: Property image paths ---
$propertyFilePaths = [];
$res = mysqli_query($conn, "
    SELECT pi.image_path, p.user_id, p.agent_id
    FROM property_images pi
    LEFT JOIN properties p ON p.id = pi.property_id
");

while ($row = mysqli_fetch_assoc($res)) {
    $img = trim($row['image_path'] ?? '');
    if (empty($img)) continue;

    $absPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($img, '/');
    $uid = (int)($row['user_id'] ?? 0);
    $aid = (int)($row['agent_id'] ?? 0);

    $ownerValid = in_array($uid, $validUserIds, true) || in_array($aid, $validUserIds, true);

    if ($ownerValid) {
        $propertyFilePaths[] = $absPath; // keep
    } else {
        $rejFilePaths[] = $absPath; // invalid owner → delete
    }
}

// --- Step 5: Property draft images ---
$draftFilePaths = [];
$res = mysqli_query($conn, "
    SELECT image_path, user_id 
    FROM property_drafts 
    WHERE image_path IS NOT NULL AND image_path != ''
");

while ($row = mysqli_fetch_assoc($res)) {
    $path = trim($row['image_path']);
    $uid = (int)$row['user_id'];

    $absPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
    $ownerValid = in_array($uid, $validUserIds, true);

    if ($ownerValid) {
        $draftFilePaths[] = $absPath; // keep
    } else {
        $rejFilePaths[] = $absPath; // invalid user → delete
    }
}

// --- Step 6: Build lookups ---
$userLookup  = array_flip(array_unique($userFilePaths));
$appLookup   = array_flip(array_unique($appFilePaths));
$propLookup  = array_flip(array_unique($propertyFilePaths));
$draftLookup = array_flip(array_unique($draftFilePaths));
$rejLookup   = array_flip(array_unique($rejFilePaths));

// --- Step 7: Iterate directories and clean ---
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;

        $fullPath = str_replace(['\\', '//'], '/', $file->getPathname());

        // Keep if referenced
        if (
            isset($userLookup[$fullPath]) ||
            isset($appLookup[$fullPath]) ||
            isset($propLookup[$fullPath]) ||
            isset($draftLookup[$fullPath])
        ) {
            continue;
        }

        // Delete if orphaned or in rejected list
        if (isset($rejLookup[$fullPath]) || (
            !isset($userLookup[$fullPath]) &&
            !isset($appLookup[$fullPath]) &&
            !isset($propLookup[$fullPath]) &&
            !isset($draftLookup[$fullPath])
        )) {
            @unlink($fullPath);
        }
    }
}
?>
