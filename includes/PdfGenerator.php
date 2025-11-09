<?php
/**
 * PDF Certificate Generator
 * Generates professional PDF certificates using DomPDF
 * 
 * DomPDF is included in includes/dompdf/dompdf/
 * No Composer required!
 */
class PdfGenerator {
    
    /**
     * Generate PDF certificate using DomPDF
     * 
     * @param array $certificate Certificate data
     * @param string $outputPath Output file path (optional)
     * @return string|bool PDF file path or false on failure
     */
    public static function generateCertificate($certificate, $outputPath = null) {
        try {
            // Load DomPDF
            require_once __DIR__ . '/dompdf/dompdf/autoload.inc.php';
            
            // Enable remote file access for images
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            
            // Generate HTML content
            $html = self::getCertificateHTML($certificate);
            
            // Create DomPDF instance
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Output to file or return
            if ($outputPath) {
                file_put_contents($outputPath, $dompdf->output());
                return $outputPath;
            } else {
                return $dompdf->output();
            }
        } catch (Exception $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get certificate HTML content for DomPDF
     */
    private static function getCertificateHTML($certificate) {
        $cert_id = htmlspecialchars($certificate['cert_id']);
        $patient_name = htmlspecialchars($certificate['patient_name']);
        $patient_code = !empty($certificate['patient_code']) ? htmlspecialchars($certificate['patient_code']) : 'N/A';
        $date_of_birth = !empty($certificate['date_of_birth']) ? htmlspecialchars($certificate['date_of_birth']) : 'N/A';
        $gender = !empty($certificate['gender']) ? htmlspecialchars($certificate['gender']) : 'N/A';
        $purpose = htmlspecialchars($certificate['purpose']);
        $diagnosis = !empty($certificate['diagnosis']) ? htmlspecialchars($certificate['diagnosis']) : 'Not specified';
        $recommendations = !empty($certificate['recommendations']) ? htmlspecialchars($certificate['recommendations']) : 'As advised';
        $issue_date = htmlspecialchars($certificate['issue_date']);
        $expiry_date = !empty($certificate['expiry_date']) ? htmlspecialchars($certificate['expiry_date']) : '';
        $issued_by = htmlspecialchars($certificate['issued_by']);
        $doctor_license = !empty($certificate['doctor_license']) ? htmlspecialchars($certificate['doctor_license']) : '';
        $clinic_name = !empty($certificate['clinic_name']) ? htmlspecialchars($certificate['clinic_name']) : 'Medical Clinic';
        $clinic_address = !empty($certificate['clinic_address']) ? htmlspecialchars($certificate['clinic_address']) : '';
        
        // E-signature path - check both signature_path and doctor_signature_path
        $signature_path = !empty($certificate['signature_path']) ? $certificate['signature_path'] : '';
        if (empty($signature_path) && !empty($certificate['doctor_signature_path'])) {
            $signature_path = $certificate['doctor_signature_path'];
        }
        
        $signature_img = '';
        if ($signature_path) {
            try {
                // Remove leading ../ if present
                $clean_path = $signature_path;
                if (str_starts_with($clean_path, '../')) {
                    $clean_path = substr($clean_path, 3);
                }
                
                // Try different path combinations
                $possible_paths = [
                    __DIR__ . '/../' . $clean_path,
                    'C:/xampp/htdocs/SYSTEMINTEG/' . $clean_path,
                    $clean_path
                ];
                
                $found_path = null;
                foreach ($possible_paths as $path) {
                    if (file_exists($path)) {
                        $found_path = $path;
                        break;
                    }
                }
                
                if ($found_path) {
                    // Convert to data URI for DomPDF
                    $image_data = base64_encode(file_get_contents($found_path));
                    $image_type = pathinfo($found_path, PATHINFO_EXTENSION);
                    $mime_type = 'image/' . ($image_type === 'jpg' ? 'jpeg' : $image_type);
                    $signature_img = '<img src="data:' . $mime_type . ';base64,' . $image_data . '" style="height: 60px; margin-bottom: 10px;">';
                }
            } catch (Exception $e) {
                error_log('Signature image processing error: ' . $e->getMessage());
                // Continue without signature
            }
        }
        
        // Seal path
        $seal_path = !empty($certificate['seal_path']) ? $certificate['seal_path'] : '';
        $seal_img = '';
        if ($seal_path) {
            $full_seal_path = $seal_path;
            if (!file_exists($full_seal_path) && !str_starts_with($seal_path, '/') && !str_starts_with($seal_path, 'C:')) {
                $full_seal_path = __DIR__ . '/../' . $seal_path;
            }
            
            if (file_exists($full_seal_path)) {
                $seal_img = '<img src="' . $full_seal_path . '" style="height: 60px; margin-bottom: 10px;">';
            }
        }
        
        // Format date nicely
        $formatted_date = date('F d, Y', strtotime($issue_date));
        
        // Generate QR code
        $qr_img = '';
        if (!empty($certificate['id'])) {
            try {
                require_once __DIR__ . '/qr_generator.php';
                $qr_file = 'MED-' . $certificate['id'] . '.png';
                $qr_path = __DIR__ . '/../qrcodes/' . $qr_file;
                
                // Generate QR if it doesn't exist
                if (!file_exists($qr_path)) {
                    try {
                        generateQRCode($cert_id, $certificate['id']);
                    } catch (Exception $e) {
                        error_log('QR generation failed: ' . $e->getMessage());
                    }
                }
                
                // Convert QR to base64 if it exists
                if (file_exists($qr_path)) {
                    $qr_data = base64_encode(file_get_contents($qr_path));
                    $qr_img = '<img src="data:image/png;base64,' . $qr_data . '" style="width: 150px; height: 150px;">';
                }
            } catch (Exception $e) {
                error_log('QR code processing error: ' . $e->getMessage());
                // Continue without QR code
            }
        }
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page {
                    margin: 30px;
                }
                body {
                    font-family: "Times New Roman", Times, serif;
                    margin: 0;
                    padding: 30px;
                    color: #000;
                    line-height: 1.6;
                }
                .letterhead {
                    text-align: center;
                    border-bottom: 3px double #1565c0;
                    padding-bottom: 15px;
                    margin-bottom: 25px;
                }
                .clinic-name {
                    font-size: 26px;
                    font-weight: bold;
                    color: #1565c0;
                    margin: 0;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .clinic-address {
                    font-size: 11px;
                    color: #555;
                    margin: 5px 0;
                }
                .certificate-title {
                    text-align: center;
                    font-size: 20px;
                    font-weight: bold;
                    text-decoration: underline;
                    margin: 30px 0 25px 0;
                    color: #1565c0;
                }
                .cert-number {
                    text-align: right;
                    font-size: 11px;
                    color: #666;
                    margin-bottom: 20px;
                }
                .certification-text {
                    text-align: justify;
                    margin: 20px 0;
                    font-size: 13px;
                }
                .patient-name {
                    font-weight: bold;
                    text-decoration: underline;
                    font-size: 14px;
                }
                .details-section {
                    margin: 25px 0;
                    padding: 15px;
                    background-color: #f8f9fa;
                    border-left: 4px solid #1565c0;
                }
                .detail-row {
                    margin: 8px 0;
                    font-size: 12px;
                }
                .detail-label {
                    font-weight: bold;
                    display: inline-block;
                    width: 140px;
                    color: #1565c0;
                }
                .validity {
                    margin: 20px 0;
                    font-size: 12px;
                    font-style: italic;
                }
                .signature-section {
                    margin-top: 60px;
                    display: table;
                    width: 100%;
                }
                .signature-box {
                    display: table-cell;
                    width: 50%;
                    text-align: center;
                    vertical-align: bottom;
                }
                .signature-line {
                    border-top: 2px solid #000;
                    margin: 50px auto 5px auto;
                    width: 200px;
                    padding-top: 5px;
                }
                .doctor-name {
                    font-weight: bold;
                    font-size: 13px;
                }
                .doctor-title {
                    font-size: 11px;
                    color: #555;
                }
                .license-number {
                    font-size: 10px;
                    color: #666;
                    margin-top: 3px;
                }
                .footer-note {
                    margin-top: 40px;
                    padding-top: 15px;
                    border-top: 1px solid #ddd;
                    font-size: 9px;
                    color: #888;
                    text-align: center;
                }
                .verification-box {
                    margin-top: 20px;
                    padding: 10px;
                    border: 1px dashed #1565c0;
                    background-color: #e3f2fd;
                    font-size: 10px;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="letterhead">
                <div class="clinic-name">GREY SLOAN MEMORIAL HOSPITAL</div>
                <div class="clinic-address">OOO</div>
                <div class="clinic-address">Digital Medical Certificate System</div>
            </div>
            
            <div class="cert-number">Certificate No: ' . $cert_id . '</div>
            
            <div class="certificate-title">MEDICAL CERTIFICATE</div>
            
            <div class="certification-text">
                <p>This is to certify that <span class="patient-name">' . strtoupper($patient_name) . '</span> 
                (Patient Code: <strong>' . $patient_code . '</strong>) was examined and treated at this clinic on <strong>' . $formatted_date . '</strong>.</p>
            </div>
            
            <div class="details-section">
                <div class="detail-row">
                    <span class="detail-label">Patient Name:</span>
                    <span>' . $patient_name . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Patient Code:</span>
                    <span>' . $patient_code . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <span>' . $date_of_birth . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Gender:</span>
                    <span>' . $gender . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Purpose:</span>
                    <span>' . $purpose . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Medical Findings:</span>
                    <span>' . $diagnosis . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Medical Advice:</span>
                    <span>' . $recommendations . '</span>
                </div>
            </div>
            
            ' . ($expiry_date ? '
            <div class="validity">
                <strong>Validity Period:</strong> This certificate is valid from ' . date('F d, Y', strtotime($issue_date)) . ' 
                until ' . date('F d, Y', strtotime($expiry_date)) . '.
            </div>
            ' : '<div class="validity"><strong>Date of Issue:</strong> ' . $formatted_date . '</div>') . '
            
            <div class="signature-section">
                <div class="signature-box">
                    <div style="text-align: left; padding-left: 50px;">
                        <div style="margin-bottom: 5px;"><strong>Date:</strong> ' . $formatted_date . '</div>
                    </div>
                </div>
                <div class="signature-box">
                    ' . ($signature_img ? $signature_img . '<div style="font-size: 11px; color: #666; margin-top: 5px;">Doctor\'s Signature</div>' : '<div style="height: 60px; border: 1px dashed #ccc; text-align: center; line-height: 60px; font-size: 10px; color: #999; margin-bottom: 10px;">Image not found or type unknown</div><div style="font-size: 11px; color: #666; margin-top: 5px;">Doctor\'s Signature</div>') . '
                    <div class="signature-line">
                        <div class="doctor-name">' . $issued_by . '</div>
                        <div class="doctor-title">Licensed Medical Practitioner</div>
                        ' . ($doctor_license ? '<div class="license-number">License No: ' . $doctor_license . '</div>' : '') . '
                    </div>
                </div>
            </div>
            
            ' . ($qr_img ? '
            <div style="text-align: center; margin-top: 30px;">
                <p style="font-size: 12px; color: #666; margin-bottom: 10px;">Scan QR code to verify certificate</p>
                ' . $qr_img . '
            </div>
            ' : '') . '
            
            <div class="verification-box">
                <strong>VERIFICATION:</strong> This certificate can be verified online at MediArchive System<br>
                Certificate ID: <strong>' . $cert_id . '</strong> | Issued: ' . $formatted_date . '
            </div>
            
            <div class="footer-note">
                This is a computer-generated medical certificate issued through MediArchive Digital Certificate System.<br>
                This document is valid without signature if verified through the system.
            </div>
        </body>
        </html>
        ';
    }
}
