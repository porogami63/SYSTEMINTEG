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
    --primary-green: #2e7d32;
    --dark-green: #1b5e20;
    --light-green: #c8e6c9;
    --bg-light: #f8f9fa;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--bg-light);
}

.navbar {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 1rem 0;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    color: white !important;
}

.nav-link {
    color: white !important;
    font-weight: 500;
    transition: all 0.3s;
}

.nav-link:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 100px 0;
    margin-bottom: 60px;
}

.hero-section h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.hero-section .lead {
    font-size: 1.3rem;
    margin-bottom: 30px;
    opacity: 0.95;
}

.btn-hero {
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.btn-hero:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

.features-section {
    padding: 80px 0;
}

.feature-card {
    background: white;
    border-radius: 15px;
    padding: 40px;
    height: 100%;
    transition: all 0.3s;
    border: 1px solid #e0e0e0;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.feature-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    color: white;
    font-size: 30px;
}

.feature-card h4 {
    color: var(--primary-green);
    font-weight: 600;
    margin-bottom: 15px;
}

.feature-card p {
    color: #666;
    line-height: 1.6;
}

.footer {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    color: white;
    padding: 30px 0;
    margin-top: 80px;
}

@media (max-width: 768px) {
    .hero-section h1 {
        font-size: 2.5rem;
    }
    
    .hero-section {
        padding: 60px 0;
    }
}
</style>
</head>
<body>
<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-heart-pulse-fill"></i> MediArchive
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="views/login.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="views/register.php">Register</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold">Digital Medical Certificate System</h1>
                <p class="lead">Securely issue, access, and verify medical certificates with advanced QR code technology</p>
                <a href="views/register.php" class="btn btn-light btn-hero">Get Started</a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section bg-white">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-12">
                <h2 class="display-5 fw-bold mb-4" style="color: var(--primary-green);">System Features</h2>
                <p class="lead text-muted">Comprehensive digital medical certificate management</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h4>Secure Storage</h4>
                    <p>All medical certificates are encrypted and securely stored with industry-standard security protocols</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-qr-code-scan"></i>
                    </div>
                    <h4>QR Code Verification</h4>
                    <p>Instant certificate verification through QR code scanning with real-time validation system</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-file-earmark-medical"></i>
                    </div>
                    <h4>Digital Certificates</h4>
                    <p>Issue and manage digital medical certificates with automated ID generation and tracking</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-phone"></i>
                    </div>
                    <h4>Mobile Access</h4>
                    <p>Access your medical certificates anytime, anywhere with our responsive mobile-friendly interface</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-broadcast"></i>
                    </div>
                    <h4>API Integration</h4>
                    <p>SOAP and JSON APIs for seamless integration with healthcare systems and mobile applications</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h4>Track Records</h4>
                    <p>Complete audit trail and verification logs for all certificate access and validation activities</p>
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