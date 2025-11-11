<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MediArchive - Digital Medical Certificate System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
:root {
    --brand-primary: #0f63d6;
    --brand-dark: #0b3d91;
    --brand-light: #eef5ff;
    --accent-cyan: #1bd6d2;
    --accent-lime: #7bf1a8;
    --text-body: #1f2a44;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: var(--text-body);
    background: #f7f9ff;
}

.navbar {
    background: rgba(15, 99, 214, 0.92);
    backdrop-filter: blur(12px);
    box-shadow: 0 10px 30px rgba(11, 61, 145, 0.25);
    padding: 1rem 0;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    color: #fff !important;
    letter-spacing: 0.5px;
}

.nav-link {
    color: rgba(255,255,255,0.9) !important;
    font-weight: 500;
    transition: all 0.2s ease;
}

.nav-link:hover {
    color: #fff !important;
    transform: translateY(-2px);
}

.hero-section {
    position: relative;
    padding: 120px 0 140px;
    background: linear-gradient(135deg, rgba(15, 99, 214, 0.95), rgba(11, 61, 145, 0.95)), url('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat;
    color: #fff;
    overflow: hidden;
}

.hero-section::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, rgba(15, 99, 214, 0.85) 0%, rgba(30, 125, 210, 0.6) 45%, rgba(27, 214, 210, 0.25) 100%);
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.12);
    border-radius: 999px;
    padding: 10px 20px;
    margin-bottom: 16px;
    font-weight: 600;
    letter-spacing: 0.6px;
}

.hero-section h1 {
    font-size: clamp(2.6rem, 4vw, 3.8rem);
    font-weight: 700;
    line-height: 1.15;
    margin-bottom: 20px;
}

.hero-section .lead {
    font-size: 1.2rem;
    max-width: 640px;
    margin: 0 auto 28px;
    opacity: 0.92;
}

.ticker {
    position: relative;
    background: rgba(11, 22, 53, 0.35);
    border-radius: 999px;
    overflow: hidden;
    padding: 10px 0;
    margin-bottom: 28px;
}

.ticker-track {
    position: relative;
    display: inline-block;
    animation: ticker 18s linear infinite;
    white-space: nowrap;
    font-weight: 600;
    letter-spacing: 0.6px;
}

