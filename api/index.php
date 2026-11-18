<?php
/**
 * Vercel Serverless Front Controller & Router
 * Barangay Health Outreach Management System
 * 
 * Routes incoming web requests to the appropriate public scripts
 * (index.php, login.php, logout.php, test-db.php) while preserving query parameters.
 */

// Parse incoming request path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Strip "public/" prefix if present
if (str_starts_with($path, 'public/')) {
    $path = substr($path, 7);
}

// Map route to target script
switch ($path) {
    case '':
    case 'index':
    case 'index.php':
    case 'home':
    case 'home.php':
        $target = __DIR__ . '/../public/index.php';
        break;

    case 'login':
    case 'login.php':
        $target = __DIR__ . '/../public/login.php';
        break;

    case 'logout':
    case 'logout.php':
        $target = __DIR__ . '/../public/logout.php';
        break;

    case 'test-db':
    case 'test-db.php':
        $target = __DIR__ . '/../public/test-db.php';
        break;

    default:
        // Check if file exists in public/
        $candidate = __DIR__ . '/../public/' . $path;
        if (file_exists($candidate) && is_file($candidate)) {
            $target = $candidate;
        } else {
            // Default fallback to index.php
            $target = __DIR__ . '/../public/index.php';
        }
        break;
}

// Set working directory to public folder so relative inclusions work seamlessly
chdir(dirname($target));
require $target;
