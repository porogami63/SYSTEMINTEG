# MediArchive - Complete Setup Guide
**Version 5.0 - Enhanced Production Release**  
**Last Updated**: November 12, 2025

## 🚀 Quick Start (For Presentation/Demo - Saturday Setup)

### Prerequisites Checklist
- [ ] XAMPP installed
- [ ] Project files in `C:\xampp\htdocs\SYSTEMINTEG\`
- [ ] Internet connection (for QR code generation)

### 3-Step Setup

#### Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Click **Start** for Apache
3. Click **Start** for MySQL
4. Wait for green indicators

#### Step 2: Import Database
1. Open browser: `http://localhost/phpmyadmin`
2. Click **"New"** in left sidebar
3. Database name: `mediarchive`
4. Click **"Create"**
5. Select `mediarchive` database
6. Click **"Import"** tab
7. Click **"Choose File"**
8. Select: `C:\xampp\htdocs\SYSTEMINTEG\database.sql`
9. Click **"Go"** at bottom
10. Wait for "Import has been successfully finished"

#### Step 3: Access System
1. Open browser: `http://localhost/SYSTEMINTEG/`
2. Login with credentials below

#### Step 4: (Optional) Setup Security Testing
**Python Security Audit Tools:**
1. Install Python 3.8+ from https://www.python.org/
2. Open Command Prompt in project folder
3. Install dependencies:
   ```
   pip install -r security_audit/requirements.txt
   ```
4. Run security tests:
   ```
   python security_audit/test_security_manual.py --target http://localhost/SYSTEMINTEG
   ```

**OWASP ZAP Security Scanner:**
1. Download OWASP ZAP from https://www.zaproxy.org/download/
2. Install and start ZAP (it will run on localhost:8080)
3. Run ZAP scan:
   ```
   python security_audit/zap.py --target http://localhost/SYSTEMINTEG
   ```
4. View reports:
   - HTML: `security_audit/zap_report.html`
   - JSON: `security_audit/zap_report.json`
   - Web: `http://localhost/SYSTEMINTEG/views/zap.html`

### Default Login Credentials

**Clinic Administrator**
```
Username: dr.smith
Password: password
```

**Patient**
```
Username: alice.j
Password: password
```

**Web Administrator**
```
Username: webadmin
Password: password
```

### ✨ New in Version 5.0
- **Modal Login**: Click Login/Register on home page for elegant modal overlays
- **Payment System**: Process payments for certificates and appointments with transaction history
- **Payment Gates**: Appointments require payment before approval (when payment is required)
- **Transaction Pages**: My Transactions for patients and clinic transactions for doctors
- **Public Find Doctors**: Standalone public page accessible without login
- **Public Doctor Profiles**: View doctor profiles without logging in
- **Enhanced Analytics**: Web admin dashboard with comprehensive system analytics
- **Medical History**: Combined view of certificates and appointments
- **Modern UI**: Beautiful gradient designs matching home page aesthetic
- **Improved Sidebar**: Better organization and reduced crowding
- **Certificate Attestation**: Medical professional attestation with payment and signature verification
- **10MB File Uploads**: Larger file support in chat system

---

## 📦 Detailed Installation Guide

### For New Device Setup (Presentation Day)

#### Part 1: Install XAMPP

1. **Download XAMPP**
   - Visit: https://www.apachefriends.org/
   - Download: XAMPP for Windows (PHP 7.4 or higher)
   - File size: ~150MB

