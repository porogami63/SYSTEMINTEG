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
}

?>
