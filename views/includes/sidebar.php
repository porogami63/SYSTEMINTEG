<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="d-flex align-items-center gap-2 px-3 mb-2">
            <span class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:30px;height:30px;color:#2e7d32;"><i class="bi bi-heart-pulse-fill"></i></span>
            <h5 class="text-white mb-0"><strong>MediArchive</strong></h5>
        </div>
        <?php if (isset($_SESSION['full_name'])): ?>
        <div class="d-flex align-items-center justify-content-between px-3 py-2 text-white">
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($_SESSION['profile_photo'])): ?>
                    <img src="../<?php echo htmlspecialchars($_SESSION['profile_photo']); ?>" alt="Profile" class="rounded-circle" style="width:28px;height:28px;object-fit:cover;">
                <?php endif; ?>
                <div class="small">Hello, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            </div>
            <!-- Notification Bell -->
            <div class="position-relative">
                <button class="btn btn-link text-white p-0" id="notificationBell" style="text-decoration:none;" onclick="toggleNotifications()">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display:none;">
                        0
                    </span>
                </button>
            </div>
        </div>
        <?php endif; ?>
        <hr class="text-white">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <?php if (isClinicAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'certificates.php' ? 'active' : ''; ?>" href="certificates.php">
                    <i class="bi bi-files"></i> Certificates & Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'patients.php' ? 'active' : ''; ?>" href="patients.php">
                    <i class="bi bi-people"></i> Patients
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'analytics.php' ? 'active' : ''; ?>" href="analytics.php">
                    <i class="bi bi-graph-up"></i> Analytics
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'my_certificates.php' ? 'active' : ''; ?>" href="my_certificates.php">
                    <i class="bi bi-file-earmark-medical"></i> My Certificates
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($current_page, ['profile.php', 'edit_profile.php']) ? 'active' : ''; ?>" href="profile.php">
                    <i class="bi bi-person"></i> Profile
                </a>
            </li>
            <?php if (!isClinicAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'request_certificate.php' ? 'active' : ''; ?>" href="request_certificate.php">
                    <i class="bi bi-file-earmark-plus"></i> Request Certificate
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'find_doctors.php' ? 'active' : ''; ?>" href="find_doctors.php">
                    <i class="bi bi-search"></i> Find Doctors
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