2. **Install XAMPP**
   - Run installer as Administrator
   - Installation path: `C:\xampp\` (recommended)
   - Select components:
     - [x] Apache
     - [x] MySQL
     - [x] PHP
     - [x] phpMyAdmin
   - Click "Next" through all steps
   - Uncheck "Learn more about Bitnami" at the end
   - Click "Finish"

3. **Start XAMPP**
   - Open XAMPP Control Panel
   - Click "Start" for Apache (should turn green)
   - Click "Start" for MySQL (should turn green)
   - If port 80 is busy:
     - Stop Skype or other services using port 80
     - Or change Apache port in Config → httpd.conf

#### Part 2: Copy Project Files

1. **Extract/Copy Project**
   - Copy entire `SYSTEMINTEG` folder
   - Paste into: `C:\xampp\htdocs\`
   - Final path: `C:\xampp\htdocs\SYSTEMINTEG\`

2. **Verify File Structure**
   ```
   C:\xampp\htdocs\SYSTEMINTEG\
   ├── api/
   ├── includes/
   ├── views/
   ├── config.php
   ├── database.sql
   ├── index.php
   └── README.md
   ```

#### Part 3: Create Database

**Option A: Using phpMyAdmin (Recommended)**

1. Open browser: `http://localhost/phpmyadmin`
2. Click "New" in left sidebar
3. Database name: `mediarchive`
4. Collation: `utf8mb4_general_ci`
5. Click "Create"
6. Select `mediarchive` from left sidebar
7. Click "Import" tab
8. Click "Choose File"
9. Navigate to: `C:\xampp\htdocs\SYSTEMINTEG\database.sql`
10. Click "Go"
11. Wait for success message

**Option B: Using MySQL Command Line**

1. Open Command Prompt
2. Navigate to MySQL bin:
   ```cmd
   cd C:\xampp\mysql\bin
   ```
3. Login to MySQL:
   ```cmd
   mysql -u root -p
   ```
   (Press Enter when asked for password - default is blank)
4. Create database:
   ```sql
   CREATE DATABASE mediarchive;
   USE mediarchive;
   SOURCE C:/xampp/htdocs/SYSTEMINTEG/database.sql;
   EXIT;
   ```

#### Part 4: Configure PHP (If Needed)

1. **Enable Required Extensions**
   - Open: `C:\xampp\php\php.ini`
   - Find and uncomment (remove `;`):
     ```ini
     extension=curl
     extension=gd
     extension=mbstring
     extension=pdo_mysql
     extension=soap
     extension=zip
     extension=xml
     ```
   - Save file
   - Restart Apache in XAMPP

2. **Adjust Upload Limits (Optional)**
   - In same `php.ini` file:
     ```ini
     upload_max_filesize = 10M
     post_max_size = 10M
     max_execution_time = 300
     ```
   - Save and restart Apache

#### Part 5: Verify Installation

1. **Check Database**
   - Open phpMyAdmin
   - Select `mediarchive` database
   - You should see 15+ tables:
     - users
     - clinics
     - patients
     - certificates
     - appointments
     - chat_conversations
     - chat_messages
     - notifications
     - audit_logs
     - etc.

2. **Check Website**
   - Open: `http://localhost/SYSTEMINTEG/`
   - You should see login page
   - No errors displayed

3. **Test Login**
   - Username: `admin`
   - Password: `password`
   - Should redirect to dashboard

---

## 🧪 Testing the System

### Test Scenario 1: Certificate Creation (Clinic Admin)

1. **Login as Clinic Admin**
   - Username: `admin`
   - Password: `password`

2. **Create Certificate**
   - Click "Create Certificate" in sidebar
   - Select patient: "Alice Johnson"
   - Fill form:
     - Purpose: "Sick Leave"
     - Diagnosis: "Flu - 3 days rest recommended"
     - Issue Date: Today
     - Expiry Date: 3 days from today
   - Click "Create Certificate"

3. **Verify Creation**
   - Should see success message
   - Certificate ID generated (e.g., MED-20251109-ABC123)
   - QR code image created
   - Redirected to certificate list

4. **View Certificate**
   - Click "All Certificates"
   - See newly created certificate
   - Click "View" to see details

### Test Scenario 2: Patient Access

1. **Logout** (top-right corner)

2. **Login as Patient**
   - Username: `patient1`
   - Password: `password`

3. **View Certificates**
   - Click "My Certificates"
   - See certificate created by admin
   - Click "View" to see full details

