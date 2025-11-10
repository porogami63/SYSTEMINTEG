<?php
/**
 * Security Manager - Comprehensive security features
 * Handles CSRF protection, security headers, rate limiting, and more
 */
class SecurityManager {
    
    private static $csrfTokenName = 'csrf_token';
    private static $rateLimitTable = 'rate_limits';
    
    /**
     * Initialize security headers
     */
    public static function setSecurityHeaders() {
        // Don't set headers if they've already been sent
        if (headers_sent()) {
            return;
        }
        
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Strict Transport Security (HTTPS only - adjust for production)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "connect-src 'self'; " .
               "frame-ancestors 'self';";
        header("Content-Security-Policy: $csp");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate token if it doesn't exist
        if (empty($_SESSION[self::$csrfTokenName])) {
            $_SESSION[self::$csrfTokenName] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$csrfTokenName];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if token exists in session
        if (empty($_SESSION[self::$csrfTokenName])) {
            error_log('CSRF token validation failed: No token in session');
            return false;
        }
        
        // Validate token using constant-time comparison
        if (empty($token)) {
            error_log('CSRF token validation failed: No token provided');
            return false;
        }
        
        $isValid = hash_equals($_SESSION[self::$csrfTokenName], $token);
        if (!$isValid) {
            error_log('CSRF token validation failed: Token mismatch. Session token: ' . substr($_SESSION[self::$csrfTokenName], 0, 10) . '..., Provided token: ' . substr($token, 0, 10) . '...');
        }
        
        return $isValid;
    }
    
    /**
     * Get CSRF token field for forms
     */
    public static function getCSRFField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Verify CSRF token from POST request
     */
    public static function verifyCSRFToken() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (empty($token)) {
                // For API endpoints, we might allow CSRF token to be optional if it's an AJAX request
                // But for forms, it's required
                if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
                    http_response_code(403);
                    die('CSRF token validation failed. No token provided. Please refresh the page and try again.');
                }
                return; // Allow AJAX requests without CSRF token for now (can be made stricter)
            }
            
            if (!self::validateCSRFToken($token)) {
                http_response_code(403);
                die('CSRF token validation failed. Please refresh the page and try again.');
            }
        }
    }
    
    /**
     * Rate limiting - check if action is allowed
     * @param string $action Action identifier (e.g., 'login', 'api_call')
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @param string|null $identifier User identifier (IP address or user ID)
     * @return bool True if allowed, false if rate limited
     */
    public static function checkRateLimit($action, $maxAttempts = 5, $windowSeconds = 300, $identifier = null) {
        try {
            $db = Database::getInstance();
            
            // Use IP address if identifier not provided
            if ($identifier === null) {
                $identifier = self::getClientIP();
            }
            
            $key = $action . ':' . $identifier;
            
            // Check if rate limit table exists, create if not
            self::ensureRateLimitTable();
            
            // Clean old entries
            $db->execute(
                "DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)",
                [$windowSeconds]
            );
            
            // Get current attempts
            $record = $db->fetch(
                "SELECT attempts, created_at FROM rate_limits WHERE action_key = ?",
                [$key]
            );
            
            if ($record) {
                $attempts = (int)$record['attempts'];
                if ($attempts >= $maxAttempts) {
                    // Check if window has expired
                    $createdAt = strtotime($record['created_at']);
                    $now = time();
                    if (($now - $createdAt) < $windowSeconds) {
                        // Still rate limited
                        self::logSecurityEvent('RATE_LIMIT_EXCEEDED', [
                            'action' => $action,
                            'identifier' => $identifier,
                            'attempts' => $attempts
                        ]);
                        return false;
                    } else {
                        // Window expired, reset
                        $db->execute(
                            "DELETE FROM rate_limits WHERE action_key = ?",
                            [$key]
                        );
                    }
                }
            }
            
            // Increment or create record
            if ($record) {
                $db->execute(
                    "UPDATE rate_limits SET attempts = attempts + 1, last_attempt = NOW() WHERE action_key = ?",
                    [$key]
                );
            } else {
                $db->execute(
                    "INSERT INTO rate_limits (action_key, action, identifier, attempts, created_at, last_attempt) 
                     VALUES (?, ?, ?, 1, NOW(), NOW())",
                    [$key, $action, $identifier]
                );
            }
            
            return true;
        } catch (Exception $e) {
            // On error, allow the request (fail open)
            error_log('Rate limiting error: ' . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Reset rate limit for an action
     */
    public static function resetRateLimit($action, $identifier = null) {
        try {
            $db = Database::getInstance();
            if ($identifier === null) {
                $identifier = self::getClientIP();
            }
            $key = $action . ':' . $identifier;
            $db->execute("DELETE FROM rate_limits WHERE action_key = ?", [$key]);
        } catch (Exception $e) {
            error_log('Rate limit reset error: ' . $e->getMessage());
        }
    }
    
    /**
     * Ensure rate limit table exists
     */
    private static function ensureRateLimitTable() {
        static $tableExists = false;
        if ($tableExists) {
            return;
        }
        
        try {
            $db = Database::getInstance();
            $db->execute("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    action_key VARCHAR(255) UNIQUE NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    identifier VARCHAR(255) NOT NULL,
                    attempts INT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_action_key (action_key),
                    INDEX idx_created_at (created_at),
                    INDEX idx_action (action)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $tableExists = true;
        } catch (Exception $e) {
            // Table might already exist
        }
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $details = []) {
        try {
            // Check if AuditLogger is available
            if (class_exists('AuditLogger')) {
                AuditLogger::log('SECURITY_' . $event, 'security', null, $details);
            } else {
                // Fallback to error_log if AuditLogger is not available
                error_log('Security Event: ' . $event . ' - ' . json_encode($details));
            }
        } catch (Exception $e) {
            error_log('Security event logging failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Sanitize output for XSS prevention
     */
    public static function escapeOutput($data, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
        if (is_array($data)) {
            return array_map(function($item) use ($flags, $encoding) {
                return self::escapeOutput($item, $flags, $encoding);
            }, $data);
        }
        return htmlspecialchars($data, $flags, $encoding);
    }
    
    /**
     * Validate and sanitize file upload
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        $errors = [];
        
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error occurred.';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size.';
        }
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check against whitelist
        if (!empty($allowedTypes) && !in_array($extension, $allowedTypes)) {
            $errors[] = 'File type not allowed.';
        }
        
        // Additional security: check actual file content
        $allowedMimeTypes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'application/pdf' => ['pdf'],
            'text/plain' => ['txt']
        ];
        
        if (isset($allowedMimeTypes[$mimeType])) {
            if (!in_array($extension, $allowedMimeTypes[$mimeType])) {
                $errors[] = 'File extension does not match file content.';
            }
        }
        
        // Validate file name
        $fileName = basename($file['name']);
        if (preg_match('/[^a-zA-Z0-9._-]/', $fileName)) {
            $errors[] = 'Invalid file name.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }
}

?>
