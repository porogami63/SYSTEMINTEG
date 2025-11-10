<?php
require_once '../config.php';

// Restrict access to web_admin only for security and confidentiality
if (!isLoggedIn()) {
    redirect('login.php');
}

// Only web_admin can access security audit reports
if (!isWebAdmin()) {
    http_response_code(403);
    die('Access denied. Security audit reports are restricted to web administrators only.');
}

$canRunAudit = true; // Only web_admin can access this page, so they can run audits

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Run security audit
if ($canRunAudit && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_audit') {
    SecurityManager::verifyCSRFToken();
    
    try {
        $audit = SecurityAuditor::runSecurityAudit($user_id);
        $auditId = SecurityAuditor::saveAuditReport($audit);
        
        if ($auditId) {
            $success = "Security audit completed successfully. Score: {$audit['score']}/100";
            
            // Log security audit activity
            AuditLogger::log(
                'SECURITY_AUDIT_RUN',
                'security_audit',
                $auditId,
                [
                    'score' => $audit['score'],
                    'status' => $audit['status'],
                    'vulnerabilities_count' => count($audit['vulnerabilities'] ?? []),
                    'recommendations_count' => count($audit['recommendations'] ?? [])
                ],
                $user_id
            );
        } else {
            $error = "Failed to save audit report";
        }
    } catch (Exception $e) {
        $error = "Error running audit: " . $e->getMessage();
        error_log('Security audit error: ' . $e->getMessage());
        
        // Log failed audit attempt
        AuditLogger::log(
            'SECURITY_AUDIT_FAILED',
            'security_audit',
            null,
            ['error' => $e->getMessage()],
            $user_id
        );
    }
}

