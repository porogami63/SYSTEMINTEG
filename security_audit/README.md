MediArchive Security Probes
===========================

Small Python harness to run simple XSS and SQL injection probes against the running app.

Prerequisites
-------------
- Python 3.8+
- App running locally (XAMPP): `http://localhost/SYSTEMINTEG`

Setup
-----
1) Install dependencies:

```bash
pip install -r security_audit/requirements.txt
```

Run
---
```bash
python security_audit/test_security_manual.py --target http://localhost/SYSTEMINTEG
```

ZAP (OWASP) Scan
----------------
Requirements:
- Install OWASP ZAP and start it (desktop or daemon) on `localhost:8080`
- If an API key is configured in ZAP, pass it via `--apikey`

Run:
```bash
python security_audit/zap.py --target http://localhost/SYSTEMINTEG --apikey YOUR_ZAP_API_KEY
```

Outputs:
- `security_audit/zap_report.html`
- `security_audit/zap_report.json`

View report inside the app:
- Open `http://localhost/SYSTEMINTEG/views/zap.html`

What it does
------------
- XSS checks: sends common payloads to `api/validate.php` and `api/json.php` and verifies content is escaped.
- SQLi checks: posts typical injection payloads to `views/login.php` and ensures login is not bypassed (no redirect to `dashboard.php`).

Exit codes
----------
- `0` when all probes pass
- `1` if any probe fails


