<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_login();

use App\Controllers\UserController;

$controller = new UserController();
$view = $_GET['view'] ?? 'home';

switch ($view) {
    case 'home':
        $controller->home();
        break;
    case 'profile':
        $controller->profile();
        break;
    case 'search':
        $controller->search();
        break;
    default:
        $controller->home();
}