@keyframes ticker {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

.btn-hero {
    padding: 14px 38px;
    font-weight: 600;
    border-radius: 40px;
    background: linear-gradient(135deg, #1bd6d2 0%, #7bf1a8 100%);
    border: none;
    color: #07295f;
    box-shadow: 0 12px 25px rgba(27, 214, 210, 0.35);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-hero:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 35px rgba(27, 214, 210, 0.4);
}

.hero-image-wrapper {
    position: relative;
    z-index: 2;
    backdrop-filter: blur(6px);
}

.hero-image {
    border-radius: 24px;
    box-shadow: 0 25px 60px rgba(10, 29, 71, 0.45);
}

.stat-card {
    background: rgba(255,255,255,0.12);
    border-radius: 16px;
    padding: 18px 20px;
    color: #fff;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.2);
}

.stat-card h3 {
    font-size: 1.8rem;
    margin-bottom: 4px;
}

.section-title {
    color: var(--brand-dark);
    font-weight: 700;
    letter-spacing: 0.6px;
}

.section-subtitle {
    color: #5f6b8b;
    max-width: 760px;
    margin: 0 auto 48px;
}

.mission-card {
    background: linear-gradient(135deg, #ffffff 0%, #f3f6ff 100%);
    border-radius: 18px;
    padding: 32px;
    height: 100%;
    border: 1px solid #dde6ff;
    box-shadow: 0 18px 40px rgba(15, 99, 214, 0.1);
}

.mission-card .icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display:flex;
    align-items:center;
    justify-content:center;
    background: rgba(15, 99, 214, 0.12);
    color: var(--brand-primary);
    font-size: 1.6rem;
    margin-bottom: 18px;
}

.capabilities-section {
    background: #ffffff;
    padding: 90px 0;
}

.capability-card {
    border-radius: 18px;
    padding: 30px;
    border: 1px solid #e3ebff;
    transition: transform .25s, box-shadow .25s;
    height: 100%;
}

.capability-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 18px 40px rgba(15, 99, 214, 0.15);
}

.capability-icon {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    background: rgba(15, 99, 214, 0.1);
    color: var(--brand-primary);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size: 1.5rem;
    margin-bottom: 16px;
}

.audience-section {
    background: linear-gradient(120deg, #0f63d6 0%, #0b3d91 100%);
    color: #fff;
    padding: 90px 0;
    position: relative;
    overflow: hidden;
}

.audience-card {
    background: rgba(255, 255, 255, 0.12);
    border-radius: 18px;
    padding: 32px;
    height: 100%;
    border: 1px solid rgba(255,255,255,0.2);
}

.audience-card h4 { color: #fff; }
.audience-card p { color: rgba(255, 255, 255, 0.85); }

.security-section {
    background: #f0f5ff;
    padding: 90px 0;
}

.security-card {
    border-radius: 16px;
    background: #fff;
    padding: 26px;
    border: 1px solid #dbe5ff;
    height: 100%;
}

.cta-section {
    background: radial-gradient(circle at top left, rgba(27, 214, 210, 0.25), transparent 55%), linear-gradient(135deg, #0f63d6 0%, #0b3d91 100%);
    border-radius: 24px;
    padding: 60px 50px;
    color: #fff;
    position: relative;
    overflow: hidden;
}

.cta-section::after {
    content: '';
    position: absolute;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: rgba(27, 214, 210, 0.25);
    right: -40px;
    bottom: -40px;
}

.footer {
    background: #071a3a;
    color: rgba(255,255,255,0.8);
    padding: 30px 0;
    margin-top: 80px;
}

@media (max-width: 992px) {
    .hero-section {
        padding: 90px 0;
    }
    .hero-section .lead {
        font-size: 1.05rem;
    }
}

@media (max-width: 768px) {
    .hero-section {
        text-align: center;
    }
    .hero-section::after {
        background: linear-gradient(135deg, rgba(15, 99, 214, 0.92), rgba(11, 61, 145, 0.92));
    }
    .navbar-brand span { display: none; }
}
</style>
</head>
<body>
<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;color:#1565c0;"><i class="bi bi-hospital-fill"></i></span>
            <span>MediArchive</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="views/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="views/logout.php">Logout</a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="views/login.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="views/register.php">Register</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container position-relative">
        <div class="row align-items-center g-5 hero-content">
            <div class="col-lg-6">
                <span class="hero-badge"><i class="bi bi-shield-check"></i> Digital Medical Certificate Platform</span>
                <h1>All your medical certificates, verified and within reach.</h1>
                <p class="lead">MediArchive digitizes certificate issuance, QR verification, chat, and appointments so clinics and patients collaborate confidently in a single secure hub.</p>
                <div class="ticker">
                    <div class="ticker-track">Real-time QR validation • Secure cloud storage • Patient-clinic messaging • Analytics-ready audit logs • Real-time QR validation • Secure cloud storage • Patient-clinic messaging • Analytics-ready audit logs</div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <a href="<?php echo isLoggedIn() ? 'views/dashboard.php' : 'views/register.php'; ?>" class="btn btn-hero">Get Started</a>
                    <a href="#capabilities" class="btn btn-outline-light border-2 px-4">Explore Capabilities</a>
                </div>
                <div class="row g-3 mt-4">
                    <div class="col-6 col-md-4">
                        <div class="stat-card">
                            <h3>10K+</h3>
                            <p class="mb-0 small">Certificates issued with automatic QR tags</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="stat-card">
                            <h3>24/7</h3>
                            <p class="mb-0 small">Verification via REST, SOAP, and QR scanning</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="stat-card">
                            <h3>5x</h3>
                            <p class="mb-0 small">Faster compliance reporting with audit trails</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1584452964155-ef139340f0db?q=80&w=1200&auto=format&fit=crop" class="img-fluid hero-image" alt="MediArchive dashboard preview">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Built for modern medical compliance</h2>
            <p class="section-subtitle">MediArchive is a System Integration capstone that digitizes medical certificate issuance, validation, chat, and appointments so clinics, patients, and verifiers stay aligned.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="mission-card h-100">
                    <div class="icon"><i class="bi bi-bullseye"></i></div>
                    <h4 class="fw-bold">Mission</h4>
                    <p class="mb-3">Replace paper-based certificates with a secure digital platform that keeps everyone in sync—clinic administrators, patients, and verification teams.</p>
                    <ul class="mb-0 text-muted small">
                        <li>Digitize certificate creation with QR codes and PDF exports</li>
                        <li>Streamline patient-clinic communication through secure chat</li>
                        <li>Automate appointment booking and status tracking</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mission-card h-100">
                    <div class="icon"><i class="bi bi-binoculars"></i></div>
                    <h4 class="fw-bold">Vision</h4>
                    <p class="mb-3">Deliver a trusted medical record experience where certificates are portable, instantly verifiable, and compliant with enterprise and government integrations.</p>
                    <ul class="mb-0 text-muted small">
                        <li>Provide real-time verification using REST, SOAP, and XML endpoints</li>
                        <li>Offer analytics and audit logs for transparent reporting</li>
                        <li>Scale to enterprise use with layered security and modular design</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Capabilities -->
<section id="capabilities" class="capabilities-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Core capabilities that power MediArchive</h2>
            <p class="section-subtitle">Everything is built around secure certificate lifecycle management, from issuance and verification to communication and analytics.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="capability-card h-100">
                    <div class="capability-icon"><i class="bi bi-file-medical"></i></div>
                    <h4 class="fw-semibold">Digital Certificate Engine</h4>
                    <p class="text-muted small">Generate MED-IDs, attach doctor signatures, and export PDF copies with embedded QR verification.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="capability-card h-100">
                    <div class="capability-icon"><i class="bi bi-qr-code-scan"></i></div>
                    <h4 class="fw-semibold">Instant QR Validation</h4>
                    <p class="text-muted small">Validate certificates 24/7 via QR scans, REST JSON, SOAP, or XML endpoints—ideal for HR and government partners.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="capability-card h-100">
                    <div class="capability-icon"><i class="bi bi-chat-dots"></i></div>
                    <h4 class="fw-semibold">Secure Patient-Clinic Chat</h4>
                    <p class="text-muted small">Exchange messages, attachments, and availability indicators with moderation controls for administrators.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="capability-card h-100">
                    <div class="capability-icon"><i class="bi bi-calendar-check"></i></div>
                    <h4 class="fw-semibold">Smart Appointment Hub</h4>
                    <p class="text-muted small">Patients book by specialization, clinics approve or reschedule, and everyone tracks statuses in real time.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="capability-card h-100">
                    <div class="capability-icon"><i class="bi bi-lock-shield"></i></div>
                    <h4 class="fw-semibold">Security & Audit Suite</h4>
                    <p class="text-muted small">CSRF tokens, CSP headers, rate limiting, audit logs, and automated security reports keep data protected.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="capability-card h-100">
                    <div class="capability-icon"><i class="bi bi-bar-chart"></i></div>
                    <h4 class="fw-semibold">Analytics & Reporting</h4>
                    <p class="text-muted small">Track certificate trends, appointment performance, and verification logs from the dashboard.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Audience -->
<section class="audience-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title text-white">Designed for every stakeholder</h2>
            <p class="section-subtitle text-white-50">MediArchive connects clinics, patients, web admins, and verification teams around a single source of truth.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="audience-card">
                    <h4 class="fw-semibold"><i class="bi bi-hospital me-2"></i>Clinics & Doctors</h4>
                    <p class="small">Issue certificates, manage appointments, attach digital signatures, and monitor patients from a unified workspace.</p>
                    <ul class="small text-white-50 mb-0">
                        <li>Digital signatures & seals</li>
                        <li>Availability toggles & operating hours</li>
                        <li>Audit-ready issuance logs</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="audience-card">
                    <h4 class="fw-semibold"><i class="bi bi-person-heart me-2"></i>Patients</h4>
                    <p class="small">Request documents, chat with clinics, track history, and verify certificates instantly from any device.</p>
                    <ul class="small text-white-50 mb-0">
                        <li>Certificate wallet with QR validation</li>
                        <li>Appointment scheduling & reminders</li>
                        <li>Secure chat with file attachments</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="audience-card">
                    <h4 class="fw-semibold"><i class="bi bi-shield-lock me-2"></i>Web Admin & Verifiers</h4>
                    <p class="small">Monitor security events, run automated audits, and validate certificates via API integrations.</p>
                    <ul class="small text-white-50 mb-0">
                        <li>Comprehensive audit logging</li>
                        <li>Security scorecards (ZAP-powered)</li>
                        <li>REST / SOAP / XML verification endpoints</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Security & Compliance -->
<section class="security-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Trust engineered into every layer</h2>
            <p class="section-subtitle">From authentication to audit logs, MediArchive upholds security best practices documented in our System Integration deliverables.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="security-card h-100">
                    <h5 class="fw-semibold"><i class="bi bi-fingerprint me-2 text-primary"></i>Secure Authentication</h5>
                    <p class="small text-muted mb-0">Bcrypt password hashing, session hardening, CSRF protection, and role-based access across patient, clinic, and web admin personas.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="security-card h-100">
                    <h5 class="fw-semibold"><i class="bi bi-shield-lock me-2 text-primary"></i>Content Security & Audits</h5>
                    <p class="small text-muted mb-0">Strict CSP headers, rate limiting, and automated OWASP ZAP assessments with downloadable audit certificates.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="security-card h-100">
                    <h5 class="fw-semibold"><i class="bi bi-cloud-arrow-down me-2 text-primary"></i>Integration Ready</h5>
                    <p class="small text-muted mb-0">JSON, SOAP, and XML services ensure HR partners and legacy systems can validate certificates without logging in.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5">
    <div class="container">
        <div class="cta-section">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <h2 class="fw-bold">Ready to modernize medical certificate management?</h2>
                    <p class="mb-0">Join MediArchive to experience secure certificate lifecycles, QR verification, real-time chat, and actionable analytics—built for clinics, patients, and verifiers.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?php echo isLoggedIn() ? 'views/dashboard.php' : 'views/register.php'; ?>" class="btn-hero">Launch MediArchive</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container text-center">
        <p class="mb-0">© 2025 MediArchive. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>