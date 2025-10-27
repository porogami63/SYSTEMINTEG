<?php
/**
 * SOAP Server for Certificate Validation
 * MediArchive - Digital Medical Certificate System
 */

require_once '../config.php';

// WSDL file will be generated dynamically
if (isset($_GET['wsdl'])) {
    header('Content-Type: text/xml');
    echo '<?xml version="1.0"?>
<definitions xmlns="http://schemas.xmlsoap.org/wsdl/" 
             xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
             xmlns:xsd="http://www.w3.org/2001/XMLSchema"
             targetNamespace="urn:MediArchive">
    <types>
        <xsd:schema targetNamespace="urn:MediArchive">
            <xsd:element name="ValidateCertificateRequest">
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="cert_id" type="xsd:string"/>
                    </xsd:sequence>
                </xsd:complexType>
            </xsd:element>
            <xsd:element name="ValidateCertificateResponse">
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="valid" type="xsd:boolean"/>
                        <xsd:element name="certificate" type="xsd:string"/>
                        <xsd:element name="message" type="xsd:string"/>
                    </xsd:sequence>
                </xsd:complexType>
            </xsd:element>
        </xsd:schema>
    </types>
    <message name="ValidateCertificateRequest">
        <part name="parameters" element="tns:ValidateCertificateRequest"/>
    </message>
    <message name="ValidateCertificateResponse">
        <part name="parameters" element="tns:ValidateCertificateResponse"/>
    </message>
    <portType name="CertificateValidatorPortType">
        <operation name="ValidateCertificate">
            <input message="tns:ValidateCertificateRequest"/>
            <output message="tns:ValidateCertificateResponse"/>
        </operation>
    </portType>
    <binding name="CertificateValidatorBinding" type="tns:CertificateValidatorPortType">
        <soap:binding transport="http://schemas.xmlsoap.org/soap/http"/>
        <operation name="ValidateCertificate">
            <soap:operation soapAction="urn:MediArchive#ValidateCertificate"/>
            <input>
                <soap:body use="literal"/>
            </input>
            <output>
                <soap:body use="literal"/>
            </output>
        </operation>
    </binding>
    <service name="CertificateValidatorService">
        <port name="CertificateValidatorPort" binding="tns:CertificateValidatorBinding">
            <soap:address location="' . SITE_URL . 'api/soap_server.php"/>
        </port>
    </service>
</definitions>';
    exit;
}

// Function to validate certificate
function validateCertificate($cert_id) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT c.*, cl.clinic_name, cl.address as clinic_address,
                           u.full_name as patient_name, u.email as patient_email 
                           FROM certificates c
                           JOIN clinics cl ON c.clinic_id = cl.id
                           JOIN patients p ON c.patient_id = p.id
                           JOIN users u ON p.user_id = u.id
                           WHERE c.cert_id = ? AND c.status = 'active'");
    $stmt->bind_param("s", $cert_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $cert = $result->fetch_assoc();
        
        // Log verification
        $stmt2 = $conn->prepare("INSERT INTO verifications (cert_id, ip_address, user_agent) VALUES (?, ?, ?)");
        $stmt2->bind_param("iss", $cert['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        $stmt2->execute();
        $stmt2->close();
        
        $stmt->close();
        $conn->close();
        
        return [
            'valid' => true,
            'certificate' => json_encode($cert),
            'message' => 'Certificate is valid and active'
        ];
    } else {
        $stmt->close();
        $conn->close();
        
        return [
            'valid' => false,
            'certificate' => '{}',
            'message' => 'Certificate not found or has been revoked'
        ];
    }
}

// Initialize SOAP Server
$server = new SoapServer(SITE_URL . 'api/soap_server.php?wsdl', [
    'uri' => 'urn:MediArchive'
]);

$server->addFunction('validateCertificate');
$server->handle();
?>

