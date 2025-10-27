<?php
/**
 * QR Code Generator for MediArchive
 * Uses external API for QR code generation
 */

function generateQRCode($cert_id, $cert_db_id) {
    $base_url = SITE_URL;
    $url = $base_url . "api/validate.php?cert_id=" . urlencode($cert_id);
    
    // Use Google Charts API for QR code generation
    $qr_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($url);
    
    // Download and save QR code
    $qr_file = 'MED-' . $cert_db_id . '.png';
    $qr_path = QR_DIR . $qr_file;
    
    // Use cURL to download QR code
    $ch = curl_init($qr_url);
    $fp = fopen($qr_path, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    
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

