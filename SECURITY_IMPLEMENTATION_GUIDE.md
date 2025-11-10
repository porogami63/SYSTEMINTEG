# Security Implementation Guide

## Quick Start

### 1. Run Database Migration

Execute the security enhancements migration to create necessary tables:

```sql
SOURCE migrations/010_security_enhancements.sql;
```

Or import via phpMyAdmin or MySQL command line.

### 2. Verify Security Features

1. **Check Security Headers**: Visit any page and check response headers in browser dev tools
2. **Test CSRF Protection**: Try submitting a form without CSRF token
3. **Test Rate Limiting**: Attempt multiple rapid login attempts
4. **Run Security Audit**: Navigate to Security Audit Report and run an audit

### 3. Configure HTTPS (Production)

In production, ensure:
- HTTPS is enabled on the server
- SSL certificate is valid
- Security headers are properly configured
- Session cookies use Secure flag

## Security Features Usage

### CSRF Protection in Forms

Add CSRF token to all forms:

```php
<form method="POST">
    <?php echo SecurityManager::getCSRFField(); ?>
    <!-- form fields -->
</form>
```

Verify CSRF token on form submission:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityManager::verifyCSRFToken();
    // process form
}
```

### Input Validation

Use InputValidator for all user inputs:

```php
// Validate email
$emailResult = InputValidator::validate($_POST['email'], 'email');
if ($emailResult['valid']) {
    $email = $emailResult['value'];
} else {
    $error = $emailResult['error'];
}

// Validate string with constraints
$usernameResult = InputValidator::validate($_POST['username'], 'string', [
    'min_length' => 3,
    'max_length' => 50
]);
```

### Rate Limiting

Implement rate limiting for sensitive operations:

```php
// Check rate limit
if (!SecurityManager::checkRateLimit('login', 5, 300, $clientIP)) {
    $error = "Too many attempts. Please try again later.";
} else {
    // process login
}

// Reset rate limit on success
SecurityManager::resetRateLimit('login', $clientIP);
```

### Secure Session Management

Sessions are automatically secured via SessionManager. Use:

```php
// Create secure session
SessionManager::createSession($userId, $username, $fullName, $role);

// Check authentication
if (SessionManager::isAuthenticated()) {
    // user is authenticated
}

// Require authentication
SessionManager::requireAuth();

// Require specific role
SessionManager::requireRole('web_admin');
```

### File Upload Security

Validate file uploads:

```php
$uploadValidation = SecurityManager::validateFileUpload(
    $_FILES['file'],
    ['jpg', 'jpeg', 'png'], // allowed extensions
    2 * 1024 * 1024 // max size (2MB)
);

if ($uploadValidation['valid']) {
    // process file
} else {
    $errors = $uploadValidation['errors'];
}
```

### Output Escaping

Always escape output:

```php
// Escape output
echo SecurityManager::escapeOutput($userInput);

// Or use in templates
<?php echo SecurityManager::escapeOutput($data); ?>
```

### Security Event Logging

Log security events:

```php
SecurityManager::logSecurityEvent('LOGIN_FAILED', [
    'username' => $username,
    'ip' => $clientIP,
    'attempts' => $failedAttempts
]);
```

### Security Audit

Run security audit:

```php
$audit = SecurityAuditor::runSecurityAudit($userId);
$auditId = SecurityAuditor::saveAuditReport($audit);
```

## Testing Security Features

### Manual Testing

1. **SQL Injection**: Try `' OR '1'='1` in login form
2. **XSS**: Try `<script>alert('XSS')</script>` in input fields
3. **CSRF**: Submit form without CSRF token
4. **Rate Limiting**: Make multiple rapid requests
5. **File Upload**: Try uploading malicious files

### Automated Testing with OWASP ZAP

1. Install OWASP ZAP
2. Configure browser proxy
3. Navigate through application
4. Run automated scan
5. Review security alerts

### Automated Testing with Burp Suite

1. Install Burp Suite
2. Configure browser proxy
3. Set scan scope
4. Run automated scanner
5. Test manually using Repeater and Intruder

## Security Checklist

### Development

- [ ] All forms have CSRF tokens
- [ ] All inputs are validated
- [ ] All outputs are escaped
- [ ] All database queries use prepared statements
- [ ] File uploads are validated
- [ ] Rate limiting is implemented
- [ ] Security events are logged
- [ ] Sessions are secure
- [ ] Passwords are hashed
- [ ] Security headers are set

### Production

- [ ] HTTPS is enabled
- [ ] SSL certificate is valid
- [ ] Security headers are configured
- [ ] Error reporting is disabled
- [ ] Debug mode is disabled
- [ ] Database credentials are secure
- [ ] File permissions are correct
- [ ] Regular security audits are performed
- [ ] Audit logs are monitored
- [ ] Backups are performed regularly

## Common Security Issues and Fixes

### Issue: SQL Injection

**Fix**: Use prepared statements
```php
// Bad
$db->query("SELECT * FROM users WHERE id = " . $_GET['id']);

// Good
$db->fetch("SELECT * FROM users WHERE id = ?", [$_GET['id']]);
```

### Issue: XSS

**Fix**: Escape output
```php
// Bad
echo $_GET['name'];

// Good
echo SecurityManager::escapeOutput($_GET['name']);
```

### Issue: CSRF

**Fix**: Use CSRF tokens
```php
// Bad
<form method="POST">
    <!-- no CSRF token -->
</form>

// Good
<form method="POST">
    <?php echo SecurityManager::getCSRFField(); ?>
</form>
```

### Issue: Session Hijacking

**Fix**: Secure session configuration
```php
// Already implemented in SessionManager
SessionManager::startSecureSession();
```

## Security Monitoring

### Monitor Audit Logs

Regularly review audit logs for:
- Failed login attempts
- Security events
- Unusual activity
- Rate limit violations

### Monitor Security Audit Reports

Run security audits regularly and:
- Track security scores
- Address vulnerabilities
- Monitor improvements
- Document changes

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [OWASP ZAP](https://www.zaproxy.org/)
- [Burp Suite](https://portswigger.net/burp)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)

---

**Last Updated:** <?php echo date('Y-m-d'); ?>

