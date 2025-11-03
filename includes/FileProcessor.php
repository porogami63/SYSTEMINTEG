<?php
/**
 * File processing utilities
 */
class FileProcessor {
    public static function ensureDir($dir)
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException('Failed to create directory: ' . $dir);
            }
        }
        return true;
    }

    public static function saveUpload(array $file, $targetDir, array $allowedTypes = [], $maxBytes = 0)
    {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Invalid uploaded file.');
        }

        self::ensureDir($targetDir);

        if ($maxBytes > 0 && $file['size'] > $maxBytes) {
            throw new RuntimeException('Uploaded file exceeds maximum allowed size.');
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!empty($allowedTypes) && !in_array(strtolower($ext), array_map('strtolower', $allowedTypes), true)) {
            throw new RuntimeException('File type not allowed.');
        }

        $safeName = uniqid('', true) . '.' . $ext;
        $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        return $dest;
    }

    public static function saveStringToFile($data, $path)
    {
        self::ensureDir(dirname($path));
        $written = file_put_contents($path, $data);
        if ($written === false) {
            throw new RuntimeException('Failed to write file: ' . $path);
        }
        return $path;
    }
}

?>
