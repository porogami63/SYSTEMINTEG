<?php
// Simple bootstrap to load helper classes. Keep this file minimal and require it from config.php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/JsonHelper.php';
require_once __DIR__ . '/XmlHandler.php';
require_once __DIR__ . '/HttpClient.php';
require_once __DIR__ . '/FileProcessor.php';
require_once __DIR__ . '/SoapFacade.php';

// Optional: provide a global helper to get the PDO instance quickly
function DB()
{
    return Database::getInstance();
}

?>
