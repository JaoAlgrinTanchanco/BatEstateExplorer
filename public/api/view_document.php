<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Not authorized");
}

$allowed = ['broker_license_path','prc_license_path','resume_path','valid_id_path','additional_docs_path'];
$field = $_GET['field'] ?? '';
if (!in_array($field, $allowed)) {
    http_response_code(400);
    exit("Invalid request");
}

$filename = basename($_GET['file']);

// Try documents first
$docPath = __DIR__ . "/../../storage/uploads/documents/" . $filename;
$imgPath = __DIR__ . "/../../storage/uploads/images/" . $filename;

if (file_exists($docPath)) {
    $filePath = $docPath;
} elseif (file_exists($imgPath)) {
    $filePath = $imgPath;
} else {
    http_response_code(404);
    exit("File not found");
}

// Detect MIME type automatically
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $filePath);
finfo_close($finfo);

header("Content-Type: $mime");
header('Content-Disposition: inline; filename="' . $filename . '"');
readfile($filePath);
exit;
