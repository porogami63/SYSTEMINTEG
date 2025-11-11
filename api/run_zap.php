<?php
/**
 * Run OWASP ZAP scan and return JSON status
 */
require_once '../config.php';

header('Content-Type: application/json');

try {
	if (!isLoggedIn() || !isWebAdmin()) {
		http_response_code(403);
		echo JsonHelper::encode(['status' => 'error', 'message' => 'Forbidden']);
		exit;
	}

	$projectRoot = realpath(__DIR__ . '/..');
	// Determine Python command (try common Windows setups)
	$python = 'python';
	// Try py launcher if available
	if (stripos(PHP_OS, 'WIN') === 0) {
		$checkPy = shell_exec('where py');
		if ($checkPy) {
			$python = 'py -3';
		}
	}
	$zapScript = $projectRoot . DIRECTORY_SEPARATOR . 'security_audit' . DIRECTORY_SEPARATOR . 'zap.py';

	if (!file_exists($zapScript)) {
		echo JsonHelper::encode(['status' => 'error', 'message' => 'ZAP script not found']);
		exit;
	}

	$target = rtrim(SITE_URL, '/');

	// Quick connectivity check to ZAP
	$zapUrl = 'http://127.0.0.1:8080';
	$ch = curl_init($zapUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	$ping = curl_exec($ch);
	$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($http !== 200 || !$ping) {
		echo JsonHelper::encode([
			'status' => 'error',
			'message' => 'OWASP ZAP is not reachable on localhost:8080. Start ZAP (desktop or daemon) and try again.',
			'details' => ['url' => $zapUrl, 'http' => $http]
		]);
		exit;
	}

	$cmd = escapeshellcmd($python) . ' ' . escapeshellarg($zapScript) . ' --target ' . escapeshellarg($target);
	if (defined('ZAP_API_KEY') && ZAP_API_KEY) {
		$cmd .= ' --apikey ' . escapeshellarg(ZAP_API_KEY);
	}
	$cmd .= ' 2>&1';
	$output = shell_exec($cmd);

	$reportPath = $projectRoot . DIRECTORY_SEPARATOR . 'security_audit' . DIRECTORY_SEPARATOR . 'zap_report.html';
	$ok = file_exists($reportPath);

	if ($ok) {
		// Create an audit history entry referencing the ZAP report
		try {
			SecurityAuditor::recordZapScan($_SESSION['user_id'] ?? null, [
				'summary' => 'ZAP scan finished',
				'html' => 'security_audit/zap_report.html',
				'json' => 'security_audit/zap_report.json'
			]);
		} catch (Exception $e) {
			// ignore logging failure
		}
		echo JsonHelper::encode([
			'status' => 'success',
			'message' => 'ZAP scan completed. Use the ZAP export buttons to download the report.',
			'log' => $output
		]);
	} else {
		echo JsonHelper::encode([
			'status' => 'error',
			'message' => 'ZAP scan did not produce a report. Ensure ZAP is running and accessible on localhost:8080.',
			'log' => $output
		]);
	}
} catch (Exception $e) {
	echo JsonHelper::encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>

