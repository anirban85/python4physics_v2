<?php
/**
 * Python4Physics - Resilient Database Connection
 */
$db_host = getenv('DB_HOST') ?: "localhost";
$db_user = getenv('DB_USER') ?: "root";
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$db_name = getenv('DB_NAME') ?: "python4p";
$db_port = getenv('DB_PORT') ?: 3306;

if (file_exists(__DIR__ . '/db_config.php')) {
    require_once __DIR__ . '/db_config.php';
}

if (!isset($conn) || $conn === null) {
    try {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $conn = new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        // Fallback or friendly error
        error_log("Database Connection Error: " . $e->getMessage());
        $db_error = $e->getMessage();
    }
}