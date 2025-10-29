<?php
require_once '../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenvPath = __DIR__ . '/..';
if (class_exists('Dotenv\Dotenv')) {
    if (method_exists('Dotenv\Dotenv', 'createImmutable')) {
        $dotenv = Dotenv::createImmutable($dotenvPath);
    } else {
        $dotenv = new Dotenv($dotenvPath);
    }
    $dotenv->load();
}

// Initialize Google Client
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope('email');
$client->addScope('profile');

// Redirect user to Google login
header('Location: ' . $client->createAuthUrl());
exit;
