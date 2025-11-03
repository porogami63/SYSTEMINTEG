<?php
/**
 * PDF Certificate Generator
 * Generates professional PDF certificates using TCPDF
 * 
 * Note: Requires TCPDF library
 * Install via Composer: composer require tecnickcom/tcpdf
 * Or download from: https://tcpdf.org/
 */
class PdfGenerator {
    
    /**
     * Generate PDF certificate
     * 
     * @param array $certificate Certificate data
     * @param string $outputPath Output file path (optional, if not provided returns PDF as string)
     * @return string|bool PDF content or file path
     */
    public static function generateCertificate($certificate, $outputPath = null) {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            // Fallback: Create simple HTML-based PDF using built-in methods
            return self::generateSimplePDF($certificate, $outputPath);
        }
        
        // Use TCPDF if available
        return self::generateWithTCPDF($certificate, $outputPath);
    }
    
    /**
     * Generate simple PDF using output buffering (fallback method)
     */
    private static function generateSimplePDF($certificate, $outputPath = null) {
        ob_start();
        
        // Generate HTML certificate
        $html = self::getCertificateHTML($certificate);
        
        // For simple implementation, we'll create a printable HTML version
        // In production, use a library like TCPDF, FPDF, or DomPDF
        
        $pdf_html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Medical Certificate - {$certificate['cert_id']}</title>
            <style>
                @media print {
                    body { margin: 0; padding: 20px; }
                    .no-print { display: none; }
                }
                body { font-family: 'Times New Roman', serif; max-width: 800px; margin: 0 auto; padding: 40px; }
                .certificate-header { text-align: center; border-bottom: 3px solid #2e7d32; padding-bottom: 20px; margin-bottom: 30px; }
                .certificate-body { line-height: 1.8; }
                .certificate-footer { margin-top: 50px; border-top: 2px solid #2e7d32; padding-top: 20px; }
                .signature-section { display: flex; justify-content: space-between; margin-top: 60px; }
                .signature-box { width: 250px; text-align: center; }
                .signature-line { border-top: 2px solid #000; margin-top: 50px; padding-top: 5px; }
                .qr-code { text-align: center; margin: 20px 0; }
                .cert-id { font-size: 18px; font-weight: bold; color: #2e7d32; }
            </style>
        </head>
        <body>
            {$html}
        </body>
        </html>";
        
        if ($outputPath) {
            file_put_contents($outputPath . '.html', $pdf_html);
            // Note: For actual PDF generation, install a library
            // This creates an HTML version that can be printed to PDF
            return $outputPath . '.html';
        }
        
        echo $pdf_html;
        return ob_get_clean();
    }
    
    /**
     * Generate PDF using TCPDF (if available)
     */
    private static function generateWithTCPDF($certificate, $outputPath = null) {
        require_once('tcpdf/tcpdf.php');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('MediArchive');
        $pdf->SetAuthor('MediArchive System');
        $pdf->SetTitle('Medical Certificate - ' . $certificate['cert_id']);
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Add a page
        $pdf->AddPage();
        
        // Generate certificate HTML
        $html = self::getCertificateHTML($certificate);
        
        // Print HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Output PDF
        if ($outputPath) {
            $pdf->Output($outputPath, 'F');
            return $outputPath;
        } else {
            return $pdf->Output('certificate_' . $certificate['cert_id'] . '.pdf', 'D');
        }
    }
    
    /**
     * Get certificate HTML content
     */
    private static function getCertificateHTML($certificate) {
        $qr_code_url = !empty($certificate['file_path']) ? SITE_URL . $certificate['file_path'] : '';
        $signature_img = !empty($certificate['doctor_signature_path']) ? '<img src="' . SITE_URL . $certificate['doctor_signature_path'] . '" style="max-height: 60px;">' : '';
        
        return "
        <div class='certificate-header'>
            <h1 style='color: #2e7d32; margin: 0;'>MEDICAL CERTIFICATE</h1>
            <p class='cert-id'>Certificate ID: {$certificate['cert_id']}</p>
        </div>
        
        <div class='certificate-body'>
            <p><strong>This is to certify that:</strong></p>
            <p style='font-size: 16px; margin-left: 40px;'><strong>{$certificate['patient_name']}</strong></p>
            
            <p><strong>Purpose:</strong> {$certificate['purpose']}</p>
            
            " . (!empty($certificate['diagnosis']) ? "<p><strong>Diagnosis:</strong> {$certificate['diagnosis']}</p>" : "") . "
            
            " . (!empty($certificate['recommendations']) ? "<p><strong>Recommendations:</strong> {$certificate['recommendations']}</p>" : "") . "
            
            <p><strong>Issue Date:</strong> {$certificate['issue_date']}</p>
            " . (!empty($certificate['expiry_date']) ? "<p><strong>Expiry Date:</strong> {$certificate['expiry_date']}</p>" : "") . "
            
            <p><strong>Issued By:</strong> {$certificate['issued_by']}</p>
            " . (!empty($certificate['doctor_license']) ? "<p><strong>License Number:</strong> {$certificate['doctor_license']}</p>" : "") . "
        </div>
        
        <div class='certificate-footer'>
            <div class='signature-section'>
                <div class='signature-box'>
                    {$signature_img}
                    <div class='signature-line'>
                        <strong>{$certificate['issued_by']}</strong><br>
                        Licensed Medical Practitioner
                    </div>
                </div>
                <div class='signature-box'>
                    <div class='signature-line'>
                        Date: {$certificate['issue_date']}
                    </div>
                </div>
            </div>
            
            " . (!empty($qr_code_url) ? "
            <div class='qr-code'>
                <p><strong>Verify Certificate:</strong></p>
                <img src='{$qr_code_url}' style='width: 150px; height: 150px;'><br>
                <small>Scan QR code to verify authenticity</small>
            </div>
            " : "") . "
        </div>
        
        <div style='text-align: center; margin-top: 30px; font-size: 12px; color: #666;'>
            <p>This is a digital certificate issued by MediArchive System</p>
            <p>Certificate ID: <strong>{$certificate['cert_id']}</strong></p>
        </div>";
    }
}

?>

