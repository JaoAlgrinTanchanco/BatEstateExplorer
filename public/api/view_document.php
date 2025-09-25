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

$filePath = __DIR__ . "/../../storage/uploads/documents/" . basename($_GET['file']);
if (!file_exists($filePath)) {
    http_response_code(404);
    exit("File not found");
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
readfile($filePath);
