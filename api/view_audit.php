<?php
/**
 * View Audit Certificate API
 * Provides audit report viewing and export functionality (JSON, XML, PDF)
 */
require_once '../config.php';

// Only web_admin can access audit reports
if (!isLoggedIn() || !isWebAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$auditId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$format = isset($_GET['format']) ? sanitizeInput($_GET['format']) : 'html';
$isZap = isset($_GET['zap']) ? true : false;

try {
    // If exporting ZAP report, handle separately
    if ($isZap) {
        $root = realpath(__DIR__ . '/..');
        $htmlPath = $root . '/security_audit/zap_report.html';
        $jsonPath = $root . '/security_audit/zap_report.json';
        
        if (!file_exists($htmlPath) && !file_exists($jsonPath)) {
            http_response_code(404);
            echo json_encode(['error' => 'ZAP report not found. Run a ZAP scan first.']);
            exit;
        }
        
        switch ($format) {
            case 'json':
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="zap_report.json"');
                if (ob_get_level()) { ob_clean(); }
                if (file_exists($jsonPath)) {
                    readfile($jsonPath);
                } else {
                    echo json_encode(['message' => 'JSON not available; run scan again.']);
                }
                break;
            case 'xml':
                // Convert JSON to XML
                header('Content-Type: application/xml; charset=UTF-8');
                header('Content-Disposition: attachment; filename="zap_report.xml"');
                if (ob_get_level()) { ob_clean(); }
                $zapJson = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : ['message' => 'JSON not available'];
                echo XmlHandler::arrayToXml($zapJson, 'zap_report');
                break;
            case 'pdf':
                // Render the HTML report to PDF
                require_once '../includes/dompdf/dompdf/autoload.inc.php';
                $options = new Dompdf\Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', true);
                $dompdf = new Dompdf\Dompdf($options);
                $html = file_exists($htmlPath) ? file_get_contents($htmlPath) : '<h1>ZAP Report</h1><p>HTML report missing.</p>';
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                if (ob_get_level()) { ob_clean(); }
                $dompdf->stream('zap_report.pdf', ['Attachment' => true]);
                break;
            case 'html':
            default:
                // Display a small wrapper that summarizes and embeds the ZAP HTML
                $summary = 'OWASP ZAP Report';
                if (file_exists($jsonPath)) {
                    $zap = json_decode(file_get_contents($jsonPath), true);
                    $high = $medium = $low = $info = 0;
                    if (isset($zap['site']) && is_array($zap['site'])) {
                        foreach ($zap['site'] as $s) {
                            if (!empty($s['alerts'])) {
                                foreach ($s['alerts'] as $a) {
                                    $risk = strtolower($a['riskdesc'] ?? ($a['risk'] ?? ''));
                                    if (strpos($risk, 'high') !== false) $high++; elseif (strpos($risk, 'medium') !== false) $medium++; elseif (strpos($risk, 'low') !== false) $low++; else $info++;
                                }
                            }
                        }
                    }
                    $summary = sprintf('OWASP ZAP Report Summary - High %d, Medium %d, Low %d, Info %d', $high, $medium, $low, $info);
                }
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>ZAP Report</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-3">';
                echo '<div class="container"><div class="mb-3"><h4>' . htmlspecialchars($summary) . '</h4>';
                echo '<div class="btn-group mb-2" role="group">';
                echo '<a class="btn btn-sm btn-danger" href="?zap=1&format=pdf&id=' . intval($auditId) . '">Export PDF</a>';
                echo '<a class="btn btn-sm btn-primary" href="?zap=1&format=json&id=' . intval($auditId) . '">Export JSON</a>';
                echo '<a class="btn btn-sm btn-warning" href="?zap=1&format=xml&id=' . intval($auditId) . '">Export XML</a>';
                echo '</div></div>';
                if (file_exists($htmlPath)) {
                    echo '<iframe style="width:100%;height:85vh;border:1px solid #ccc;border-radius:6px;" src="../security_audit/zap_report.html"></iframe>';
                } else {
                    echo '<div class="alert alert-warning">ZAP report HTML not found. Run a ZAP scan first.</div>';
                }
                echo '</div></body></html>';
                break;
        }
        exit;
    }
    
    if (!$auditId) {
        http_response_code(400);
        echo json_encode(['error' => 'Audit ID required']);
        exit;
    }
    
    // Get audit report
    $audit = SecurityAuditor::getAuditReport($auditId);
    
    if (!$audit) {
        http_response_code(404);
        echo json_encode(['error' => 'Audit report not found']);
        exit;
    }
    
    $auditData = json_decode($audit['audit_data'], true);
    
    // Handle different formats
    switch ($format) {
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="security_audit_' . $auditId . '.json"');
            echo json_encode([
                'audit_id' => $audit['id'],
                'audit_date' => $audit['created_at'],
                'auditor' => $audit['full_name'] ?? $audit['username'] ?? 'System',
                'score' => $audit['score'],
                'status' => $audit['status'],
                'checks' => $auditData['checks'] ?? [],
                'manual_tests' => $auditData['manual_tests'] ?? [],
                'vulnerabilities' => $auditData['vulnerabilities'] ?? [],
                'recommendations' => $auditData['recommendations'] ?? []
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'xml':
            // Clear any output buffer to prevent XML declaration error
            if (ob_get_level()) {
                ob_clean();
            }
            
            header('Content-Type: application/xml; charset=UTF-8');
            header('Content-Disposition: attachment; filename="security_audit_' . $auditId . '.xml"');
            
            $xml = XmlHandler::arrayToXml([
                'audit_id' => $audit['id'],
                'audit_date' => $audit['created_at'],
                'auditor' => $audit['full_name'] ?? $audit['username'] ?? 'System',
                'score' => $audit['score'],
                'status' => $audit['status'],
                'checks' => $auditData['checks'] ?? [],
                'manual_tests' => $auditData['manual_tests'] ?? [],
                'vulnerabilities' => $auditData['vulnerabilities'] ?? [],
                'recommendations' => $auditData['recommendations'] ?? []
            ], 'security_audit');
            echo $xml;
            
            // Log after output
            AuditLogger::log(
                'SECURITY_AUDIT_VIEW',
                'security_audit',
                $auditId,
                ['format' => 'xml'],
                $_SESSION['user_id'] ?? null
            );
            break;
            
        case 'pdf':
            // Generate PDF audit certificate
            require_once '../includes/dompdf/dompdf/autoload.inc.php';
        
            $options = new Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new Dompdf\Dompdf($options);
            
            // Build HTML for PDF
            $html = generateAuditPDF($audit, $auditData);
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('security_audit_' . $auditId . '.pdf', ['Attachment' => true]);
            break;
            
        case 'html':
        default:
            // Return HTML view
            echo generateAuditHTML($audit, $auditData);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve audit report: ' . $e->getMessage()]);
    error_log('View audit error: ' . $e->getMessage());
}

/**
 * Generate HTML view for audit certificate
 */
function generateAuditHTML($audit, $auditData) {
    $auditId = $audit['id'];
    $score = $audit['score'];
    $status = $audit['status'];
    $auditor = SecurityManager::escapeOutput($audit['full_name'] ?? $audit['username'] ?? 'System');
    $date = date('F d, Y H:i:s', strtotime($audit['created_at']));
    
    $statusColor = $status === 'excellent' ? '#00ff88' : 
                   ($status === 'good' ? '#00a8ff' : 
                   ($status === 'fair' ? '#ffaa00' : '#ff4444'));
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit Certificate #' . $auditId . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #1a1a1a; color: #e0e0e0; padding: 2rem; }
        .audit-certificate { max-width: 900px; margin: 0 auto; background: #252525; padding: 3rem; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .cert-header { text-align: center; border-bottom: 3px solid ' . $statusColor . '; padding-bottom: 2rem; margin-bottom: 2rem; }
        .cert-title { font-size: 2.5rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .cert-subtitle { color: #aaa; font-size: 1.1rem; }
        .score-display { font-size: 4rem; font-weight: 700; color: ' . $statusColor . '; text-shadow: 0 0 20px ' . $statusColor . '80; }
        .status-badge { display: inline-block; padding: 0.5rem 1.5rem; background: ' . $statusColor . '; color: #000; font-weight: 700; border-radius: 25px; font-size: 1.2rem; }
        .check-item { padding: 1rem; margin-bottom: 0.5rem; border-radius: 5px; border-left: 4px solid; }
        .check-passed { background: rgba(0, 255, 136, 0.1); border-color: #00ff88; }
        .check-failed { background: rgba(255, 68, 68, 0.1); border-color: #ff4444; }
        .section-title { color: #fff; font-weight: 600; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid #333; padding-bottom: 0.5rem; }
        .export-buttons { margin-top: 2rem; text-align: center; }
        .export-buttons .btn { margin: 0 0.5rem; }
        .description-text { color: #aaa; font-size: 0.9rem; font-style: italic; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="audit-certificate">
        <div class="cert-header">
            <div class="cert-title"><i class="bi bi-shield-check"></i> Security Audit Certificate</div>
            <div class="cert-subtitle">MediArchive System Security Assessment</div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Audit ID:</strong> #' . $auditId . '</p>
                <p><strong>Audit Date:</strong> ' . $date . '</p>
                <p><strong>Conducted By:</strong> ' . $auditor . '</p>
            </div>
            <div class="col-md-6 text-center">
                <div class="score-display">' . $score . '<small style="font-size: 2rem; color: #aaa;">/100</small></div>
                <div class="status-badge">' . strtoupper($status) . '</div>
            </div>
        </div>
        
        <div class="section-title"><i class="bi bi-list-check"></i> Security Checks Performed</div>';
    
    foreach ($auditData['checks'] ?? [] as $checkName => $check) {
        $isPassed = isset($check['protected']) ? $check['protected'] : 
                   (isset($check['secure']) ? $check['secure'] : 
                   (isset($check['enabled']) ? $check['enabled'] : 
                   (isset($check['complete']) ? $check['complete'] : 
                   (isset($check['comprehensive']) ? $check['comprehensive'] : false))));
        
        $checkClass = $isPassed ? 'check-passed' : 'check-failed';
        $checkIcon = $isPassed ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>';
        $checkTitle = ucfirst(str_replace('_', ' ', $checkName));
        
        $html .= '<div class="check-item ' . $checkClass . '">
            <strong>' . $checkIcon . ' ' . $checkTitle . '</strong>';
        
        if (isset($check['method'])) {
            $html .= '<br><small>Method: ' . SecurityManager::escapeOutput($check['method']) . '</small>';
        }
        
        if (isset($check['description'])) {
            $html .= '<div class="description-text">' . SecurityManager::escapeOutput($check['description']) . '</div>';
        }
        
        if (isset($check['issue']) && $check['issue']) {
            $html .= '<br><small class="text-danger">Issue: ' . SecurityManager::escapeOutput($check['issue']) . '</small>';
        }
        
        $html .= '</div>';
    }
    
    if (!empty($auditData['vulnerabilities'])) {
        $html .= '<div class="section-title"><i class="bi bi-exclamation-triangle"></i> Vulnerabilities Found</div>
        <div class="alert alert-danger">';
        foreach ($auditData['vulnerabilities'] as $vuln) {
            $html .= '<div><i class="bi bi-arrow-right"></i> ' . SecurityManager::escapeOutput($vuln) . '</div>';
        }
        $html .= '</div>';
    }
    
    // Manual probes section
    if (!empty($auditData['manual_tests']) && !empty($auditData['manual_tests']['available'])) {
        $html .= '<div class="section-title"><i class="bi bi-terminal"></i> Manual Security Probes</div>';
        $summary = 'Passed: ' . intval($auditData['manual_tests']['passed'] ?? 0) . ' | Failed: ' . intval($auditData['manual_tests']['failed'] ?? 0);
        $html .= '<div class="alert alert-info"><strong>Summary:</strong> ' . SecurityManager::escapeOutput($summary) . '</div>';
        if (!empty($auditData['manual_tests']['raw_output'])) {
            $html .= '<pre style="background:#111;color:#0f0;padding:1rem;border-radius:6px;white-space:pre-wrap;">' . SecurityManager::escapeOutput($auditData['manual_tests']['raw_output']) . '</pre>';
        }
    }
    
    if (!empty($auditData['recommendations'])) {
        $html .= '<div class="section-title"><i class="bi bi-lightbulb"></i> Recommendations</div>
        <div class="alert alert-warning">';
        foreach ($auditData['recommendations'] as $rec) {
            $html .= '<div><i class="bi bi-arrow-right"></i> ' . SecurityManager::escapeOutput($rec) . '</div>';
        }
        $html .= '</div>';
    }
    
    $html .= '
        <div class="export-buttons">
            <a href="?id=' . $auditId . '&format=json" class="btn btn-primary"><i class="bi bi-filetype-json"></i> Export JSON</a>
            <a href="?id=' . $auditId . '&format=xml" class="btn btn-info"><i class="bi bi-filetype-xml"></i> Export XML</a>
            <a href="?id=' . $auditId . '&format=pdf" class="btn btn-danger"><i class="bi bi-file-pdf"></i> Export PDF</a>
        </div>
        
        <div class="text-center mt-4" style="color: #666; font-size: 0.9rem;">
            <p>This is an official security audit certificate generated by MediArchive System</p>
            <p>Document ID: AUDIT-' . $auditId . '-' . date('Ymd', strtotime($audit['created_at'])) . '</p>
        </div>
    </div>
</body>
</html>';
    
    return $html;
}

/**
 * Generate PDF content for audit certificate
 */
function generateAuditPDF($audit, $auditData) {
    $auditId = $audit['id'];
    $score = $audit['score'];
    $status = $audit['status'];
    $auditor = SecurityManager::escapeOutput($audit['full_name'] ?? $audit['username'] ?? 'System');
    $date = date('F d, Y H:i:s', strtotime($audit['created_at']));
    
    $statusColor = $status === 'excellent' ? '#00ff88' : 
                   ($status === 'good' ? '#00a8ff' : 
                   ($status === 'fair' ? '#ffaa00' : '#ff4444'));
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .cert-header { text-align: center; border-bottom: 3px solid ' . $statusColor . '; padding-bottom: 20px; margin-bottom: 20px; }
        .cert-title { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        .cert-subtitle { color: #666; font-size: 14px; }
        .info-row { margin: 10px 0; }
        .score-display { text-align: center; font-size: 48px; font-weight: bold; color: ' . $statusColor . '; margin: 20px 0; }
        .status-badge { display: inline-block; padding: 5px 15px; background: ' . $statusColor . '; color: #fff; font-weight: bold; border-radius: 15px; }
        .section-title { font-weight: bold; margin-top: 20px; margin-bottom: 10px; border-bottom: 2px solid #ccc; padding-bottom: 5px; }
        .check-item { padding: 10px; margin-bottom: 5px; border-left: 4px solid; }
        .check-passed { background: #e8f5e9; border-color: #4caf50; }
        .check-failed { background: #ffebee; border-color: #f44336; }
        .description-text { color: #666; font-size: 11px; font-style: italic; margin-top: 5px; }
        .footer { text-align: center; margin-top: 30px; color: #999; font-size: 10px; }
    </style>
</head>
<body>
    <div class="cert-header">
        <div class="cert-title">Security Audit Certificate</div>
        <div class="cert-subtitle">MediArchive System Security Assessment</div>
    </div>
    
    <div class="info-row"><strong>Audit ID:</strong> #' . $auditId . '</div>
    <div class="info-row"><strong>Audit Date:</strong> ' . $date . '</div>
    <div class="info-row"><strong>Conducted By:</strong> ' . $auditor . '</div>
    
    <div class="score-display">' . $score . '/100</div>
    <div style="text-align: center;"><span class="status-badge">' . strtoupper($status) . '</span></div>
    
    <div class="section-title">Security Checks Performed</div>';
    
    foreach ($auditData['checks'] ?? [] as $checkName => $check) {
        $isPassed = isset($check['protected']) ? $check['protected'] : 
                   (isset($check['secure']) ? $check['secure'] : 
                   (isset($check['enabled']) ? $check['enabled'] : 
                   (isset($check['complete']) ? $check['complete'] : 
                   (isset($check['comprehensive']) ? $check['comprehensive'] : false))));
        
        $checkClass = $isPassed ? 'check-passed' : 'check-failed';
        $checkIcon = $isPassed ? '✓' : '✗';
        $checkTitle = ucfirst(str_replace('_', ' ', $checkName));
        
        $html .= '<div class="check-item ' . $checkClass . '">
            <strong>' . $checkIcon . ' ' . $checkTitle . '</strong>';
        
        if (isset($check['method'])) {
            $html .= '<br><small>Method: ' . SecurityManager::escapeOutput($check['method']) . '</small>';
        }
        
        if (isset($check['description'])) {
            $html .= '<div class="description-text">' . SecurityManager::escapeOutput($check['description']) . '</div>';
        }
        
        if (isset($check['issue']) && $check['issue']) {
            $html .= '<br><small style="color: #f44336;">Issue: ' . SecurityManager::escapeOutput($check['issue']) . '</small>';
        }
        
        $html .= '</div>';
    }
    
    if (!empty($auditData['vulnerabilities'])) {
        $html .= '<div class="section-title">Vulnerabilities Found</div>';
        foreach ($auditData['vulnerabilities'] as $vuln) {
            $html .= '<div style="margin: 5px 0;">• ' . SecurityManager::escapeOutput($vuln) . '</div>';
        }
    }
    // Manual probes section for PDF
    if (!empty($auditData['manual_tests']) && !empty($auditData['manual_tests']['available'])) {
        $html .= '<div class="section-title">Manual Security Probes</div>';
        $html .= '<div>Passed: ' . intval($auditData['manual_tests']['passed'] ?? 0) . ' | Failed: ' . intval($auditData['manual_tests']['failed'] ?? 0) . '</div>';
        if (!empty($auditData['manual_tests']['raw_output'])) {
            $escaped = htmlspecialchars($auditData['manual_tests']['raw_output'], ENT_QUOTES, 'UTF-8');
            $html .= '<pre style="white-space: pre-wrap; font-size: 10px; background: #f5f5f5; padding: 10px; border-radius: 4px;">' . $escaped . '</pre>';
        }
    }
    
    if (!empty($auditData['recommendations'])) {
        $html .= '<div class="section-title">Recommendations</div>';
        foreach ($auditData['recommendations'] as $rec) {
            $html .= '<div style="margin: 5px 0;">• ' . SecurityManager::escapeOutput($rec) . '</div>';
        }
    }
    
    $html .= '
    <div class="footer">
        <p>This is an official security audit certificate generated by MediArchive System</p>
        <p>Document ID: AUDIT-' . $auditId . '-' . date('Ymd', strtotime($audit['created_at'])) . '</p>
    </div>
</body>
</html>';
    
    return $html;
}
?>
