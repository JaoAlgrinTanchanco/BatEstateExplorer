<?php
/**
 * Simple Redirect Helper Functions
 * These functions provide direct, simple redirects without complex controller logic
 */

// User type redirects - using hardcoded paths that work
function redirect_by_user_type($user_type) {
    switch ($user_type) {
        case 'admin':
            header('Location: ../public/controllers/admin_dashboard.php');
            break;
        case 'direct_agent':
        case 'associate_agent':
            header('Location: ../public/agent/agent_dashboard.php');
            break;
        default:
            header('Location: ../public/user/user_dashboard.php');
    }
    exit;
}

// Simple page redirects
function redirect_to($page) {
    header("Location: $page");
    exit;
}

// Common redirects - using hardcoded paths
function redirect_to_login() {
    header('Location: ../auth/login.php');
    exit;
}

function redirect_to_home() {
    header('Location: ../index.php');
    exit;
}

function redirect_to_dashboard() {
    header('Location: ../public/dashboard.php');
    exit;
}