try {
    $db = Database::getInstance();
    
    // Get audit reports
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    $audits = SecurityAuditor::getAuditReports($per_page, $offset);
    
    // Get total count
    $totalCount = $db->fetch("SELECT COUNT(*) as total FROM security_audits");
    $totalAudits = $totalCount['total'] ?? 0;
    $totalPages = ceil($totalAudits / $per_page);
    
    // Get latest audit for display
    $latestAudit = null;
    if (!empty($audits)) {
        $latestAuditData = json_decode($audits[0]['audit_data'], true);
        if ($latestAuditData) {
            $latestAudit = $latestAuditData;
        }
    }
    
} catch (Exception $e) {
    $audits = [];
    $totalAudits = 0;
    $totalPages = 0;
    $latestAudit = null;
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Security Audit Report - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<?php include 'includes/role_styles.php'; ?>
<style>
/* Security Audit Report - Enhanced Readability for Web Admin Black Theme */
.status-excellent { color: #00ff88; }
.status-good { color: #00a8ff; }
.status-fair { color: #ffaa00; }
.status-poor { color: #ff4444; }

.security-score {
    font-size: 3rem;
    font-weight: bold;
    text-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
}

.vulnerability-item {
    border-left: 4px solid #ff4444;
    padding-left: 1rem;
    margin-bottom: 0.5rem;
    background: rgba(255, 68, 68, 0.1);
    padding: 0.75rem;
    border-radius: 4px;
}

.recommendation-item {
    border-left: 4px solid #ffaa00;
    padding-left: 1rem;
    margin-bottom: 0.5rem;
    background: rgba(255, 170, 0, 0.1);
    padding: 0.75rem;
    border-radius: 4px;
}

.check-item {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-radius: 0.25rem;
}

.check-passed {
    background-color: rgba(0, 255, 136, 0.15);
    border-left: 4px solid #00ff88;
    color: #e0e0e0;
}

.check-failed {
    background-color: rgba(255, 68, 68, 0.15);
    border-left: 4px solid #ff4444;
    color: #e0e0e0;
}

/* Enhanced readability for web admin black theme */
<?php if (isWebAdmin()): ?>
.main-content h2 {
    color: #fff;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}

.main-content h5, .main-content h6 {
    color: #fff;
    font-weight: 600;
}

.main-content p.text-muted {
    color: #aaa !important;
}

.card-header.bg-primary {
    background: linear-gradient(135deg, #00ff88 0%, #00cc6f 100%) !important;
    color: #000 !important;
    font-weight: 600;
}

.card-header.bg-primary h5 {
    color: #000 !important;
}

.security-score small {
    font-size: 1.5rem;
    color: #ccc;
}

.badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    font-weight: 600;
}

.table tbody td {
    color: #000;
    background-color: #fff;
    border-color: #dee2e6;
}

.table tbody td strong {
    color: #000;
    font-weight: 600;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.alert {
    border-radius: 8px;
    padding: 1rem 1.5rem;
    font-weight: 500;
}

.alert-success {
    background: rgba(0, 255, 136, 0.15) !important;
    border-color: #00ff88 !important;
    color: #00ff88 !important;
}

.alert-danger {
    background: rgba(255, 68, 68, 0.15) !important;
    border-color: #ff4444 !important;
    color: #ff9999 !important;
}

.alert-warning {
    background: rgba(255, 170, 0, 0.15) !important;
    border-color: #ffaa00 !important;
    color: #ffcc66 !important;
}

.alert-info {
    background: rgba(0, 168, 255, 0.15) !important;
    border-color: #00a8ff !important;
    color: #66ccff !important;
}

.alert h6 {
    color: #fff;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.alert ul {
    margin-bottom: 0;
    padding-left: 1.5rem;
}

.alert ul li {
    margin-bottom: 0.25rem;
    color: inherit;
}

.card-body {
    color: #e0e0e0;
}

.card-body small {
    color: #aaa;
}

.vulnerability-item small, .recommendation-item small {
    color: #e0e0e0 !important;
    font-weight: 500;
}

.check-item strong {
    color: #fff;
    font-weight: 600;
}

.check-item small {
    color: #ccc;
}

.check-item small.text-danger {
    color: #ff9999 !important;
}

.btn-primary {
    box-shadow: 0 4px 8px rgba(0, 255, 136, 0.3);
    transition: all 0.3s ease;
}

.btn-primary:hover {
    box-shadow: 0 6px 12px rgba(0, 255, 136, 0.5);
    transform: translateY(-2px);
}

.pagination .page-link {
    color: #00ff88 !important;
    background: #1a1a1a !important;
    border-color: #333 !important;
}

.pagination .page-link:hover {
    background: #252525 !important;
    border-color: #00ff88 !important;
    color: #00ff88 !important;
}

.pagination .page-item.active .page-link {
    background: #00ff88 !important;
    border-color: #00ff88 !important;
    color: #000 !important;
    font-weight: 600;
}

.modal-content {
    background: #1a1a1a;
    border: 1px solid #333;
    color: #e0e0e0;
}

.modal-header {
    background: #252525;
    border-bottom: 1px solid #333;
    color: #fff;
}

.modal-header .btn-close {
    filter: invert(1);
}

.modal-body {
    background: #1a1a1a;
    color: #e0e0e0;
}

.stats-card {
    background: #1a1a1a !important;
    border: 1px solid #333 !important;
}

.stats-card h6 {
    color: #aaa;
    font-weight: 600;
}

.stats-card h2 {
    color: #fff;
    font-weight: 700;
}
<?php endif; ?>
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0"><i class="bi bi-shield-check"></i> Security Audit Report</h2>
                        <p class="text-muted">Comprehensive security assessment and vulnerability testing</p>
                    </div>
                    <?php if ($canRunAudit): ?>
                    <form method="POST" class="d-inline">
                        <?php echo SecurityManager::getCSRFField(); ?>
                        <input type="hidden" name="action" value="run_audit">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-clockwise"></i> Run New Audit
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                
                <!-- Security Audit Instructions -->
                <div class="card shadow-sm mb-4" style="background: #1a1a1a; border: 1px solid #333;">
                    <div class="card-header" style="background: linear-gradient(135deg, #00a8ff 0%, #0088cc 100%); color: #fff; font-weight: 600;">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> What is Security Audit?</h5>
                    </div>
                    <div class="card-body" style="color: #e0e0e0;">
                        <p><strong>Security Audit</strong> is a comprehensive automated assessment that evaluates the security posture of the MediArchive system. It performs multiple checks to identify vulnerabilities and provide recommendations for improvement.</p>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6 style="color: #00ff88; font-weight: 600;"><i class="bi bi-check-circle"></i> What It Does:</h6>
                                <ul style="color: #ccc;">
                                    <li>Scans for SQL Injection vulnerabilities</li>
                                    <li>Checks XSS (Cross-Site Scripting) protection</li>
                                    <li>Validates CSRF token implementation</li>
                                    <li>Assesses session security configuration</li>
                                    <li>Verifies password hashing methods</li>
                                    <li>Reviews file upload security</li>
                                    <li>Examines HTTP security headers</li>
                                    <li>Tests rate limiting mechanisms</li>
                                    <li>Evaluates input validation coverage</li>
                                    <li>Confirms HTTPS encryption status</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 style="color: #ffaa00; font-weight: 600;"><i class="bi bi-graph-up"></i> How It Works:</h6>
                                <ul style="color: #ccc;">
                                    <li><strong>Automated Scanning:</strong> Analyzes code and configuration files</li>
                                    <li><strong>Scoring System:</strong> Assigns a score out of 100 based on findings</li>
                                    <li><strong>Status Rating:</strong> Excellent (90+), Good (75-89), Fair (60-74), Poor (<60)</li>
                                    <li><strong>Detailed Reports:</strong> Lists vulnerabilities and recommendations</li>
                                    <li><strong>Export Options:</strong> Generate certificates in PDF, JSON, or XML format</li>
                                </ul>
                                
                                <div class="alert alert-info mt-3" style="background: rgba(0, 168, 255, 0.15) !important; border-color: #00a8ff !important; color: #66ccff !important;">
                                    <strong><i class="bi bi-lightbulb"></i> Tip:</strong> Run audits regularly (weekly or after major changes) to maintain security standards.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo SecurityManager::escapeOutput($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo SecurityManager::escapeOutput($success); ?></div>
                <?php endif; ?>
                
                <?php if ($latestAudit): ?>
                <!-- Latest Audit Summary -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Latest Security Audit</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="security-score status-<?php echo htmlspecialchars($latestAudit['status']); ?>">
                                    <?php echo $latestAudit['score']; ?><small>/100</small>
                                </div>
                                <p class="text-muted mb-2">Security Score</p>
                                <span class="badge bg-<?php 
                                    echo $latestAudit['status'] === 'excellent' ? 'success' : 
                                        ($latestAudit['status'] === 'good' ? 'info' : 
                                        ($latestAudit['status'] === 'fair' ? 'warning' : 'danger')); 
                                ?>">
                                    <?php echo ucfirst($latestAudit['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-4">
                                <h6 style="color: #e0e0e0; font-weight: 600;">Vulnerabilities Found</h6>
                                <p class="h4" style="color: #ff4444; font-weight: 700;"><?php echo count($latestAudit['vulnerabilities'] ?? []); ?></p>
                                <?php if (!empty($latestAudit['vulnerabilities'])): ?>
                                <ul class="list-unstyled mt-3">
                                    <?php foreach (array_slice($latestAudit['vulnerabilities'], 0, 3) as $vuln): ?>
                                    <li class="vulnerability-item">
                                        <small><?php echo SecurityManager::escapeOutput($vuln); ?></small>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <h6 style="color: #e0e0e0; font-weight: 600;">Recommendations</h6>
                                <p class="h4" style="color: #ffaa00; font-weight: 700;"><?php echo count($latestAudit['recommendations'] ?? []); ?></p>
                                <?php if (!empty($latestAudit['recommendations'])): ?>
                                <ul class="list-unstyled mt-3">
                                    <?php foreach (array_slice($latestAudit['recommendations'], 0, 3) as $rec): ?>
                                    <li class="recommendation-item">
                                        <small><?php echo SecurityManager::escapeOutput($rec); ?></small>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <hr style="border-color: #333; margin: 2rem 0;">
                        
                        <!-- Security Checks -->
                        <h6 style="color: #fff; font-weight: 600; margin-bottom: 1rem;">Security Checks</h6>
                        <div class="row">
                            <?php foreach ($latestAudit['checks'] ?? [] as $checkName => $check): ?>
                            <div class="col-md-6 mb-2">
                                <div class="check-item <?php 
                                    $isPassed = isset($check['protected']) ? $check['protected'] : 
                                               (isset($check['secure']) ? $check['secure'] : 
                                               (isset($check['enabled']) ? $check['enabled'] : 
                                               (isset($check['complete']) ? $check['complete'] : 
                                               (isset($check['comprehensive']) ? $check['comprehensive'] : false))));
                                    echo $isPassed ? 'check-passed' : 'check-failed';
                                ?>">
                                    <strong><?php echo $isPassed ? '<i class="bi bi-check-circle-fill" style="color: #00ff88;"></i>' : '<i class="bi bi-x-circle-fill" style="color: #ff4444;"></i>'; ?> <?php echo ucfirst(str_replace('_', ' ', $checkName)); ?></strong>
                                    <?php if (isset($check['method'])): ?>
                                    <br><small><?php echo SecurityManager::escapeOutput($check['method']); ?></small>
                                    <?php endif; ?>
                                    <?php if (isset($check['description'])): ?>
                                    <br><small style="color: #aaa; font-style: italic;"><?php echo SecurityManager::escapeOutput($check['description']); ?></small>
                                    <?php endif; ?>
                                    <?php if (isset($check['issue']) && $check['issue']): ?>
                                    <br><small class="text-danger"><?php echo SecurityManager::escapeOutput($check['issue']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Full Details -->
                        <?php if (!empty($latestAudit['vulnerabilities']) || !empty($latestAudit['recommendations'])): ?>
                        <div class="mt-4">
                            <h6 style="color: #fff; font-weight: 600; margin-bottom: 1rem;">Full Details</h6>
                            
                            <?php if (!empty($latestAudit['vulnerabilities'])): ?>
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-exclamation-triangle"></i> Vulnerabilities</h6>
                                <ul>
                                    <?php foreach ($latestAudit['vulnerabilities'] as $vuln): ?>
                                    <li><?php echo SecurityManager::escapeOutput($vuln); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($latestAudit['recommendations'])): ?>
                            <div class="alert alert-warning">
                                <h6><i class="bi bi-lightbulb"></i> Recommendations</h6>
                                <ul>
                                    <?php foreach ($latestAudit['recommendations'] as $rec): ?>
                                    <li><?php echo SecurityManager::escapeOutput($rec); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> <strong>No security audits have been run yet.</strong><br>
                    Click "Run New Audit" to perform a comprehensive security assessment of the system.
                </div>
                <?php endif; ?>
                
                <!-- Audit History -->
                <div class="card shadow-sm">
                    <div class="card-header" style="background: #252525; border-bottom: 1px solid #333; color: #fff;">
                        <h5 class="mb-0" style="color: #fff; font-weight: 600;"><i class="bi bi-clock-history"></i> Audit History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($audits)): ?>
                        <p class="text-center py-4" style="color: #aaa;">No audit reports available</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="color: #fff; font-weight: 600;">Date</th>
                                        <th style="color: #fff; font-weight: 600;">User</th>
                                        <th style="color: #fff; font-weight: 600;">Score</th>
                                        <th style="color: #fff; font-weight: 600;">Status</th>
                                        <th style="color: #fff; font-weight: 600;">Vulnerabilities</th>
                                        <th style="color: #fff; font-weight: 600;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($audits as $audit): ?>
                                    <?php 
                                    $auditData = json_decode($audit['audit_data'], true);
                                    $vulnCount = count($auditData['vulnerabilities'] ?? []);
                                    ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($audit['created_at'])); ?></td>
                                        <td><?php echo SecurityManager::escapeOutput($audit['full_name'] ?? $audit['username'] ?? 'System'); ?></td>
                                        <td>
                                            <strong class="status-<?php echo htmlspecialchars($audit['status']); ?>">
                                                <?php echo $audit['score']; ?>/100
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $audit['status'] === 'excellent' ? 'success' : 
                                                    ($audit['status'] === 'good' ? 'info' : 
                                                    ($audit['status'] === 'fair' ? 'warning' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($audit['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($vulnCount > 0): ?>
                                            <span class="badge bg-danger"><?php echo $vulnCount; ?></span>
                                            <?php else: ?>
                                            <span class="badge bg-success">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../api/view_audit.php?id=<?php echo $audit['id']; ?>" target="_blank" class="btn btn-sm btn-info" title="View Audit Certificate">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <div class="btn-group" role="group">
                                                <a href="../api/view_audit.php?id=<?php echo $audit['id']; ?>&format=pdf" class="btn btn-sm btn-danger" title="Export as PDF">
                                                    <i class="bi bi-file-pdf"></i>
                                                </a>
                                                <a href="../api/view_audit.php?id=<?php echo $audit['id']; ?>&format=json" class="btn btn-sm btn-primary" title="Export as JSON">
                                                    <i class="bi bi-filetype-json"></i>
                                                </a>
                                                <a href="../api/view_audit.php?id=<?php echo $audit['id']; ?>&format=xml" class="btn btn-sm btn-warning" title="Export as XML">
                                                    <i class="bi bi-filetype-xml"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page == $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Audit Details Modal -->
<div class="modal fade" id="auditDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="auditDetailsContent">
                Loading...
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

