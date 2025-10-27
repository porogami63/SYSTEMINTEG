# MediArchive - Quick Setup Guide

## 🚀 Quick Start (3 Steps)

### Step 1: Import Database
1. Start XAMPP (Apache + MySQL)
2. Open phpMyAdmin: http://localhost/phpmyadmin
3. Click "Import" tab
4. Select file: `database.sql`
5. Click "Go" to import

### Step 2: Access System
- Navigate to: http://localhost/SYSTEMINTEG/

### Step 3: Login
**Clinic Admin:**
- Username: `admin`
- Password: `password`

**Patient:**
- Username: `patient1`  
- Password: `password`

---

## 📋 What's Included

### Core Features Implemented ✅

1. **Authentication System**
   - User login/register
   - Role-based access (Clinic Admin / Patient)
   - Password hashing
   - Session management

2. **Clinic Admin Dashboard**
   - Create medical certificates
   - View all certificates
   - Manage patients
   - View patient list

3. **Patient Dashboard**
   - View my certificates
   - Download certificates
   - Print certificates
   - View profile

4. **Certificate Management**
   - Generate unique certificate IDs (MED-YYYYMMDD-XXXXX)
   - QR code generation
   - Download as PDF
   - Print certificate

5. **QR Code Verification**
   - Scan QR code on certificate
   - Validates authenticity
   - Shows certificate details
   - Tracks verification logs

6. **SOAP API**
   - Certificate validation service
   - WSDL: http://localhost/SYSTEMINTEG/api/soap_server.php?wsdl
   - Function: validateCertificate($cert_id)

7. **JSON REST API**
   - Endpoint: api/json.php?cert_id=MED-XXX
   - Returns certificate data as JSON
   - Mobile app friendly

8. **XML Export**
   - Endpoint: api/xml.php?cert_id=MED-XXX
   - Returns certificate data as XML
   - Government/HR integration ready

---

## 🧪 Testing the System

### Test As Clinic Admin:
1. Login with `admin` / `password`
2. Click "Create Certificate"
3. Select patient and fill form
4. Submit - Certificate ID is generated
5. Go to "All Certificates" to view

### Test As Patient:
1. Login with `patient1` / `password`
2. Click "My Certificates"
3. Click "View" on any certificate
4. Try:
   - **Download PDF**: Click "Download PDF"
   - **Print**: Click "Print Certificate"
   - **View JSON**: Click "View JSON"
   - **View XML**: Click "View XML"
   - **Scan QR**: Use mobile to scan QR code

### Test QR Code:
1. Open certificate from patient account
2. Scan QR code with mobile phone
3. Should redirect to validation page showing certificate details

### Test SOAP API:
```php
<?php
$client = new SoapClient('http://localhost/SYSTEMINTEG/api/soap_server.php?wsdl');
$result = $client->validateCertificate('MED-20250127-XXXXX');
print_r($result);
?>
```

### Test JSON API:
- Open in browser: http://localhost/SYSTEMINTEG/api/json.php?cert_id=MED-20250127-XXXXX
- Replace XXXXX with actual certificate ID

---

## 📊 Database Tables

- **users**: User accounts (admins & patients)
- **clinics**: Clinic information
- **patients**: Patient profiles
- **certificates**: Medical certificates
- **verifications**: QR scan logs

---

## 🔧 API Endpoints

| Endpoint | Purpose | Method |
|----------|---------|--------|
| `/api/soap_server.php?wsdl` | SOAP WSDL definition | GET |
| `/api/soap_server.php` | SOAP API endpoint | POST |
| `/api/json.php?cert_id=XXX` | Get certificate as JSON | GET |
| `/api/xml.php?cert_id=XXX` | Get certificate as XML | GET |
| `/api/validate.php?cert_id=XXX` | QR validation page | GET |
| `/api/download.php?id=XXX` | Download certificate | GET |

---

## 📝 Course Requirements Status

✅ **File Processing** - Upload/download certificates  
✅ **Database Connectivity** - MySQL with 5 tables  
✅ **Web Services + SOAP** - Certificate validation API  
✅ **XML Handling** - Export certificate data  
✅ **JSON Encoding/Decoding** - Mobile app API  
✅ **cURL Functions** - QR code generation via API  
✅ **UI Design** - Bootstrap dashboard  

---

## 🎯 Demo Scenario

**Scenario:** Clinic issues sick leave certificate

1. **Clinic Admin logs in**
   - Navigate to "Create Certificate"
   - Select patient "Alice Johnson"
   - Fill in diagnosis: "Flu - 3 days rest recommended"
   - Set purpose: "Sick Leave"
   - Issue date: Today
   - Submit

2. **System generates**
   - Certificate ID: MED-20250127-ABC123
   - QR code image saved
   - Certificate stored in database

3. **Patient logs in**
   - View "My Certificates"
   - Download or print certificate
   - Share QR code with HR

4. **HR scans QR code**
   - Redirected to validation page
   - Verifies certificate authenticity
   - Can see all details

5. **Integration (Optional)**
   - HR calls SOAP API programmatically
   - Gets JSON/XML data for their system
   - Certificate verified automatically

---

## ⚠️ Troubleshooting

**Database connection error:**
- Check MySQL is running in XAMPP
- Verify credentials in config.php

**SOAP error:**
- Enable SOAP extension in php.ini
- Add: `extension=soap`

**QR code not generating:**
- Check internet connection (uses Google Charts API)
- Check qrcodes/ directory permissions

**Permission denied:**
- Make sure uploads/ and qrcodes/ directories exist
- Set permissions to 777 (if needed)

---

## 📞 Support

For technical issues during defense/demo:
1. Check Apache and MySQL are running
2. Verify database is imported
3. Check phpMyAdmin for table structure
4. Review config.php for database settings

---

## 🎓 Defense Tips

**Highlight These Features:**
- SOAP API for enterprise integration
- QR code verification for mobile-first approach
- JSON/XML export for system integration
- Role-based access control
- Certificate lifecycle management

**Demo Flow:**
1. Show admin creating certificate (30 seconds)
2. Show patient viewing certificate (20 seconds)
3. Scan QR code on phone (20 seconds)
4. Show API endpoints (30 seconds)
5. Download certificate (10 seconds)

**Total demo time:** ~2 minutes

Good luck! 🚀

