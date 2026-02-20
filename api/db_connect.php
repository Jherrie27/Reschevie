<?php
// ============================================================
// db_connect.php — Database connection
// ============================================================

// ============================================================
// If using .env, use this code block:
// $envPath = __DIR__ . '/../.env';
//
// if (!file_exists($envPath)) {
//     error_log('[RESCHEVIE] .env file not found at: ' . $envPath);
//     http_response_code(500);
//     die(json_encode(['success' => false, 'message' => 'Server configuration error']));
// }
//
// $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
// foreach ($lines as $line) {
//     if (str_starts_with(trim($line), '#')) continue;
//     [$key, $value] = explode('=', $line, 2) + [null, null];
//     if ($key && $value !== null) {
//         $_ENV[trim($key)] = trim($value);
//     }
// }
//
// define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
// define('DB_USER', $_ENV['DB_USER'] ?? '');
// define('DB_PASS', $_ENV['DB_PASS'] ?? '');
// define('DB_NAME', $_ENV['DB_NAME'] ?? '');

// ============================================================
// Else, use this
define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // pachange
define('DB_PASS', '');             // pachange
define('DB_NAME', 'reschevie_db');
// ============================================================

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log('[RESCHEVIE] DB connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$conn->set_charset('utf8mb4');

// ============================================================
// CONNECTIVITY TEST - testing only, remove sa prod
// ============================================================
if (isset($_GET['test'])) {
    header('Content-Type: application/json');
    $checks = [];

    $checks['connection'] = $conn->ping()
        ? ['ok' => true,  'message' => 'Connected to MySQL']
        : ['ok' => false, 'message' => 'Ping failed'];

    $dbResult = $conn->query("SELECT DATABASE() AS db");
    $dbRow     = $dbResult->fetch_assoc();
    $checks['database'] = $dbRow['db'] === DB_NAME
        ? ['ok' => true,  'message' => "Using database: {$dbRow['db']}"]
        : ['ok' => false, 'message' => "Wrong database: {$dbRow['db']}"];

    $requiredTables = ['users', 'admins', 'products', 'product_images', 'inquiries', 'inquiry_items', 'client_stories', 'newsletters'];
    $tableResult    = $conn->query("SHOW TABLES");
    $existingTables = [];
    while ($row = $tableResult->fetch_array()) {
        $existingTables[] = $row[0];
    }

    $missingTables = array_diff($requiredTables, $existingTables);
    $checks['tables'] = count($missingTables) === 0
        ? ['ok' => true,  'message' => 'All required tables found']
        : ['ok' => false, 'message' => 'Missing tables: ' . implode(', ', $missingTables)];

    $charsetResult = $conn->query("SELECT @@character_set_database AS charset");
    $charsetRow    = $charsetResult->fetch_assoc();
    $checks['charset'] = $charsetRow['charset'] === 'utf8mb4'
        ? ['ok' => true,  'message' => 'Charset: utf8mb4']
        : ['ok' => false, 'message' => "Charset is '{$charsetRow['charset']}', expected utf8mb4"];

    $allOk = array_reduce($checks, fn($carry, $c) => $carry && $c['ok'], true);

    echo json_encode([
        'success' => $allOk,
        'checks'  => $checks
    ], JSON_PRETTY_PRINT);
    exit;
}