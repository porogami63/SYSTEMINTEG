<?php
/**
 * SoapFacade - exposes certificate validation logic in an OOP way
 */
class SoapFacade {
    /**
     * Validate certificate and return array similar to legacy function
     */
    public static function validateCertificate($cert_id)
    {
        $db = Database::getInstance();

        $sql = "SELECT c.*, cl.clinic_name, cl.address as clinic_address,
                       u.full_name as patient_name, u.email as patient_email 
                       FROM certificates c
                       JOIN clinics cl ON c.clinic_id = cl.id
                       JOIN patients p ON c.patient_id = p.id
                       JOIN users u ON p.user_id = u.id
                       WHERE c.cert_id = ? AND c.status = 'active'";

        $cert = $db->fetch($sql, [$cert_id]);

        if ($cert) {
            // Log verification
            $insertSql = "INSERT INTO verifications (cert_id, ip_address, user_agent) VALUES (?, ?, ?)";
            try {
                $db->execute($insertSql, [$cert['id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? '']);
            } catch (Exception $e) {
                // logging failure shouldn't stop response
            }

            return [
                'valid' => true,
                'certificate' => json_encode($cert),
                'message' => 'Certificate is valid and active'
            ];
        }

        return [
            'valid' => false,
            'certificate' => '{}',
            'message' => 'Certificate not found or has been revoked'
        ];
    }
}

?>
