<?php
/**
 * Security Auditor - Security audit and vulnerability testing
 */
class SecurityAuditor {
    
    /**
     * Run security audit and generate report
     */
    public static function runSecurityAudit($userId = null) {
        $audit = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId ?? ($_SESSION['user_id'] ?? null),
            'checks' => [],
            'vulnerabilities' => [],
            'recommendations' => [],
            'score' => 100
        ];
        
        // Check 1: SQL Injection Protection
        $sqlCheck = self::checkSQLInjectionProtection();
        $audit['checks']['sql_injection'] = $sqlCheck;
        if (!$sqlCheck['protected']) {
            $audit['vulnerabilities'][] = 'SQL Injection: ' . $sqlCheck['issue'];
            $audit['score'] -= 20;
        }
        
        // Check 2: XSS Protection
        $xssCheck = self::checkXSSProtection();
        $audit['checks']['xss'] = $xssCheck;
        if (!$xssCheck['protected']) {
            $audit['vulnerabilities'][] = 'XSS: ' . $xssCheck['issue'];
            $audit['score'] -= 15;
        }
        
        // Check 3: CSRF Protection
        $csrfCheck = self::checkCSRFProtection();
        $audit['checks']['csrf'] = $csrfCheck;
        if (!$csrfCheck['protected']) {
            $audit['vulnerabilities'][] = 'CSRF: ' . $csrfCheck['issue'];
            $audit['score'] -= 15;
        }
        
        // Check 4: Session Security
        $sessionCheck = self::checkSessionSecurity();
        $audit['checks']['session'] = $sessionCheck;
        if (!$sessionCheck['secure']) {
            $audit['vulnerabilities'][] = 'Session: ' . $sessionCheck['issue'];
            $audit['score'] -= 10;
        }
        
        // Check 5: Password Security
        $passwordCheck = self::checkPasswordSecurity();
        $audit['checks']['password'] = $passwordCheck;
        if (!$passwordCheck['secure']) {
            $audit['vulnerabilities'][] = 'Password: ' . $passwordCheck['issue'];
            $audit['score'] -= 10;
        }
        
        // Check 6: File Upload Security
        $uploadCheck = self::checkFileUploadSecurity();
        $audit['checks']['file_upload'] = $uploadCheck;
        if (!$uploadCheck['secure']) {
            $audit['vulnerabilities'][] = 'File Upload: ' . $uploadCheck['issue'];
            $audit['score'] -= 10;
        }
        
        // Check 7: Security Headers
        $headersCheck = self::checkSecurityHeaders();
        $audit['checks']['security_headers'] = $headersCheck;
        if (!$headersCheck['complete']) {
            $audit['vulnerabilities'][] = 'Security Headers: ' . $headersCheck['issue'];
            $audit['score'] -= 10;
        }
        
        // Check 8: Rate Limiting
        $rateLimitCheck = self::checkRateLimiting();
        $audit['checks']['rate_limiting'] = $rateLimitCheck;
        if (!$rateLimitCheck['enabled']) {
            $audit['recommendations'][] = 'Rate Limiting: ' . $rateLimitCheck['recommendation'];
            $audit['score'] -= 5;
        }
        
        // Check 9: Input Validation
        $inputCheck = self::checkInputValidation();
        $audit['checks']['input_validation'] = $inputCheck;
        if (!$inputCheck['comprehensive']) {
            $audit['recommendations'][] = 'Input Validation: ' . $inputCheck['recommendation'];
            $audit['score'] -= 5;
        }
        
        // Check 10: HTTPS Usage
        $httpsCheck = self::checkHTTPS();
        $audit['checks']['https'] = $httpsCheck;
        if (!$httpsCheck['enabled']) {
            $audit['recommendations'][] = 'HTTPS: ' . $httpsCheck['recommendation'];
            $audit['score'] -= 5;
        }
        
        // Ensure score doesn't go below 0
        $audit['score'] = max(0, $audit['score']);
        
