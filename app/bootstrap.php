<?php
// Global bootstrap for BatEstateExplorer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/redirects.php';

// Simple guard helpers
function require_login(): void {
    if (!is_logged_in()) {
        redirect_to_login();
    }
}

function require_admin($conn): void {
    require_login();
    $user = get_logged_in_user($conn);
    if (!$user || $user['user_type'] !== 'admin') {
        redirect_to_login();
    }
}

function current_user($conn) {
    return get_logged_in_user($conn);
}


