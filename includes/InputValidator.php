<?php
/**
 * Input Validator - Comprehensive input validation and sanitization
 */
class InputValidator {
    
    /**
     * Validate and sanitize string input
     */
    public static function validateString($value, $minLength = 1, $maxLength = 255, $allowHtml = false) {
        if (!is_string($value)) {
            return ['valid' => false, 'error' => 'Invalid input type'];
        }
        
        $value = trim($value);
        
        if (strlen($value) < $minLength) {
            return ['valid' => false, 'error' => "Input must be at least {$minLength} characters"];
        }
        
        if (strlen($value) > $maxLength) {
            return ['valid' => false, 'error' => "Input must not exceed {$maxLength} characters"];
        }
        
        if (!$allowHtml) {
            $value = strip_tags($value);
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        
        return ['valid' => true, 'value' => $value];
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => true, 'value' => $email];
        }
        return ['valid' => false, 'error' => 'Invalid email format'];
    }
    
    /**
     * Validate integer
     */
    public static function validateInt($value, $min = null, $max = null) {
        $options = ['options' => []];
        if ($min !== null) {
            $options['options']['min_range'] = $min;
        }
        if ($max !== null) {
            $options['options']['max_range'] = $max;
        }
        
        $intValue = filter_var($value, FILTER_VALIDATE_INT, $options);
        if ($intValue !== false) {
            return ['valid' => true, 'value' => $intValue];
        }
        return ['valid' => false, 'error' => 'Invalid integer value'];
    }
    
    /**
     * Validate date
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) === $date) {
            return ['valid' => true, 'value' => $date];
        }
        return ['valid' => false, 'error' => 'Invalid date format'];
    }
    
    /**
     * Validate URL
     */
    public static function validateURL($url) {
        $url = filter_var(trim($url), FILTER_SANITIZE_URL);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => true, 'value' => $url];
        }
        return ['valid' => false, 'error' => 'Invalid URL format'];
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password, $minLength = 8) {
        if (strlen($password) < $minLength) {
            return ['valid' => false, 'error' => "Password must be at least {$minLength} characters"];
        }
        
        $strength = 0;
        $feedback = [];
        
        if (preg_match('/[a-z]/', $password)) {
            $strength++;
        } else {
            $feedback[] = 'Add lowercase letters';
        }
        
        if (preg_match('/[A-Z]/', $password)) {
            $strength++;
        } else {
            $feedback[] = 'Add uppercase letters';
        }
        
        if (preg_match('/[0-9]/', $password)) {
            $strength++;
        } else {
            $feedback[] = 'Add numbers';
        }
        
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $strength++;
        } else {
            $feedback[] = 'Add special characters';
        }
        
        if ($strength < 3) {
            return [
                'valid' => false,
                'error' => 'Password is too weak',
                'feedback' => $feedback
            ];
        }
        
        return ['valid' => true, 'value' => $password, 'strength' => $strength];
    }
    
    /**
     * Validate phone number
     */
    public static function validatePhone($phone) {
        // Remove common formatting
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Basic validation - adjust regex for your needs
        if (preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
            return ['valid' => true, 'value' => $phone];
        }
        return ['valid' => false, 'error' => 'Invalid phone number format'];
    }
    
    /**
     * Validate against SQL injection patterns
     */
    public static function validateSQLInjection($value) {
        $dangerousPatterns = [
            '/(\s*)(\d*)(\s*)(union|select|insert|update|delete|drop|create|alter|exec|execute)(\s*)/i',
            '/(\s*)(\/\*|\*\/|--|#)(\s*)/',
            '/(\s*)(or|and)(\s*)(\d*)(\s*)(=)(\s*)(\d*)/i',
            '/(\s*)(\'|"|;|\)|\(|\{|\})/'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                SecurityManager::logSecurityEvent('SQL_INJECTION_ATTEMPT', [
                    'input' => substr($value, 0, 100)
                ]);
                return ['valid' => false, 'error' => 'Invalid characters detected'];
            }
        }
        
        return ['valid' => true, 'value' => $value];
    }
    
    /**
     * Validate against XSS patterns
     */
    public static function validateXSS($value) {
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/on\w+\s*=\s*["\'][^"\']*["\']/i',
            '/javascript:/i',
            '/<img[^>]*src[^>]*=[\'"]?javascript:/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                SecurityManager::logSecurityEvent('XSS_ATTEMPT', [
                    'input' => substr($value, 0, 100)
                ]);
                return ['valid' => false, 'error' => 'Potentially dangerous content detected'];
            }
        }
        
        return ['valid' => true, 'value' => $value];
    }
    
    /**
     * Comprehensive input validation
     */
    public static function validate($value, $type = 'string', $options = []) {
        // First check for SQL injection and XSS
        $securityCheck = self::validateSQLInjection($value);
        if (!$securityCheck['valid']) {
            return $securityCheck;
        }
        
        $xssCheck = self::validateXSS($value);
        if (!$xssCheck['valid']) {
            return $xssCheck;
        }
        
        // Then validate based on type
        switch ($type) {
            case 'email':
                return self::validateEmail($value);
            case 'int':
            case 'integer':
                return self::validateInt($value, $options['min'] ?? null, $options['max'] ?? null);
            case 'date':
                return self::validateDate($value, $options['format'] ?? 'Y-m-d');
            case 'url':
                return self::validateURL($value);
            case 'password':
                return self::validatePassword($value, $options['min_length'] ?? 8);
            case 'phone':
                return self::validatePhone($value);
            case 'string':
            default:
                return self::validateString(
                    $value,
                    $options['min_length'] ?? 1,
                    $options['max_length'] ?? 255,
                    $options['allow_html'] ?? false
                );
        }
    }
    
    /**
     * Sanitize input for database storage
     */
    public static function sanitizeForDB($value) {
        if (is_array($value)) {
            return array_map([self::class, 'sanitizeForDB'], $value);
        }
        
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        // Basic sanitization (actual protection comes from prepared statements)
        return $value;
    }
}

?>