        // Determine status
        if ($audit['score'] >= 90) {
            $audit['status'] = 'excellent';
        } elseif ($audit['score'] >= 75) {
            $audit['status'] = 'good';
        } elseif ($audit['score'] >= 60) {
            $audit['status'] = 'fair';
        } else {
            $audit['status'] = 'poor';
        }
        
        return $audit;
    }
    
    /**
     * Check SQL Injection Protection
     */
    private static function checkSQLInjectionProtection() {
        // Check if Database class uses prepared statements
        $dbFile = file_get_contents(__DIR__ . '/Database.php');
        $usesPrepared = strpos($dbFile, 'prepare') !== false && strpos($dbFile, 'execute') !== false;
        
        return [
            'protected' => $usesPrepared,
            'method' => $usesPrepared ? 'Prepared statements (PDO)' : 'None detected',
            'issue' => $usesPrepared ? null : 'Database queries may be vulnerable to SQL injection'
        ];
    }
    
    /**
     * Check XSS Protection
     */
    private static function checkXSSProtection() {
        // Check if htmlspecialchars or SecurityManager::escapeOutput is used
        $configFile = file_get_contents(__DIR__ . '/../config.php');
        $usesEscape = strpos($configFile, 'htmlspecialchars') !== false || 
                     strpos($configFile, 'escapeOutput') !== false;
        
        return [
            'protected' => $usesEscape,
            'method' => $usesEscape ? 'Output escaping' : 'None detected',
            'issue' => $usesEscape ? null : 'Output may be vulnerable to XSS attacks'
        ];
    }
    
    /**
     * Check CSRF Protection
     */
    private static function checkCSRFProtection() {
        // Check if SecurityManager CSRF methods exist
        $securityFile = file_get_contents(__DIR__ . '/SecurityManager.php');
        $hasCSRF = strpos($securityFile, 'generateCSRFToken') !== false && 
                  strpos($securityFile, 'validateCSRFToken') !== false;
        
        return [
            'protected' => $hasCSRF,
            'method' => $hasCSRF ? 'CSRF tokens' : 'None detected',
            'issue' => $hasCSRF ? null : 'Forms may be vulnerable to CSRF attacks'
        ];
    }
    
    /**
     * Check Session Security
     */
    private static function checkSessionSecurity() {
        $secure = true;
        $issues = [];
        
        // Check session configuration
        if (ini_get('session.cookie_httponly') != '1') {
            $secure = false;
            $issues[] = 'Session cookie not HttpOnly';
        }
        
        if (ini_get('session.use_strict_mode') != '1') {
            $secure = false;
            $issues[] = 'Session strict mode not enabled';
        }
        
        return [
            'secure' => $secure,
            'config' => [
                'httponly' => ini_get('session.cookie_httponly') == '1',
                'secure' => ini_get('session.cookie_secure') == '1',
                'strict_mode' => ini_get('session.use_strict_mode') == '1'
            ],
            'issue' => $secure ? null : implode(', ', $issues)
        ];
    }
    
    /**
     * Check Password Security
     */
    private static function checkPasswordSecurity() {
        // Check if password_hash is used
        $loginFile = file_get_contents(__DIR__ . '/../views/login.php');
        $usesHash = strpos($loginFile, 'password_hash') !== false || 
                   strpos($loginFile, 'password_verify') !== false;
        
        return [
            'secure' => $usesHash,
            'method' => $usesHash ? 'bcrypt (password_hash)' : 'Plain text or weak hashing',
            'issue' => $usesHash ? null : 'Passwords may not be securely stored'
        ];
    }
    
    /**
     * Check File Upload Security
     */
    private static function checkFileUploadSecurity() {
        // Check if FileProcessor validates file types
        $fileProcessor = file_get_contents(__DIR__ . '/FileProcessor.php');
        $validatesType = strpos($fileProcessor, 'allowedTypes') !== false;
        $validatesSize = strpos($fileProcessor, 'maxBytes') !== false || 
                        strpos($fileProcessor, 'maxSize') !== false;
        
        return [
            'secure' => $validatesType && $validatesSize,
            'validations' => [
                'type_check' => $validatesType,
                'size_check' => $validatesSize
            ],
            'issue' => ($validatesType && $validatesSize) ? null : 'File uploads may not be properly validated'
        ];
    }
    
    /**
     * Check Security Headers
     */
    private static function checkSecurityHeaders() {
        // Check if SecurityManager sets headers
        $securityFile = file_get_contents(__DIR__ . '/SecurityManager.php');
        $hasHeaders = strpos($securityFile, 'setSecurityHeaders') !== false;
        $hasCSP = strpos($securityFile, 'Content-Security-Policy') !== false;
        $hasXFrame = strpos($securityFile, 'X-Frame-Options') !== false;
        
        return [
            'complete' => $hasHeaders && $hasCSP && $hasXFrame,
            'headers' => [
                'csp' => $hasCSP,
                'x_frame' => $hasXFrame,
                'x_xss_protection' => strpos($securityFile, 'X-XSS-Protection') !== false
            ],
            'issue' => ($hasHeaders && $hasCSP && $hasXFrame) ? null : 'Security headers may be incomplete'
        ];
    }
    
    /**
     * Check Rate Limiting
     */
    private static function checkRateLimiting() {
        $securityFile = file_get_contents(__DIR__ . '/SecurityManager.php');
        $hasRateLimit = strpos($securityFile, 'checkRateLimit') !== false;
        
        return [
            'enabled' => $hasRateLimit,
            'recommendation' => $hasRateLimit ? null : 'Consider implementing rate limiting for authentication and API endpoints'
        ];
    }
    
    /**
     * Check Input Validation
     */
    private static function checkInputValidation() {
        $validatorFile = file_get_contents(__DIR__ . '/InputValidator.php');
        $hasValidation = strpos($validatorFile, 'validate') !== false;
        
        return [
            'comprehensive' => $hasValidation,
            'recommendation' => $hasValidation ? null : 'Ensure all user inputs are validated before processing'
        ];
    }
    
    /**
     * Check HTTPS Usage
     */
    private static function checkHTTPS() {
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        
        return [
            'enabled' => $isHttps,
            'recommendation' => $isHttps ? null : 'Enable HTTPS in production to encrypt data in transit'
        ];
    }
    
    /**
     * Save audit report to database
     */
    public static function saveAuditReport($audit) {
        try {
            $db = Database::getInstance();
            
            // Ensure security_audits table exists
            self::ensureSecurityAuditsTable();
            
            $db->execute(
                "INSERT INTO security_audits (user_id, audit_data, score, status, created_at) 
                 VALUES (?, ?, ?, ?, NOW())",
                [
                    $audit['user_id'],
                    json_encode($audit),
                    $audit['score'],
                    $audit['status']
                ]
            );
            
            return $db->lastInsertId();
        } catch (Exception $e) {
            error_log('Failed to save security audit: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get audit reports
     */
    public static function getAuditReports($limit = 50, $offset = 0) {
        try {
            $db = Database::getInstance();
            self::ensureSecurityAuditsTable();
            
            return $db->fetchAll(
                "SELECT sa.*, u.username, u.full_name 
                 FROM security_audits sa
                 LEFT JOIN users u ON sa.user_id = u.id
                 ORDER BY sa.created_at DESC
                 LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
        } catch (Exception $e) {
            error_log('Failed to get audit reports: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ensure security_audits table exists
     */
    private static function ensureSecurityAuditsTable() {
        static $tableExists = false;
        if ($tableExists) {
            return;
        }
        
        try {
            $db = Database::getInstance();
            $db->execute("
                CREATE TABLE IF NOT EXISTS security_audits (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    audit_data TEXT NOT NULL,
                    score INT NOT NULL,
                    status VARCHAR(20) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at),
                    INDEX idx_status (status),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $tableExists = true;
        } catch (Exception $e) {
            // Table might already exist
        }
    }
}

?>
