<?php
/**
 * Simple migration runner. Run from project root:
 * php migrations/migrate.php
 *
 * It will execute statements in the SQL file(s) under migrations/ and report results.
 */

require_once __DIR__ . '/../config.php';

function runSqlStatements(PDO $pdo, string $sql)
{
    // Split by semicolon for simple statements. This is not a full SQL parser; keep migrations simple.
    $parts = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($parts as $part) {
        if ($part === '') continue;
        try {
            $pdo->exec($part);
            echo "OK: " . (strlen($part) > 70 ? substr($part,0,70) . '...' : $part) . "\n";
        } catch (PDOException $e) {
            // Non-fatal: show message and continue
            echo "WARN: " . $e->getMessage() . "\n";
        }
    }
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    echo "Failed to connect to DB: " . $e->getMessage() . "\n";
    exit(1);
}

$migrationFiles = glob(__DIR__ . '/*.sql');
if (empty($migrationFiles)) {
    echo "No migration files found in migrations/\n";
    exit(0);
}

foreach ($migrationFiles as $file) {
    echo "Applying migration: " . basename($file) . "\n";
    $sql = file_get_contents($file);
    runSqlStatements($pdo, $sql);
}

echo "Migrations complete.\n";

?>
