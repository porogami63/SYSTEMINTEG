<?php
/**
 * Email Notification System
 * Sends emails for various system events
 */
class EmailNotifier {
    private static $smtp_host = 'smtp.gmail.com'; // Configure in config.php
    private static $smtp_port = 587;
    private static $smtp_username = ''; // Configure in config.php
    private static $smtp_password = ''; // Configure in config.php
    private static $from_email = 'noreply@mediarchive.com';
    private static $from_name = 'MediArchive System';
    
    /**
     * Send email using PHP mail() function (basic implementation)
     * For production, integrate PHPMailer or similar
     */
    public static function send($to, $subject, $message, $html = true) {
        try {
            $headers = [];
            
            if ($html) {
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=UTF-8';
            }
            
            $headers[] = 'From: ' . self::$from_name . ' <' . self::$from_email . '>';
            $headers[] = 'Reply-To: ' . self::$from_email;
            $headers[] = 'X-Mailer: PHP/' . phpversion();
            
            $mail_sent = @mail($to, $subject, $message, implode("\r\n", $headers));
            
            if ($mail_sent) {
                // Log email sent
                AuditLogger::log('SEND_EMAIL', 'notification', null, ['to' => $to, 'subject' => $subject]);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send certificate issuance email
     */
    public static function sendCertificateIssued($user_email, $user_name, $cert_id, $cert_details) {
        $subject = 'Medical Certificate Issued - ' . $cert_id;
        $message = self::getCertificateEmailTemplate($user_name, $cert_id, $cert_details);
        return self::send($user_email, $subject, $message);
    }
    
    /**
     * Send certificate expiry reminder
     */
    public static function sendExpiryReminder($user_email, $user_name, $cert_id, $expiry_date, $days_remaining) {
        $subject = "Certificate Expiring Soon - {$cert_id}";
        $message = self::getExpiryReminderTemplate($user_name, $cert_id, $expiry_date, $days_remaining);
        return self::send($user_email, $subject, $message);
    }
    
    /**
     * Send certificate expired notification
     */
    public static function sendExpiredNotification($user_email, $user_name, $cert_id, $expiry_date) {
        $subject = "Certificate Expired - {$cert_id}";
        $message = self::getExpiredTemplate($user_name, $cert_id, $expiry_date);
        return self::send($user_email, $subject, $message);
    }
    
    /**
     * Email template for certificate issuance
     */
    private static function getCertificateEmailTemplate($name, $cert_id, $details) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2e7d32; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; margin-top: 20px; }
                .button { display: inline-block; padding: 10px 20px; background: #2e7d32; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>MediArchive</h2>
                </div>
                <div class='content'>
                    <h3>Hello {$name},</h3>
                    <p>A new medical certificate has been issued to you.</p>
                    <p><strong>Certificate ID:</strong> {$cert_id}</p>
                    <p><strong>Purpose:</strong> {$details['purpose']}</p>
                    <p><strong>Issue Date:</strong> {$details['issue_date']}</p>
                    " . (!empty($details['expiry_date']) ? "<p><strong>Expiry Date:</strong> {$details['expiry_date']}</p>" : "") . "
                    <p>You can view and download your certificate by logging into your MediArchive account.</p>
                    <a href='" . SITE_URL . "views/my_certificates.php' class='button'>View Certificate</a>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Email template for expiry reminder
     */
    private static function getExpiryReminderTemplate($name, $cert_id, $expiry_date, $days) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #ffc107; color: #333; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; margin-top: 20px; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>MediArchive - Expiry Reminder</h2>
                </div>
                <div class='content'>
                    <h3>Hello {$name},</h3>
                    <div class='warning'>
                        <strong>Certificate Expiring Soon</strong><br>
                        Your medical certificate <strong>{$cert_id}</strong> will expire in {$days} day(s) on {$expiry_date}.
                    </div>
                    <p>Please contact your clinic if you need a renewal.</p>
                    <a href='" . SITE_URL . "views/my_certificates.php' style='display: inline-block; padding: 10px 20px; background: #ffc107; color: #333; text-decoration: none; border-radius: 5px; margin-top: 15px;'>View Certificate</a>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Email template for expired certificate
     */
    private static function getExpiredTemplate($name, $cert_id, $expiry_date) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; margin-top: 20px; }
                .alert { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>MediArchive - Certificate Expired</h2>
                </div>
                <div class='content'>
                    <h3>Hello {$name},</h3>
                    <div class='alert'>
                        <strong>Certificate Expired</strong><br>
                        Your medical certificate <strong>{$cert_id}</strong> has expired on {$expiry_date}.
                    </div>
                    <p>This certificate is no longer valid. Please contact your clinic if you need a new certificate.</p>
                    <a href='" . SITE_URL . "views/my_certificates.php' style='display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px;'>View Certificates</a>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Configure SMTP settings
     */
    public static function configure($host, $port, $username, $password, $from_email = null, $from_name = null) {
        self::$smtp_host = $host;
        self::$smtp_port = $port;
        self::$smtp_username = $username;
        self::$smtp_password = $password;
        if ($from_email) self::$from_email = $from_email;
        if ($from_name) self::$from_name = $from_name;
    }
}

?>

