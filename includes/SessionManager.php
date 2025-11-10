<?php
/**
 * Session Manager - Secure session handling
 */
class SessionManager {
    
    /**
     * Initialize secure session
     */
    public static function startSecureSession() {
        // Check if session is already started
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session configuration BEFORE starting session
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', 3600); // 1 hour
            ini_set('session.cookie_lifetime', 0); // Session cookie (expires when browser closes)
            
            // Start the session
            session_start();
        }
        
        // Regenerate session ID periodically (only if session is active)
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                // Preserve CSRF token during regeneration
                $csrfToken = $_SESSION['csrf_token'] ?? null;
                
                session_regenerate_id(true);
                
                // Restore CSRF token after regeneration
                if ($csrfToken !== null) {
                    $_SESSION['csrf_token'] = $csrfToken;
                }
                
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    /**
     * Validate session
     */
    public static function validateSession() {
        // Check if session exists
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
            // Session expired
            self::destroySession();
            return false;
        }
        
        // Check IP address (optional - can be disabled if users have dynamic IPs)
        if (isset($_SESSION['ip_address'])) {
            $currentIP = SecurityManager::getClientIP();
            if ($_SESSION['ip_address'] !== $currentIP) {
                // IP changed - could be session hijacking
                SecurityManager::logSecurityEvent('SESSION_IP_CHANGE', [
                    'old_ip' => $_SESSION['ip_address'],
                    'new_ip' => $currentIP,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);
                // Uncomment to enforce strict IP checking:
                // self::destroySession();
                // return false;
            }
        } else {
            $_SESSION['ip_address'] = SecurityManager::getClientIP();
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Create secure session for user
     */
    public static function createSession($userId, $username, $fullName, $role) {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            self::startSecureSession();
        }
        
        // Preserve CSRF token during session regeneration
        $csrfToken = $_SESSION['csrf_token'] ?? null;
        
        // Regenerate session ID on login
        session_regenerate_id(true);
        
        // Restore CSRF token after regeneration
        if ($csrfToken !== null) {
            $_SESSION['csrf_token'] = $csrfToken;
        }
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['role'] = $role;
        $_SESSION['ip_address'] = SecurityManager::getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
        $_SESSION['login_time'] = time();
        
        // Log successful login
        if (class_exists('AuditLogger')) {
            AuditLogger::log('LOGIN_SUCCESS', 'user', $userId, [
                'ip_address' => $_SESSION['ip_address'],
                'user_agent' => $_SESSION['user_agent']
            ]);
        }
    }
    
    /**
     * Destroy session
     */
    public static function destroySession() {
        $userId = $_SESSION['user_id'] ?? null;
        
        // Log logout
        if ($userId) {
            AuditLogger::log('LOGOUT', 'user', $userId);
        }
        
        // Clear all session data
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return self::validateSession() && isset($_SESSION['user_id']);
    }
    
    /**
     * Require authentication (redirect if not authenticated)
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            header('Location: login.php');
            exit();
        }
    }
    
    /**
     * Require specific role
     */
    public static function requireRole($role) {
        self::requireAuth();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }
    }
}

?>