4. **Download PDF**
   - Click "Download PDF" button
   - PDF should download
   - Open PDF to verify content

5. **Test Other Features**
   - Click "View JSON" → See JSON data
   - Click "View XML" → See XML data
   - Click "Print Certificate" → Print preview

### Test Scenario 3: QR Code Validation

1. **Get QR Code**
   - Open certificate in patient view
   - QR code is displayed

2. **Scan with Mobile**
   - Use phone camera or QR scanner app
   - Scan the QR code
   - Should redirect to validation page
   - Shows certificate details

3. **Manual Validation**
   - Copy certificate ID (e.g., MED-20251109-ABC123)
   - Open: `http://localhost/SYSTEMINTEG/api/validate.php?cert_id=MED-20251109-ABC123`
   - Should show validation page

### Test Scenario 4: Chat System

1. **Login as Patient**
   - Username: `patient1`
   - Password: `password`

2. **Start Conversation**
   - Click "Messages" in sidebar
   - Click "+ New" button
   - Select clinic: "Green Valley Medical Center"
   - Type message: "Hello, I need to schedule an appointment"
   - Click "Start Conversation"

3. **Send Messages**
   - Type another message
   - Click "Send"
   - Try attaching a file (image or document)

4. **Check as Clinic Admin**
   - Logout
   - Login as `admin` / `password`
   - Click "Messages"
   - See conversation from patient
   - Reply to message

5. **Web Admin Moderation**
   - Logout
   - Login as `webadmin` / `password`
   - Click "Messages (Moderation)"
   - See all conversations
   - Can read but not send (read-only)

### Test Scenario 5: Appointments

1. **Login as Patient**
   - Username: `patient1`
   - Password: `password`

2. **Book Appointment**
   - Click "Appointments" in sidebar
   - Click "Book New Appointment"
   - Select clinic
   - Choose specialization
   - Select date and time
   - Enter purpose
   - Click "Book Appointment"

3. **Check as Clinic Admin**
   - Logout
   - Login as `admin` / `password`
   - Click "Appointments"
   - See pending appointment
   - Click "Approve"

4. **Verify Notification**
   - Patient should receive notification
   - Bell icon shows unread count

### Test Scenario 6: API Endpoints

1. **Test JSON API**
   - Get a certificate ID (e.g., MED-20251109-ABC123)
   - Open browser:
     ```
     http://localhost/SYSTEMINTEG/api/json.php?cert_id=MED-20251109-ABC123
     ```
   - Should see JSON response

2. **Test XML API**
   - Same certificate ID
   - Open browser:
     ```
     http://localhost/SYSTEMINTEG/api/xml.php?cert_id=MED-20251109-ABC123
     ```
   - Should see XML response

3. **Test SOAP API**
   - Create file: `test_soap.php`
   - Content:
     ```php
     <?php
     $client = new SoapClient('http://localhost/SYSTEMINTEG/api/soap_server.php?wsdl');
     $result = $client->validateCertificate('MED-20251109-ABC123');
     print_r($result);
     ?>
     ```
   - Run in browser
   - Should see certificate data

---

## ⚙️ Configuration

### Database Configuration

Edit `config.php` if needed:

```php
// Database settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP has no password
define('DB_NAME', 'mediarchive');
```

### File Upload Settings

Default limits in `config.php`:
```php
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
```

### Timezone Settings

In `config.php`:
```php
date_default_timezone_set('Asia/Manila'); // Adjust as needed
```

---

## 🔧 Troubleshooting

### Problem: "Database connection failed"

**Solution:**
1. Check MySQL is running in XAMPP (green light)
2. Verify database name is `mediarchive`
3. Check credentials in `config.php`
4. Try accessing phpMyAdmin: `http://localhost/phpmyadmin`

### Problem: "SOAP extension not found"

**Solution:**
1. Open `C:\xampp\php\php.ini`
2. Find line: `;extension=soap`
3. Remove semicolon: `extension=soap`
4. Save file
5. Restart Apache in XAMPP

