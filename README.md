# MediArchive - Digital Medical Certificate & Verification System

## Overview

MediArchive is a comprehensive digital medical certificate system that allows clinics to issue and manage medical certificates, while patients can access, verify, and download their certificates online. The system includes QR code verification, SOAP/JSON APIs, and XML export capabilities.

## Features

✅ **User Roles**: Clinic Admins and Patients  
✅ **Certificate Management**: Create, view, and download medical certificates  
✅ **QR Code Verification**: Instant validation via mobile scanning  
✅ **SOAP API**: Certificate validation web service  
✅ **JSON REST API**: Mobile-friendly certificate data endpoint  
✅ **XML Export**: Government/HR-compatible certificate export  
✅ **Bootstrap UI**: Modern, responsive dashboard  
✅ **Secure Authentication**: Password hashing and session management  

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server (XAMPP)
- Enable SOAP extension in php.ini

## Installation

### 1. Database Setup

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database (or import `database.sql`)
3. Import the `database.sql` file

Or run via command line:
```bash
mysql -u root -p < database.sql
```

### 2. Configuration

Update the database credentials in `config.php` if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mediarchive');
```

### 3. Directory Setup

The system will automatically create these directories:
- `uploads/` - For uploaded certificate files
- `qrcodes/` - For generated QR code images

### 4. Access the System

Navigate to: `http://localhost/SYSTEMINTEG/`

## Demo Accounts

### Clinic Admin
- Username: `admin`
- Password: `password`

### Patient
- Username: `patient1`
- Password: `password`

## File Structure

```
SYSTEMINTEG/
├── config.php              # Database configuration
├── database.sql            # Database schema
├── index.php               # Landing page
├── views/                  # View files
│   ├── login.php          # Login page
│   ├── register.php       # Registration
│   ├── dashboard.php      # Main dashboard
│   ├── create_certificate.php
│   ├── certificates.php   # All certificates (admin)
│   ├── my_certificates.php # Patient certificates
│   ├── view_certificate.php
│   ├── patients.php       # Patient list (admin)
│   ├── profile.php        # User profile
│   └── includes/
│       └── sidebar.php    # Navigation sidebar
├── api/                    # API endpoints
│   ├── soap_server.php    # SOAP API for validation
│   ├── json.php           # JSON REST API
│   ├── xml.php            # XML export
│   ├── validate.php       # QR code validation page
│   └── download.php       # Certificate download
└── includes/
    └── qr_generator.php   # QR code generation
```

## API Endpoints

### SOAP API
- **Endpoint**: `http://localhost/SYSTEMINTEG/api/soap_server.php`
- **WSDL**: `http://localhost/SYSTEMINTEG/api/soap_server.php?wsdl`
- **Function**: `validateCertificate($cert_id)`

### JSON API
- **Endpoint**: `http://localhost/SYSTEMINTEG/api/json.php?cert_id=MED-XXXXXXXX`
- **Response**: JSON formatted certificate data

### XML Export
- **Endpoint**: `http://localhost/SYSTEMINTEG/api/xml.php?cert_id=MED-XXXXXXXX`
- **Response**: XML formatted certificate data

### QR Validation
- **Endpoint**: `http://localhost/SYSTEMINTEG/api/validate.php?cert_id=MED-XXXXXXXX`
- **Usage**: Opens when scanning QR code

## Workflow

### For Clinic Admin:
1. Login with clinic admin account
2. Navigate to "Create Certificate"
3. Select patient and fill in certificate details
4. System generates QR code and certificate ID
5. Certificate is saved and ready for patient access

### For Patient:
1. Register or login as patient
2. View "My Certificates"
3. Click on certificate to view full details
4. Download as PDF or print
5. Share certificate via QR code

### For HR/Verification:
1. Scan QR code on certificate
2. Redirected to validation page
3. View certificate authenticity and details
4. Or call SOAP API for programmatic validation

## Technical Features

### Security
- Password hashing using PHP `password_hash()`
- Session management
- SQL injection prevention via prepared statements
- Input sanitization

### Technologies Used
- **PHP**: Backend logic and server-side processing
- **MySQL**: Database management
- **Bootstrap 5**: Responsive UI
- **SOAP**: Certificate validation web service
- **JSON**: RESTful API for mobile apps
- **XML**: Export for integration
- **cURL**: External API calls for QR generation
- **Google Charts API**: QR code generation

## Course Requirement Checklist

- ✅ File Processing (Upload/Download certificates)
- ✅ Database Connectivity (MySQL with multiple tables)
- ✅ Web Services & SOAP (Certificate validation API)
- ✅ XML Handling (Export certificate data)
- ✅ JSON Encoding/Decoding (Mobile app API)
- ✅ cURL Functions (QR code generation)
- ✅ UI Design (Bootstrap dashboard)

## Development Notes

- The system uses prepared statements to prevent SQL injection
- QR codes are generated using Google Charts API via cURL
- SOAP server is implemented natively in PHP
- All dates are stored in MySQL DATE format
- File paths use relative paths for portability

## Future Enhancements

- Email notifications for certificate issuance
- PDF certificate generation library
- Mobile app with offline support
- Advanced search and filtering
- Certificate expiry notifications
- Bulk certificate generation
- Analytics and reporting dashboard

## License

This project is developed for educational purposes as part of system integration coursework.

## Support

For issues or questions, refer to the course instructor or documentation.

