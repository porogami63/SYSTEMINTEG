<?php
/**
 * XML helper for building and parsing XML
 */
class XmlHandler {
    public static function arrayToXml(array $data, $rootElement = 'root')
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $root = $xml->createElement($rootElement);
        $xml->appendChild($root);
        self::appendArray($xml, $root, $data);
        $xml->formatOutput = true;
        return $xml->saveXML();
    }

    private static function appendArray(DOMDocument $xml, DOMElement $node, array $data)
    {
        foreach ($data as $key => $value) {
            $key = is_numeric($key) ? 'item' : preg_replace('/[^a-z0-9_\-]/i', '_', $key);
            if (is_array($value)) {
                $child = $xml->createElement($key);
                $node->appendChild($child);
                self::appendArray($xml, $child, $value);
            } else {
                // Handle null values by converting to empty string
                $textValue = ($value === null) ? '' : (string)$value;
                $child = $xml->createElement($key, htmlspecialchars($textValue, ENT_XML1, 'UTF-8'));
                $node->appendChild($child);
            }
        }
    }

    public static function xmlToArray($xmlString)
    {
        libxml_use_internal_errors(true);
        $simple = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($simple === false) {
            throw new RuntimeException('Invalid XML provided');
        }
        $json = json_encode($simple);
        return json_decode($json, true);
    }
}

?>