### Problem: "QR code not generating"

**Solution:**
1. Check internet connection (uses Google Charts API)
2. Verify `qrcodes/` folder exists
3. Check folder permissions (should be writable)
4. Try creating folder manually: `C:\xampp\htdocs\SYSTEMINTEG\qrcodes\`

### Problem: "Permission denied" for uploads

**Solution:**
1. Create folders if they don't exist:
   - `C:\xampp\htdocs\SYSTEMINTEG\uploads\`
   - `C:\xampp\htdocs\SYSTEMINTEG\qrcodes\`
   - `C:\xampp\htdocs\SYSTEMINTEG\temp\`
2. Right-click folder → Properties → Security
3. Give "Full Control" to "Users"

### Problem: "PDF generation error"

**Solution:**
1. Verify DomPDF library exists:
   - `C:\xampp\htdocs\SYSTEMINTEG\includes\dompdf\`
2. Check PHP GD extension is enabled
3. Verify temp folder is writable

### Problem: "Port 80 already in use"

**Solution:**
1. Stop Skype (uses port 80)
2. Or change Apache port:
   - XAMPP Control → Apache Config → httpd.conf
   - Find: `Listen 80`
   - Change to: `Listen 8080`
   - Access via: `http://localhost:8080/SYSTEMINTEG/`

### Problem: "Blank page / White screen"

**Solution:**
1. Enable error display:
   - Edit `config.php`
   - Add at top:
     ```php
     ini_set('display_errors', 1);
     error_reporting(E_ALL);
     ```
2. Check Apache error log:
   - `C:\xampp\apache\logs\error.log`

### Problem: "Session errors"

**Solution:**
1. Check session folder exists:
   - `C:\xampp\tmp\`
2. Clear browser cookies
3. Try different browser

---

## 📋 Pre-Presentation Checklist

### 1 Week Before
- [ ] Install XAMPP on presentation device
- [ ] Copy project files
- [ ] Import database
- [ ] Test all features
- [ ] Create sample certificates
- [ ] Prepare QR codes for scanning

### 1 Day Before
- [ ] Verify XAMPP starts correctly
- [ ] Test all login credentials
- [ ] Check internet connection (for QR generation)
- [ ] Prepare backup USB drive
- [ ] Test on actual presentation device

### 1 Hour Before
- [ ] Start XAMPP
- [ ] Open system in browser
- [ ] Login to all accounts
- [ ] Have sample data ready
- [ ] Test QR scanner on phone
- [ ] Close unnecessary applications

### During Presentation
- [ ] XAMPP running (Apache + MySQL green)
- [ ] Browser open to login page
- [ ] Phone ready for QR scanning
- [ ] Backup plan if internet fails

---

## 🎯 Demo Script (2-Minute Presentation)

### Minute 1: Core Features (60 seconds)

**[0:00-0:15] Login & Dashboard**
- "This is MediArchive, a digital medical certificate system"
- Login as clinic admin
- Show dashboard with statistics

**[0:15-0:35] Create Certificate**
- Click "Create Certificate"
- Select patient "Alice Johnson"
- Fill: Purpose "Sick Leave", Diagnosis "Flu"
- Click "Create" → Show generated certificate ID and QR code

**[0:35-0:50] Patient View**
- Logout, login as patient
- Click "My Certificates"
- Show certificate details
- Click "Download PDF" → Show PDF

**[0:50-1:00] QR Validation**
- Scan QR code with phone
- Show validation page on phone screen

### Minute 2: Advanced Features (60 seconds)

**[1:00-1:20] Chat System**
- Click "Messages"
- Show conversation with clinic
- Send a message
- Show file attachment feature

**[1:20-1:35] Appointments**
- Click "Appointments"
- Show appointment booking interface
- Demonstrate appointment approval (as admin)

**[1:35-1:50] API Integration**
- Open JSON endpoint in new tab
- Show structured data
- Mention SOAP and XML APIs

**[1:50-2:00] Admin Features**
- Login as web admin
- Show audit logs
- Show analytics dashboard
- "Thank you!"

---

## 📊 Database Tables Reference

### Core Tables
- **users** (4 records) - User accounts
- **clinics** (1 record) - Clinic information
- **patients** (2 records) - Patient profiles
- **certificates** (0 records initially) - Medical certificates
- **certificate_requests** (0 records) - Certificate requests

### Feature Tables
- **appointments** (0 records) - Appointment bookings
- **chat_conversations** (0 records) - Chat conversations
- **chat_messages** (0 records) - Chat messages
- **notifications** (0 records) - User notifications
- **notification_preferences** (0 records) - Notification settings

### System Tables
- **audit_logs** (0 records) - Activity logs
- **verifications** (0 records) - QR scan logs
- **certificate_notes** (0 records) - Internal notes

---

## 🔐 Security Notes

### For Production Deployment

1. **Change Default Passwords**
   ```sql
   UPDATE users SET password = PASSWORD_HASH('new_password', PASSWORD_DEFAULT) 
   WHERE username = 'admin';
   ```

2. **Disable Error Display**
   ```php
   // In config.php
   ini_set('display_errors', 0);
   error_reporting(0);
   ```

3. **Enable HTTPS**
   - Use SSL certificate
   - Force HTTPS in .htaccess

4. **Secure File Uploads**
   - Validate file types
   - Scan for malware
   - Limit file sizes

5. **Database Backup**
   - Regular automated backups
   - Store offsite

---

## 📞 Support

### For Technical Issues During Presentation

1. **Quick Fixes**
   - Restart XAMPP
   - Clear browser cache
   - Check database connection
   - Verify file permissions

2. **Backup Plan**
   - Have screenshots ready
   - Prepare video demo
   - Use backup device
   - Have USB with project files

3. **Contact**
   - Course instructor
   - IT support
   - Team members

---

## ✅ Post-Installation Verification

Run this checklist after setup:

### System Access
- [ ] Can access `http://localhost/SYSTEMINTEG/`
- [ ] Login page loads without errors
- [ ] Can login as admin
- [ ] Can login as patient
- [ ] Can login as webadmin

