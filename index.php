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
    --primary-blue: #1565c0;
    --dark-blue: #0d47a1;
    --light-blue: #e3f2fd;
    --accent-teal: #00897b;
    --bg-light: #f5f7fa;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--bg-light);
}

.navbar {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
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
    background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
    background-image: 
        linear-gradient(135deg, rgba(21, 101, 192, 0.95) 0%, rgba(13, 71, 161, 0.95) 100%),
        url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
    color: white;
    padding: 120px 0;
    margin-bottom: 0;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    animation: float 20s infinite ease-in-out;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(180deg); }
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
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-teal) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    color: white;
    font-size: 30px;
}

.feature-card h4 {
    color: var(--primary-blue);
    font-weight: 600;
    margin-bottom: 15px;
}

.feature-card p {
    color: #666;
    line-height: 1.6;
}

.footer {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
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
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold">Digital Medical Certificate System</h1>
                <p class="lead">Securely issue, access, and verify medical certificates with advanced QR code technology</p>
                <a href="<?php echo isLoggedIn() ? 'views/dashboard.php' : 'views/register.php'; ?>" class="btn btn-light btn-hero">Get Started</a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section bg-white">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-12">
                <h2 class="display-5 fw-bold mb-4" style="color: var(--primary-blue);">System Features</h2>
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