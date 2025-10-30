<?php
/**
 * Basic test runner for core utilities.
 * Run from command line: php tests/run_tests.php
 */

require_once __DIR__ . '/../config.php';

echo "Running basic system tests...\n";

$passed = 0;
$failed = 0;

// Test Database connection
try {
    $db = Database::getInstance();
    $row = $db->fetch('SELECT 1 AS ok');
    if (!empty($row['ok'])) {
        echo "[PASS] Database connection\n";
        $passed++;
    } else {
        echo "[FAIL] Database query did not return expected result\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "[FAIL] Database connection: " . $e->getMessage() . "\n";
    $failed++;
}

// Test JsonHelper
try {
    $data = ['foo' => 'bar', 'num' => 1];
    $json = JsonHelper::encode($data);
    $decoded = JsonHelper::decode($json);
    if ($decoded['foo'] === 'bar') {
        echo "[PASS] JsonHelper encode/decode\n";
        $passed++;
    } else {
        echo "[FAIL] JsonHelper mismatch\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "[FAIL] JsonHelper: " . $e->getMessage() . "\n";
    $failed++;
}

// Test FileProcessor saveStringToFile
try {
    $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mediarchive_test_' . time() . '.txt';
    $saved = FileProcessor::saveStringToFile('hello', $tmpPath);
    if (file_exists($saved)) {
        echo "[PASS] FileProcessor saveStringToFile\n";
        unlink($saved);
        $passed++;
    } else {
        echo "[FAIL] FileProcessor did not create file\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "[FAIL] FileProcessor: " . $e->getMessage() . "\n";
    $failed++;
}

// Test HttpClient (simple GET against local JSON endpoint expected to return error without cert_id)
try {
    $url = rtrim(SITE_URL, '/') . '/api/json.php';
    $res = HttpClient::get($url);
    if (is_array($res) && isset($res['body'])) {
        echo "[PASS] HttpClient GET (received response)\n";
        $passed++;
    } else {
        echo "[FAIL] HttpClient GET unexpected response\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "[WARN] HttpClient GET failed (network or remote): " . $e->getMessage() . "\n";
}

// Test SOAP (best-effort against local WSDL)
try {
    $wsdl = rtrim(SITE_URL, '/') . '/api/soap_server.php?wsdl';
    if (extension_loaded('soap')) {
        $client = @new SoapClient($wsdl, ['cache_wsdl' => WSDL_CACHE_NONE]);
        // call with a fake cert_id to ensure service responds
        try {
            $resp = $client->ValidateCertificate(['cert_id' => 'DUMMY']);
            echo "[PASS] SOAP client call (service responded)\n";
            $passed++;
        } catch (Exception $e) {
            echo "[WARN] SOAP client call failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "[SKIP] SOAP extension not enabled in PHP; skipping SOAP test\n";
    }
} catch (Exception $e) {
    echo "[WARN] SOAP test error: " . $e->getMessage() . "\n";
}

echo "\nTests completed. Passed: $passed, Failed: $failed\n";

if ($failed > 0) {
    exit(1);
}

exit(0);

?>
