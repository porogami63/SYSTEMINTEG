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
            
            // Generate HTML content
            $html = self::getCertificateHTML($certificate);
            
            // Create DomPDF instance
            $dompdf = new \Dompdf\Dompdf();
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
        $purpose = htmlspecialchars($certificate['purpose']);
        $diagnosis = !empty($certificate['diagnosis']) ? htmlspecialchars($certificate['diagnosis']) : 'Not specified';
        $recommendations = !empty($certificate['recommendations']) ? htmlspecialchars($certificate['recommendations']) : 'As advised';
        $issue_date = htmlspecialchars($certificate['issue_date']);
        $expiry_date = !empty($certificate['expiry_date']) ? htmlspecialchars($certificate['expiry_date']) : '';
        $issued_by = htmlspecialchars($certificate['issued_by']);
        $doctor_license = !empty($certificate['doctor_license']) ? htmlspecialchars($certificate['doctor_license']) : '';
        $clinic_name = !empty($certificate['clinic_name']) ? htmlspecialchars($certificate['clinic_name']) : 'Medical Clinic';
        $clinic_address = !empty($certificate['clinic_address']) ? htmlspecialchars($certificate['clinic_address']) : '';
        
        // E-signature path
        $signature_path = !empty($certificate['signature_path']) ? $certificate['signature_path'] : '';
        $signature_img = '';
        if ($signature_path && file_exists($signature_path)) {
            $signature_img = '<img src="' . $signature_path . '" style="height: 60px; margin-bottom: 10px;">';
        }
        
        // Format date nicely
        $formatted_date = date('F d, Y', strtotime($issue_date));
        
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
                    font-size: 24px;
                    font-weight: bold;
                    color: #1565c0;
                    margin: 0;
                    text-transform: uppercase;
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
                    font-size: 10px;
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
                <div class="clinic-name">' . $clinic_name . '</div>
                ' . ($clinic_address ? '<div class="clinic-address">' . $clinic_address . '</div>' : '') . '
                <div class="clinic-address">Digital Medical Certificate System</div>
            </div>
            
            <div class="cert-number">Certificate No: ' . $cert_id . '</div>
            
            <div class="certificate-title">MEDICAL CERTIFICATE</div>
            
            <div class="certification-text">
                <p>This is to certify that <span class="patient-name">' . strtoupper($patient_name) . '</span> 
                was examined and treated at this clinic on <strong>' . $formatted_date . '</strong>.</p>
                
                <p>Based on my professional medical examination and assessment, I hereby certify that the above-named patient:</p>
            </div>
            
            <div class="details-section">
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
            
            <div class="certification-text">
                <p>I certify that the information provided above is true and correct based on my professional medical examination and the patient\'s medical history as presented to me.</p>
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
                    ' . $signature_img . '
                    <div class="signature-line">
                        <div class="doctor-name">' . $issued_by . '</div>
                        <div class="doctor-title">Licensed Medical Practitioner</div>
                        ' . ($doctor_license ? '<div class="license-number">License No: ' . $doctor_license . '</div>' : '') . '
                    </div>
                </div>
            </div>
            
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