### Database
- [ ] Database `mediarchive` exists
- [ ] All 15+ tables created
- [ ] Sample data loaded (4 users, 1 clinic, 2 patients)
- [ ] Can view tables in phpMyAdmin

### Features
- [ ] Can create certificate
- [ ] QR code generates
- [ ] Can download PDF
- [ ] Can send chat message
- [ ] Can book appointment
- [ ] Notifications work
- [ ] JSON API responds
- [ ] XML API responds
- [ ] SOAP API responds (if SOAP enabled)

### File System
- [ ] `uploads/` folder exists and writable
- [ ] `qrcodes/` folder exists and writable
- [ ] `temp/` folder exists and writable
- [ ] Profile photos upload successfully

---

## 🎓 Additional Resources

### Documentation
- README.md - Complete system documentation
- This file (SETUP_GUIDE.md) - Installation guide
- Code comments - Inline documentation

### Learning Resources
- PHP Manual: https://www.php.net/manual/
- MySQL Documentation: https://dev.mysql.com/doc/
- Bootstrap 5: https://getbootstrap.com/docs/5.0/

### Tools
- XAMPP: https://www.apachefriends.org/
- phpMyAdmin: Included with XAMPP
- Visual Studio Code: Recommended code editor

---

## 🏆 Success Criteria

Your installation is successful if:

✅ All pages load without errors  
✅ Can create and view certificates  
✅ QR codes generate correctly  
✅ PDF downloads work  
✅ Chat system functions  
✅ Appointments can be booked  
✅ All three user roles work  
✅ APIs return correct data  
✅ No database connection errors  
✅ File uploads work  

---

**Setup Complete!** 🎉

You're now ready for your presentation. Good luck! 🚀

For any issues, refer to the Troubleshooting section or contact your instructor.
