<?php
/**
 * Simple cURL wrapper for HTTP requests and file downloads
 */
class HttpClient {
    public static function get($url, array $headers = [], $timeout = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err) {
            throw new RuntimeException('cURL GET error: ' . $err);
        }
        return ['status' => $code, 'body' => $resp];
    }

    public static function post($url, $postFields = [], array $headers = [], $timeout = 15)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err) {
            throw new RuntimeException('cURL POST error: ' . $err);
        }
        return ['status' => $code, 'body' => $resp];
    }

    public static function downloadToFile($url, $destPath)
    {
        $fp = fopen($destPath, 'wb');
        if ($fp === false) {
            throw new RuntimeException('Unable to open file for writing: ' . $destPath);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        if ($err) {
            throw new RuntimeException('cURL download error: ' . $err);
        }
        return true;
    }
}

?>
