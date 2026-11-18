<?php
/**
 * Database Connection Configuration
 * Barangay Health Outreach Management System
 * 
 * Supports both:
 * 1. Local MySQL / MariaDB (XAMPP default localhost:3306)
 * 2. TiDB Cloud (Serverless & Dedicated over TLS/SSL)
 */

// 0. Auto-load local .env file if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (getenv($key) === false) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
            }
        }
    }
}

// 1. Connection parameter resolution (Environment variables or default fallbacks)
$dbUrl = getenv('DATABASE_URL') ?: (getenv('TIDB_DATABASE_URL') ?: null);

if (!empty($dbUrl)) {
    $parsed = parse_url($dbUrl);
    $host     = $parsed['host'] ?? 'localhost';
    $port     = (int)($parsed['port'] ?? 3306);
    $username = isset($parsed['user']) ? urldecode($parsed['user']) : 'root';
    $password = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
    $dbname   = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'barangay_health_db';
} else {
    $host     = getenv('DB_HOST') ?: (getenv('TIDB_HOST') ?: 'localhost');
    $defaultPort = (stripos($host, 'tidb') !== false) ? 4000 : 3306;
    $port     = (int)(getenv('DB_PORT') ?: (getenv('TIDB_PORT') ?: $defaultPort));
    $dbname   = getenv('DB_NAME') ?: (getenv('TIDB_DATABASE') ?: (getenv('DB_DATABASE') ?: 'barangay_health_db'));
    $username = getenv('DB_USER') ?: (getenv('TIDB_USER') ?: (getenv('DB_USERNAME') ?: 'root'));
    
    // Check possible password variable keys
    $envPass = getenv('DB_PASSWORD');
    if ($envPass === false) $envPass = getenv('TIDB_PASSWORD');
    if ($envPass === false) $envPass = getenv('DB_PASS');
    $password = ($envPass !== false) ? $envPass : '';
}

// 2. PDO Options Configuration
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 3. SSL Configuration (Required for TiDB Cloud Serverless)
$isRemote = !in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
$sslCa    = getenv('DB_SSL_CA') ?: (getenv('TIDB_SSL_CA') ?: null);

if (!empty($sslCa) && file_exists($sslCa)) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
} elseif ($isRemote || stripos($host, 'tidb') !== false) {
    // Detect standard system Certificate Authority (CA) bundles (Linux / Vercel AWS Lambda / Debian)
    $systemCertBundles = [
        '/etc/pki/tls/certs/ca-bundle.crt',     // Amazon Linux / RHEL / CentOS (used on Vercel AWS Lambda)
        '/etc/ssl/certs/ca-certificates.crt', // Debian / Ubuntu
        '/etc/ssl/cert.pem',                  // macOS / Alpine
    ];
    $certFound = false;
    foreach ($systemCertBundles as $bundle) {
        if (file_exists($bundle)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $bundle;
            $certFound = true;
            break;
        }
    }
    // If running remotely without local CA bundle file (e.g. Windows testing TiDB Cloud directly)
    if (!$certFound && (getenv('DB_SSL_VERIFY') === 'false' || stripos(PHP_OS, 'WIN') === 0)) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        $options
    );
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());

    if (php_sapi_name() === 'cli' || getenv('APP_DEBUG') === 'true' || isset($_GET['debug'])) {
        die("Database connection failed: " . htmlspecialchars($e->getMessage()) . "\n");
    }

    die("Database connection failed. Please verify your database connection credentials.");
}
