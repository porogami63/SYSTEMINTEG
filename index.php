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

/* Top Info Bar */
.top-info-bar {
    background: #0b5394;
    color: white;
    font-size: 0.85rem;
    padding: 8px 0;
}

.top-info-bar a {
    color: white;
    text-decoration: none;
    margin: 0 10px;
}

.top-info-bar i {
    margin-right: 5px;
}

.top-info-bar .social-icons a {
    margin: 0 5px;
    font-size: 0.9rem;
}

/* Main Navbar */
.navbar {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 0;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.3rem;
    color: #0b5394 !important;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.navbar-brand i {
    font-size: 1.8rem;
    color: #0b5394;
}

.nav-link {
    color: #333 !important;
    font-weight: 500;
    padding: 1.2rem 1rem !important;
    transition: all 0.2s ease;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.nav-link:hover {
    color: #0b5394 !important;
    background: #f0f7ff;
}

.btn-book-appointment {
    background: #0b5394;
    color: white;
    border-radius: 25px;
    padding: 10px 25px;
    font-weight: 600;
    border: none;
    margin-left: 15px;
}

.btn-book-appointment:hover {
    background: #084070;
    color: white;
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
<!-- Top Info Bar -->
<div class="top-info-bar">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span><i class="bi bi-telephone-fill"></i> EMERGENCY CALL +1 9088 3060 / 8088 7000</span>
                <span><i class="bi bi-envelope-fill"></i> or chat with us</span>
            </div>
            <div class="col-md-4 text-end">
                <span class="social-icons">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-twitter"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-youtube"></i></a>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Main Navigation Bar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-hospital-fill"></i>
            <span>MediArchive</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="#about">ABOUT US</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">OUR SERVICES</a></li>
                <li class="nav-item"><a class="nav-link" href="views/find_doctors_public.php">FIND A DOCTOR</a></li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item"><a class="nav-link" href="views/dashboard.php">DASHBOARD</a></li>
                <li class="nav-item"><a class="nav-link" href="views/logout.php?redirect=<?php echo urlencode('/SYSTEMINTEG/index.php'); ?>">LOGOUT</a></li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">LOGIN</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="#contact">CONTACT US</a></li>
                <li class="nav-item"><a class="nav-link" href="#search"><i class="bi bi-search"></i></a></li>
                <?php if (!isLoggedIn()): ?>
                <li class="nav-item">
                    <button class="btn btn-book-appointment" data-bs-toggle="modal" data-bs-target="#registerModal">
                        GET STARTED
                    </button>
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
                    <?php if (isLoggedIn()): ?>
                    <a href="views/dashboard.php" class="btn btn-hero">Get Started</a>
                    <?php else: ?>
                    <a href="#" class="btn btn-hero" data-bs-toggle="modal" data-bs-target="#registerModal">Get Started</a>
                    <?php endif; ?>
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

<!-- About Us Section -->
<section id="about" class="py-5 bg-white">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h2 class="section-title mb-4">About MediArchive</h2>
                <p class="lead mb-4">Your trusted partner in digital healthcare documentation and medical certificate management.</p>
                <p class="text-muted mb-3">MediArchive is a comprehensive digital medical certificate system designed to streamline healthcare documentation, enhance security, and improve patient-clinic communication. We bridge the gap between traditional medical practices and modern digital solutions.</p>
                <p class="text-muted mb-4">Founded with a vision to revolutionize medical documentation, we provide healthcare providers and patients with secure, efficient, and accessible tools for managing medical certificates, appointments, and health records.</p>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-2"></i>
                            <span><strong>100%</strong> Secure</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-2"></i>
                            <span><strong>24/7</strong> Available</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-2"></i>
                            <span><strong>HIPAA</strong> Compliant</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-2"></i>
                            <span><strong>QR</strong> Verified</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&auto=format&fit=crop" alt="Medical Team" class="img-fluid rounded shadow-lg">
            </div>
        </div>
    </div>
</section>

<!-- Our Services Section -->
<section id="services" class="py-5" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Our Services</h2>
            <p class="section-subtitle">Comprehensive healthcare documentation solutions for modern medical practices</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-file-earmark-medical-fill text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold">Digital Certificates</h5>
                    <p class="text-muted small">Issue, manage, and verify medical certificates with QR code authentication</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-calendar-heart-fill text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold">Appointment Booking</h5>
                    <p class="text-muted small">Schedule and manage appointments with your preferred healthcare providers</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-chat-dots-fill text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold">Secure Messaging</h5>
                    <p class="text-muted small">Communicate securely with your doctor through encrypted chat system</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-shield-fill-check text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold">QR Verification</h5>
                    <p class="text-muted small">Instant certificate validation via QR code scanning and API integration</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-folder-fill text-info" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold">Medical Records</h5>
                    <p class="text-muted small">Access your complete medical history and certificates anytime, anywhere</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-graph-up-arrow text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold">Analytics Dashboard</h5>
                    <p class="text-muted small">Track trends, monitor performance, and generate comprehensive reports</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-credit-card-fill text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold">Payment Integration</h5>
                    <p class="text-muted small">Secure online payment processing for certificates and consultations</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-bell-fill text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold">Real-time Notifications</h5>
                    <p class="text-muted small">Stay updated with instant alerts for appointments and certificate updates</p>
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
                    <?php if (isLoggedIn()): ?>
                    <a href="views/dashboard.php" class="btn-hero">Launch MediArchive</a>
                    <?php else: ?>
                    <a href="#" class="btn-hero" data-bs-toggle="modal" data-bs-target="#registerModal">Launch MediArchive</a>
                    <?php endif; ?>
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

<?php if (!isLoggedIn()): ?>
<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 25px 60px rgba(10, 29, 71, 0.45);">
            <div class="modal-header border-0" style="padding: 32px 32px 16px;">
                <h5 class="modal-title fw-bold" id="loginModalLabel" style="color: var(--brand-primary);"><i class="bi bi-box-arrow-in-right me-2"></i>Login to MediArchive</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 16px 32px 32px;">
                <form id="loginForm" method="POST" action="views/login.php">
                    <?php echo SecurityManager::getCSRFField(); ?>
                    <div class="mb-3">
                        <label for="login_username" class="form-label fw-semibold">Username</label>
                        <input type="text" class="form-control" id="login_username" name="username" required autocomplete="username" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    <div class="mb-3">
                        <label for="login_password" class="form-label fw-semibold">Password</label>
                        <input type="password" class="form-control" id="login_password" name="password" required autocomplete="current-password" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold" style="padding: 14px; border-radius: 40px; background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-dark) 100%); border: none; box-shadow: 0 8px 20px rgba(15, 99, 214, 0.3);">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </button>
                </form>
                
                <?php if (!empty(GOOGLE_CLIENT_ID)): ?>
                <div style="display: flex; align-items: center; gap: 12px; color: #64748b; margin: 16px 0;">
                    <div style="height: 1px; flex: 1; background: #e2e8f0;"></div>
                    <span>or</span>
                    <div style="height: 1px; flex: 1; background: #e2e8f0;"></div>
                </div>
                <div id="g_id_onload"
                     data-client_id="<?php echo SecurityManager::escapeOutput(GOOGLE_CLIENT_ID); ?>"
                     data-callback="handleCredentialResponse"
                     data-auto_prompt="false">
                </div>
                <div class="d-flex justify-content-center mb-2">
                    <div class="g_id_signin"
                         data-type="standard"
                         data-size="large"
                         data-theme="outline"
                         data-text="signin_with"
                         data-shape="rectangular"
                         data-logo_alignment="left"
                         data-width="320">
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <small class="text-muted">Don't have an account? <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#registerModal" style="color: var(--brand-primary); font-weight: 600;">Register here</a></small>
                </div>
                <hr class="my-3">
                <p class="text-muted small mb-1">Demo Accounts:</p>
                <ul class="small text-muted mb-0">
                    <li>Clinic: <strong>dr.smith</strong> / password</li>
                    <li>Patient: <strong>alice.j</strong> / password</li>
                    <li>Admin: <strong>webadmin</strong> / password</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 25px 60px rgba(10, 29, 71, 0.45);">
            <div class="modal-header border-0" style="padding: 32px 32px 16px;">
                <h5 class="modal-title fw-bold" id="registerModalLabel" style="color: var(--brand-primary);"><i class="bi bi-person-plus me-2"></i>Create Your Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 16px 32px 32px; max-height: 70vh; overflow-y: auto;">
                <form id="registerForm" method="POST" action="views/register.php" enctype="multipart/form-data">
                    <?php echo SecurityManager::getCSRFField(); ?>
                    
                    <div class="mb-4">
                        <label class="form-label text-uppercase small fw-semibold text-muted">I am a</label>
                        <div class="d-flex flex-wrap gap-2">
                            <label class="role-pill active">
                                <input type="radio" name="role" value="patient" checked>
                                <span><i class="bi bi-person-heart"></i> Patient</span>
                            </label>
                            <label class="role-pill">
                                <input type="radio" name="role" value="clinic_admin">
                                <span><i class="bi bi-hospital"></i> Doctor / Clinic Admin</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" required style="border-radius: 12px; padding: 12px 16px;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" required style="border-radius: 12px; padding: 12px 16px;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required style="border-radius: 12px; padding: 12px 16px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" class="form-control" name="phone" placeholder="+63 900 000 0000" style="border-radius: 12px; padding: 12px 16px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Home Address</label>
                            <input type="text" class="form-control" name="home_address" style="border-radius: 12px; padding: 12px 16px;">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Profile Photo</label>
                            <input type="file" class="form-control" name="profile_photo" accept="image/*" style="border-radius: 12px; padding: 12px 16px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required style="border-radius: 12px; padding: 12px 16px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="confirm_password" required style="border-radius: 12px; padding: 12px 16px;">
                        </div>
                    </div>
                    
                    <!-- Patient Fields -->
                    <div id="patientFieldsModal" class="mt-4 p-3" style="background: #f8f9fa; border-radius: 12px;">
                        <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-person"></i> Patient Information</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth" style="border-radius: 12px; padding: 12px 16px;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender" style="border-radius: 12px; padding: 12px 16px;">
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" style="border-radius: 12px; padding: 12px 16px;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Clinic Admin Fields -->
                    <div id="clinicAdminFieldsModal" class="mt-4 p-3 d-none" style="background: #f8f9fa; border-radius: 12px;">
                        <h6 class="fw-semibold text-success mb-3"><i class="bi bi-activity"></i> Clinic Credentials</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Clinic / Practice Name</label>
                                <input type="text" class="form-control" name="clinic_name" style="border-radius: 12px; padding: 12px 16px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Medical License <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="medical_license" style="border-radius: 12px; padding: 12px 16px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Specialization <span class="text-danger">*</span></label>
                                <select class="form-select" name="specialization" style="border-radius: 12px; padding: 12px 16px;">
                                    <option value="">Select specialization</option>
                                    <option value="General Medicine">General Medicine</option>
                                    <option value="Cardiology">Cardiology</option>
                                    <option value="Neurology">Neurology</option>
                                    <option value="Pediatrics">Pediatrics</option>
                                    <option value="Orthopedics">Orthopedics</option>
                                    <option value="Dermatology">Dermatology</option>
                                    <option value="Psychiatry">Psychiatry</option>
                                    <option value="Oncology">Oncology</option>
                                    <option value="Gynecology">Gynecology</option>
                                    <option value="Emergency Medicine">Emergency Medicine</option>
                                    <option value="Internal Medicine">Internal Medicine</option>
                                    <option value="Surgery">Surgery</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Clinic Address</label>
                                <input type="text" class="form-control" name="clinic_address" style="border-radius: 12px; padding: 12px 16px;">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold mt-4" style="padding: 14px; border-radius: 40px; background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-dark) 100%); border: none; box-shadow: 0 8px 20px rgba(15, 99, 214, 0.3);">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                    <div class="text-center mt-3">
                        <small class="text-muted">Already have an account? <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal" style="color: var(--brand-primary); font-weight: 600;">Login here</a></small>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.role-pill {
    border: 1px solid #d0d7ff;
    border-radius: 999px;
    padding: 10px 18px;
    cursor: pointer;
    background: #f8faff;
    color: #20417c;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .2s ease;
}
.role-pill input { display: none; }
.role-pill.active {
    background: linear-gradient(135deg, #2150c9 0%, #1f9cd6 100%);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 8px 24px rgba(33, 80, 201, 0.35);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rolePills = document.querySelectorAll('#registerModal .role-pill');
    const patientSection = document.getElementById('patientFieldsModal');
    const clinicSection = document.getElementById('clinicAdminFieldsModal');
    
    function syncRole(value) {
        rolePills.forEach(p => p.classList.remove('active'));
        document.querySelectorAll('#registerModal .role-pill input').forEach(input => {
            if (input.value === value) {
                input.checked = true;
                input.parentElement.classList.add('active');
            }
        });
        patientSection.classList.toggle('d-none', value !== 'patient');
        clinicSection.classList.toggle('d-none', value !== 'clinic_admin');
    }
    
    rolePills.forEach(pill => {
        pill.addEventListener('click', () => {
            const val = pill.querySelector('input').value;
            syncRole(val);
        });
    });
    
    document.querySelectorAll('#registerModal .role-pill input').forEach(input => {
        input.addEventListener('change', () => syncRole(input.value));
    });
});

// Google Sign-In Handler
<?php if (!empty(GOOGLE_CLIENT_ID)): ?>
window.handleCredentialResponse = function(response) {
    fetch('api/google_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ credential: response.credential })
    }).then(function(r){ return r.json(); })
    .then(function(res){
        if (res.status === 'success') {
            if (res.needs_profile) {
                window.location.href = 'views/complete_profile.php';
            } else {
                window.location.href = 'views/dashboard.php';
            }
        } else {
            alert(res.message || 'Google sign-in failed');
        }
    }).catch(function(){ alert('Network error during Google sign-in'); });
};
<?php endif; ?>
</script>
<?php endif; ?>

<?php if (!empty(GOOGLE_CLIENT_ID)): ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>