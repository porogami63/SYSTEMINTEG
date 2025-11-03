<?php
/**
 * Certificate Expiry Management
 * Handles automatic expiry status updates and notifications
 */
class ExpiryManager {
    
    /**
     * Check and update expired certificates
     * Should be called daily via cron job
     */
    public static function updateExpiredCertificates() {
        $db = Database::getInstance();
        $updated = 0;
        
        try {
            // Find certificates that have expired but still marked as active
            $expired = $db->fetchAll(
                "SELECT c.*, p.user_id as patient_user_id 
                 FROM certificates c
                 JOIN patients p ON c.patient_id = p.id
                 WHERE c.status = 'active' 
                 AND c.expiry_date IS NOT NULL 
                 AND c.expiry_date < CURDATE()",
                []
            );
            
            foreach ($expired as $cert) {
                // Update status to expired
                $db->execute(
                    "UPDATE certificates SET status = 'expired' WHERE id = ?",
                    [$cert['id']]
                );
                
                // Log the expiry
                AuditLogger::log(
                    'CERTIFICATE_EXPIRED',
                    'certificate',
                    $cert['id'],
                    ['cert_id' => $cert['cert_id'], 'expiry_date' => $cert['expiry_date']]
                );
                
                // Notify patient (in-app and email)
                if ($cert['patient_user_id']) {
                    $mysqli = getDBConnection();
                    notifyUser(
                        $mysqli,
                        intval($cert['patient_user_id']),
                        'Certificate Expired',
                        "Your medical certificate {$cert['cert_id']} has expired on {$cert['expiry_date']}.",
                        'my_certificates.php'
                    );
                    $mysqli->close();
                    
                    // Send email notification
                    try {
                        $patient = $db->fetch(
                            "SELECT u.email, u.full_name FROM users u WHERE u.id = ?",
                            [$cert['patient_user_id']]
                        );
                        if ($patient) {
                            require_once __DIR__ . '/EmailNotifier.php';
                            EmailNotifier::sendExpiredNotification(
                                $patient['email'],
                                $patient['full_name'],
                                $cert['cert_id'],
                                $cert['expiry_date']
                            );
                        }
                    } catch (Exception $e) {
                        error_log('Expiry email notification failed: ' . $e->getMessage());
                    }
                }
                
                $updated++;
            }
            
            return ['updated' => $updated, 'success' => true];
        } catch (Exception $e) {
            return ['updated' => $updated, 'success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get certificates expiring soon (within specified days)
     * @param int $days Days ahead to check
     * @param int|null $clinic_id Optional clinic ID to filter by
     */
    public static function getExpiringSoon($days = 7, $clinic_id = null) {
        $db = Database::getInstance();
        
        $where = ["c.status = 'active'", "c.expiry_date IS NOT NULL", 
                  "c.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)"];
        $params = [$days];
        
        if ($clinic_id !== null) {
            $where[] = "c.clinic_id = ?";
            $params[] = $clinic_id;
        }
        
        return $db->fetchAll(
            "SELECT c.*, u.full_name as patient_name, cl.clinic_name
             FROM certificates c
             JOIN patients p ON c.patient_id = p.id
             JOIN users u ON p.user_id = u.id
             JOIN clinics cl ON c.clinic_id = cl.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY c.expiry_date ASC",
            $params
        );
    }
    
    /**
     * Send expiry notifications for certificates expiring soon
     */
    public static function sendExpiryNotifications($daysAhead = 7) {
        $db = Database::getInstance();
        $expiring = self::getExpiringSoon($daysAhead);
        $notified = 0;
        
        $mysqli = getDBConnection();
        
        foreach ($expiring as $cert) {
            // Get patient user ID
            $patient = $db->fetch(
                "SELECT u.id as user_id FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?",
                [$cert['patient_id']]
            );
            
            if ($patient) {
                $daysUntilExpiry = (strtotime($cert['expiry_date']) - time()) / 86400;
                $daysUntilExpiry = floor($daysUntilExpiry);
                
                // In-app notification
                notifyUser(
                    $mysqli,
                    intval($patient['user_id']),
                    'Certificate Expiring Soon',
                    "Your medical certificate {$cert['cert_id']} will expire in {$daysUntilExpiry} day(s) on {$cert['expiry_date']}.",
                    'my_certificates.php'
                );
                
                // Email notification
                try {
                    $user = $db->fetch("SELECT email, full_name FROM users WHERE id = ?", [$patient['user_id']]);
                    if ($user) {
                        require_once __DIR__ . '/EmailNotifier.php';
                        EmailNotifier::sendExpiryReminder(
                            $user['email'],
                            $user['full_name'],
                            $cert['cert_id'],
                            $cert['expiry_date'],
                            $daysUntilExpiry
                        );
                    }
                } catch (Exception $e) {
                    error_log('Expiry reminder email failed: ' . $e->getMessage());
                }
                
                $notified++;
            }
        }
        
        $mysqli->close();
        return $notified;
    }
    
    /**
     * Get expiry statistics
     */
    public static function getExpiryStats($clinic_id = null) {
        $db = Database::getInstance();
        
        $where = "c.status = 'active' AND c.expiry_date IS NOT NULL";
        $params = [];
        
        if ($clinic_id) {
            $where .= " AND c.clinic_id = ?";
            $params[] = $clinic_id;
        }
        
        // Expiring this week
        $expiringThisWeek = $db->fetch(
            "SELECT COUNT(*) as count FROM certificates c 
             WHERE {$where} 
             AND c.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
            $params
        );
        
        // Expiring this month
        $expiringThisMonth = $db->fetch(
            "SELECT COUNT(*) as count FROM certificates c 
             WHERE {$where} 
             AND c.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
            $params
        );
        
        // Already expired
        $alreadyExpired = $db->fetch(
            "SELECT COUNT(*) as count FROM certificates c 
             WHERE c.status = 'expired' 
             AND c.expiry_date IS NOT NULL" . 
             ($clinic_id ? " AND c.clinic_id = ?" : ""),
            $clinic_id ? [$clinic_id] : []
        );
        
        return [
            'expiring_this_week' => intval($expiringThisWeek['count'] ?? 0),
            'expiring_this_month' => intval($expiringThisMonth['count'] ?? 0),
            'already_expired' => intval($alreadyExpired['count'] ?? 0)
        ];
    }
}

?>

