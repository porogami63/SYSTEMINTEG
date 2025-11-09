# PDF Download Troubleshooting Guide

## Current Status
✅ **PDF generation is working correctly**
- All PDF structure checks pass
- Valid PDF 1.4 format
- Correct xref table with dynamic offsets
- Proper stream formatting

## If PDFs Still Won't Open

### 1. Check Your PDF Reader
Different PDF readers have different strictness levels:

**Try these readers:**
- Adobe Acrobat Reader (most strict, best for testing)
- Microsoft Edge (built-in PDF viewer)
- Google Chrome (built-in PDF viewer)
- Firefox (built-in PDF viewer)
- SumatraPDF (lightweight, Windows)

**Test command:**
```bash
# Generate a test PDF
php -r "require 'config.php'; PdfGenerator::generateCertificate(['cert_id'=>'TEST','patient_name'=>'Test','purpose'=>'Test','issue_date'=>'2025-01-01','issued_by'=>'Dr. Test'], 'temp/test.pdf');"

# Try opening with different readers
Start-Process "temp\test.pdf"
```

### 2. Check Browser Download Settings

**Issue:** Browser might be corrupting the download

**Solution:**
1. Right-click the download link → "Save Link As"
2. Don't open directly from browser
3. Save to disk first, then open with PDF reader

### 3. Check HTTP Headers

The download script (`api/download.php`) sends these headers:
```php
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="certificate_XXX.pdf"');
```

**Verify headers are correct:**
```bash
# Check what headers are being sent
curl -I "http://localhost/SYSTEMINTEG/api/download.php?id=1"
```

Should see:
```
Content-Type: application/pdf
Content-Disposition: attachment; filename="certificate_XXX.pdf"
```

### 4. Check for PHP Output Buffering Issues

**Issue:** Extra whitespace or output before PDF content

**Check `api/download.php`:**
```php
<?php
// NO WHITESPACE OR OUTPUT BEFORE THIS LINE!
require_once '../config.php';
// ... rest of code
```

**Also check `config.php` and `includes/bootstrap.php`:**
- No `?>` at end of file (can cause whitespace)
- No `echo` or `print` statements
- No BOM (Byte Order Mark) at start of file

### 5. Test Direct PDF Generation

Create `test_direct_download.php`:
```php
<?php
require_once 'config.php';

$cert = [
    'cert_id' => 'TEST-001',
    'patient_name' => 'Test Patient',
    'purpose' => 'Test Purpose',
    'issue_date' => date('Y-m-d'),
    'issued_by' => 'Dr. Test'
];

// Generate directly to browser
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="test.pdf"');

require_once 'includes/SimplePDF.php';
$pdf = new SimplePDF();
$pdf->addLine('TEST CERTIFICATE', 18, 'bold');
$pdf->newLine(20);
$pdf->addLine('This is a test', 12);

echo $pdf->output();
exit;
?>
```

Access: `http://localhost/SYSTEMINTEG/test_direct_download.php`

### 6. Check File Permissions

**Issue:** Temp directory not writable

**Solution:**
```bash
# Check temp directory exists and is writable
ls -la temp/
chmod 777 temp/  # On Linux/Mac
icacls temp /grant Everyone:F  # On Windows
```

### 7. Verify SimplePDF is Being Used

**Check which generator is active:**
```php
<?php
require_once 'config.php';

echo "TCPDF available: " . (class_exists('TCPDF') ? 'YES' : 'NO') . "\n";
echo "SimplePDF will be used: " . (!class_exists('TCPDF') ? 'YES' : 'NO') . "\n";

// Force SimplePDF
require_once 'includes/SimplePDF.php';
$pdf = new SimplePDF();
echo "SimplePDF loaded: " . (class_exists('SimplePDF') ? 'YES' : 'NO') . "\n";
?>
```

### 8. Check for Antivirus/Security Software

Some antivirus software blocks dynamically generated PDFs.

**Test:**
1. Temporarily disable antivirus
2. Try downloading PDF again
3. If it works, add exception for your localhost

### 9. Browser Console Errors

**Check browser console (F12):**
- Look for JavaScript errors
- Check Network tab for failed requests
- Verify PDF file is actually downloading (check size)

### 10. Compare with Reference PDF

Generate a known-good PDF:
```php
<?php
$pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n4 0 obj\n<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>\nendobj\n5 0 obj\n<< /Length 44 >>\nstream\nBT\n/F1 24 Tf\n100 700 Td\n(TEST) Tj\nET\nendstream\nendobj\nxref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000219 00000 n \n0000000314 00000 n \ntrailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n408\n%%EOF";
file_put_contents('temp/reference.pdf', $pdf);
echo "Reference PDF created. Try opening: temp/reference.pdf\n";
?>
```

If reference PDF opens but SimplePDF doesn't, there's still an issue.

## Common Error Messages

### "File is damaged and could not be repaired"
- **Cause:** Corrupted PDF structure or wrong byte offsets
- **Fix:** Already fixed in SimplePDF with dynamic offset calculation

### "Failed to load PDF document"
- **Cause:** Invalid PDF header or missing objects
- **Fix:** Verify all validation checks pass

### "Something went wrong"
- **Cause:** Generic error, could be many things
- **Fix:** Try different PDF reader, check browser console

## Still Not Working?

If PDFs still won't open after all these steps:

1. **Share the error message** - Exact text from PDF reader
2. **Share the file** - Upload generated PDF to file sharing service
3. **Check file size** - Is it 0 bytes? Too large? Expected size?
4. **Hex dump first 100 bytes:**
   ```bash
   xxd -l 100 temp/test.pdf
   ```
   Should start with: `25 50 44 46 2d 31 2e 34` (%PDF-1.4)

## Contact Info

If you need further help, provide:
- PDF reader name and version
- Browser name and version
- Operating system
- Exact error message
- File size of generated PDF
- First 100 bytes of PDF (hex dump)

---

**Last Updated:** 2025-11-09  
**Status:** PDF generation verified working, all checks pass
