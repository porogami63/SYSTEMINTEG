<?php
/**
 * JSON helper with error handling
 */
class JsonHelper {
    public static function encode($data, $pretty = true)
    {
        $options = 0;
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }
        $json = json_encode($data, $options | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return json_encode([
                'status' => 'error',
                'message' => 'JSON encoding error: ' . json_last_error_msg()
            ]);
        }
        return $json;
    }

    public static function decode($json, $assoc = true)
    {
        $data = json_decode($json, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('JSON decode error: ' . json_last_error_msg());
        }
        return $data;
    }

    /**
     * Read and decode JSON body from php://input if Content-Type is JSON.
     * Returns null if body is empty or content-type isn't JSON.
     */
    public static function getJsonInput($assoc = true)
    {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'])[0])) : '';
        if ($contentType !== 'application/json' && $contentType !== 'text/json' && $contentType !== 'application/ld+json') {
            return null;
        }
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return null;
        }
        return self::decode($raw, $assoc);
    }

    /**
     * Send a JSON response with proper headers and status code.
     */
    public static function respond($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo self::encode($data, false);
    }
}

?>
