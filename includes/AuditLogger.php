<?php
/**
 * Audit Logger - Logs all system actions for security and compliance
 */
class AuditLogger {
    
    /**
     * Log an action to the audit log
     * 
     * @param string $action Action name (e.g., 'CREATE_CERTIFICATE', 'VIEW_CERTIFICATE')
     * @param string $entityType Entity type (e.g., 'certificate', 'patient')
     * @param int|null $entityId ID of the entity
     * @param array|string|null $details Additional details
     * @param int|null $userId User ID (if null, uses session)
     */
    public static function log($action, $entityType, $entityId = null, $details = null, $userId = null) {
        try {
            $db = Database::getInstance();
            
            if ($userId === null) {
                $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            }
            
            $detailsJson = null;
            if ($details !== null) {
                if (is_array($details)) {
                    $detailsJson = json_encode($details);
                } else {
                    $detailsJson = $details;
                }
            }
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $db->execute(
                "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$userId, $action, $entityType, $entityId, $detailsJson, $ipAddress, $userAgent]
            );
        } catch (Exception $e) {
            // Don't break the application if logging fails
            error_log('Audit logging failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get audit logs with optional filters
     * 
     * @param array $filters Filter options (user_id, action, entity_type, date_from, date_to)
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return array Audit log entries
     */
    public static function getLogs($filters = [], $limit = 50, $offset = 0) {
        $db = Database::getInstance();
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = 'al.user_id = ?';
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = 'al.action = ?';
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = 'al.entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'al.created_at >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'al.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        $params[] = $limit;
        $params[] = $offset;
        
        $sql = "SELECT al.*, u.full_name as user_name, u.username 
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Get total count of audit logs with filters
     */
    public static function getLogCount($filters = []) {
        $db = Database::getInstance();
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = 'action = ?';
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        $sql = "SELECT COUNT(*) as total FROM audit_logs WHERE " . implode(' AND ', $where);
        $result = $db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }
}

?>

