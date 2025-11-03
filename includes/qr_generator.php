<?php
/**
 * QR Code Generator for MediArchive
 * Uses external API for QR code generation
 * Uses HttpClient OOP class for cURL operations
 */

function generateQRCode($cert_id, $cert_db_id) {
    $base_url = SITE_URL;
    $url = $base_url . "api/validate.php?cert_id=" . urlencode($cert_id);
    
    // Use QRServer API (reliable alternative to deprecated Google Charts QR)
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($url);
    
    // Download and save QR code using HttpClient OOP class
    $qr_file = 'MED-' . $cert_db_id . '.png';
    $qr_path = QR_DIR . $qr_file;
    
    // Use HttpClient class for cURL download (demonstrates OOP and cURL)
    try {
        HttpClient::downloadToFile($qr_url, $qr_path);
    } catch (Exception $e) {
        throw new RuntimeException('Failed to generate QR code: ' . $e->getMessage());
    }
    
    return 'qrcodes/' . $qr_file;
}

function getQRCodeImage($cert_id, $cert_db_id) {
    $qr_file = 'MED-' . $cert_db_id . '.png';
    $qr_path = QR_DIR . $qr_file;
    
    if (file_exists($qr_path)) {
        return SITE_URL . 'qrcodes/' . $qr_file;
    }
    
    // Generate if doesn't exist
    generateQRCode($cert_id, $cert_db_id);
    return SITE_URL . 'qrcodes/' . $qr_file;
}
?>

