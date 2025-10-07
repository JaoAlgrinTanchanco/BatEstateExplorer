<?php
/**
 * cleanup_accounts.php
 * Removes orphaned user-related files when the user no longer exists.
 */

require_once __DIR__ . '/../config/database.php';

// Base upload directories
$basePaths = [
    'documents'      => $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/documents/',
    'images'         => $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/images/',
    'profile_images' => $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/profile_images/'
];

// --- STEP 1: Collect all valid file paths from DB ---
$validPaths = [];

$query = "
    SELECT 
        id,
        profile_image_path,
        broker_license_path,
        prc_license_path,
        resume_path,
        valid_id_path,
        additional_docs_path
    FROM users
";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    foreach ($row as $col => $val) {
        if (in_array($col, ['id'])) continue;
        if (empty($val)) continue;

        if ($col === 'additional_docs_path') {
            // JSON or CSV of multiple docs
            $docs = json_decode($val, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($docs)) {
                foreach ($docs as $doc) {
                    $validPaths[] = realpath($_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/' . ltrim($doc, '/'));
                }
            } else {
                // fallback: comma-separated
                foreach (array_filter(explode(',', $val)) as $p) {
                    $validPaths[] = realpath($_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/' . ltrim($p, '/'));
                }
            }
        } else {
            $validPaths[] = realpath($_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/' . ltrim($val, '/'));
        }
    }
}
$validPaths = array_filter($validPaths); // remove nulls
$validPaths = array_unique($validPaths);

// --- STEP 2: Scan each upload directory ---
foreach ($basePaths as $folder => $dir) {
    if (!is_dir($dir)) continue;
    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $fullPath = realpath($dir . $file);
        if (!$fullPath) continue;

        // If this file path is NOT in DB, delete it
        if (!in_array($fullPath, $validPaths, true)) {
            if (is_file($fullPath)) {
                if (@unlink($fullPath)) {
                    error_log("[Account Cleanup] Deleted orphaned file: $fullPath");
                } else {
                    error_log("[Account Cleanup] Failed to delete: $fullPath");
                }
            }
        }
    }
}
