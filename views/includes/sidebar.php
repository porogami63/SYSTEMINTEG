<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>
<nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="d-flex align-items-center gap-2 px-3 mb-2">
            <?php if ($role === 'web_admin'): ?>
            <span class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width:30px;height:30px;color:#000;"><i class="bi bi-shield-check"></i></span>
            <h5 class="text-white mb-0"><strong>MediArchive</strong></h5>
            <?php elseif ($role === 'clinic_admin'): ?>
            <span class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:30px;height:30px;color:#2e7d32;"><i class="bi bi-heart-pulse-fill"></i></span>
            <h5 class="text-white mb-0"><strong>MediArchive</strong></h5>
            <?php else: ?>
            <span class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:30px;height:30px;color:#1976d2;"><i class="bi bi-heart-pulse-fill"></i></span>
            <h5 class="text-white mb-0"><strong>MediArchive</strong></h5>
            <?php endif; ?>
        </div>
        <?php if (isset($_SESSION['full_name'])): ?>
        <div class="d-flex align-items-center justify-content-between px-3 py-2 text-white">
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($_SESSION['profile_photo'])): ?>
                    <img src="../<?php echo htmlspecialchars($_SESSION['profile_photo']); ?>" alt="Profile" class="rounded-circle" style="width:28px;height:28px;object-fit:cover;">
                <?php endif; ?>
                <div class="small d-flex align-items-center gap-2">
                    <span>Hello, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <?php if (isClinicAdmin()): ?>
                        <?php 
                        // fetch current availability
                        try {
                            $row = DB()->fetch("SELECT is_available FROM clinics WHERE user_id = ?", [$_SESSION['user_id']]);
                            $isAvail = $row ? intval($row['is_available']) : 0;
                        } catch (Exception $e) { $isAvail = 0; }
                        ?>
                        <span id="availIndicator" class="d-inline-block rounded-circle" style="width:8px;height:8px;background: <?php echo $isAvail ? '#2ecc71' : '#bdc3c7'; ?>;"></span>
                    <?php endif; ?>
                </div>
            </div>
			<!-- Notification Bell -->
			<div class="position-relative">
				<button class="btn btn-link text-white p-0" id="notificationBell" style="text-decoration:none;" onclick="toggleNotifications()">
					<i class="bi bi-bell fs-5"></i>
					<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display:none;">
						0
					</span>
				</button>
				<div id="notificationDropdown" class="card shadow position-absolute end-0 mt-2" style="width:320px; display:none; z-index:1050;">
					<div class="card-header d-flex align-items-center justify-content-between py-2">
						<strong>Notifications</strong>
						<button class="btn btn-sm btn-outline-secondary" type="button" onclick="markAllNotificationsRead()">Mark all read</button>
					</div>
					<div id="notificationList" class="list-group list-group-flush" style="max-height:360px; overflow:auto;"></div>
					<div class="card-footer text-center py-2">
						<a href="notification_settings.php" class="small">Settings</a>
					</div>
				</div>
			</div>
        </div>
        <?php endif; ?>
        <hr class="text-white">
        <ul class="nav flex-column">
            <?php if (isClinicAdmin()): ?>
            <li class="nav-item px-3 mb-2">
                <div class="form-check form-switch text-white">
                    <?php 
                    if (!isset($isAvail)) {
                        try { $row = DB()->fetch("SELECT is_available FROM clinics WHERE user_id = ?", [$_SESSION['user_id']]); $isAvail = $row ? intval($row['is_available']) : 0; } catch (Exception $e) { $isAvail = 0; }
                    }
                    ?>
                    <input class="form-check-input" type="checkbox" role="switch" id="availabilitySwitch" <?php echo $isAvail ? 'checked' : ''; ?> onclick="setAvailability(this.checked)">
                    <label class="form-check-label ms-2" for="availabilitySwitch">Available</label>
                </div>
            </li>
            <?php endif; ?>
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
                <a class="nav-link <?php echo $current_page === 'clinic_appointments.php' ? 'active' : ''; ?>" href="clinic_appointments.php">
                    <i class="bi bi-calendar-event"></i> Appointments
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
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'chat.php' ? 'active' : ''; ?>" href="chat.php">
                    <i class="bi bi-chat-dots"></i> Messages
                </a>
            </li>
            <?php elseif (isPatient()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'my_certificates.php' ? 'active' : ''; ?>" href="my_certificates.php">
                    <i class="bi bi-file-earmark-medical"></i> My Certificates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'my_appointments.php' ? 'active' : ''; ?>" href="my_appointments.php">
                    <i class="bi bi-calendar"></i> My Appointments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'request_appointment.php' ? 'active' : ''; ?>" href="request_appointment.php">
                    <i class="bi bi-calendar-plus"></i> Request Appointment
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'chat.php' ? 'active' : ''; ?>" href="chat.php">
                    <i class="bi bi-chat-dots"></i> Messages
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (isWebAdmin()): ?>
            <hr class="text-white">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'all_certificates.php' ? 'active' : ''; ?>" href="all_certificates.php">
                    <i class="bi bi-files"></i> All Certificates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'all_appointments.php' ? 'active' : ''; ?>" href="all_appointments.php">
                    <i class="bi bi-calendar-event"></i> All Appointments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'audit_logs.php' ? 'active' : ''; ?>" href="audit_logs.php">
                    <i class="bi bi-shield-check"></i> Audit Logs
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($current_page, ['profile.php', 'edit_profile.php']) ? 'active' : ''; ?>" href="profile.php">
                    <i class="bi bi-person"></i> Profile
                </a>
            </li>
            <?php if (isPatient()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'patient_history.php' ? 'active' : ''; ?>" href="patient_history.php">
                    <i class="bi bi-clock-history"></i> Medical History
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'notification_settings.php' ? 'active' : ''; ?>" href="notification_settings.php">
                    <i class="bi bi-bell"></i> Notifications
                </a>
            </li>
            <?php if (isPatient()): ?>
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

<?php if (isClinicAdmin()): ?>
<script>
function setAvailability(isOn){
  const form = new FormData();
  form.append('is_available', isOn ? 1 : 0);
  fetch('../api/availability.php', { method: 'POST', body: form })
    .then(r => r.json())
    .then(j => {
      const ind = document.getElementById('availIndicator');
      if (ind) ind.style.background = (isOn ? '#2ecc71' : '#bdc3c7');
    })
    .catch(() => {});
}
</script>
<?php endif; ?>

<script>
// Notifications UI (patients and clinic admins)
let notifOpen = false;
function toggleNotifications(){
	const dd = document.getElementById('notificationDropdown');
	if (!dd) return;
	notifOpen = !notifOpen;
	dd.style.display = notifOpen ? 'block' : 'none';
	if (notifOpen) { loadNotifications(); }
}

function closeNotificationsOnOutsideClick(e){
	const dd = document.getElementById('notificationDropdown');
	const bell = document.getElementById('notificationBell');
	if (!dd || !bell) return;
	if (notifOpen && !dd.contains(e.target) && !bell.contains(e.target)) {
		dd.style.display = 'none';
		notifOpen = false;
	}
}
document.addEventListener('click', closeNotificationsOnOutsideClick);

function refreshNotificationCount(){
	fetch('../api/notifications.php?action=count')
		.then(r => r.json())
		.then(j => {
			const badge = document.getElementById('notificationBadge');
			if (!badge) return;
			const c = parseInt(j.count || 0, 10);
			if (c > 0) {
				badge.style.display = 'inline-block';
				badge.textContent = c;
			} else {
				badge.style.display = 'none';
			}
		})
		.catch(()=>{});
}

function loadNotifications(){
	const list = document.getElementById('notificationList');
	if (list) list.innerHTML = '<div class="text-center py-3 small text-muted">Loading...</div>';
	fetch('../api/notifications.php?limit=10')
		.then(r => r.json())
		.then(j => {
			if (!list) return;
			const items = (j.notifications || []).map(n => {
				const linkStart = n.link ? '<a href="'+ n.link +'" class="list-group-item list-group-item-action">' : '<div class="list-group-item">';
				const linkEnd = n.link ? '</a>' : '</div>';
				return linkStart +
					'<div class="d-flex justify-content-between align-items-start">'
					+ '<div>'
					+ '<div class="fw-semibold">' + escapeHtml(n.title) + '</div>'
					+ '<div class="small text-muted">' + escapeHtml(n.created_at) + '</div>'
					+ '</div>'
					+ (n.is_read ? '' : '<span class="badge bg-primary">New</span>')
					+ '</div>'
					+ '<div class="small mt-1">' + escapeHtml(n.message) + '</div>'
				+ linkEnd;
			}).join('');
			list.innerHTML = items || '<div class="text-center py-3 small text-muted">No notifications</div>';
		})
		.catch(()=>{ if (list) list.innerHTML = '<div class="text-center py-3 small text-muted">Failed to load</div>'; });
	// also refresh count
	setTimeout(refreshNotificationCount, 250);
}

function markAllNotificationsRead(){
	const form = new FormData();
	fetch('../api/notifications.php?action=mark_read', { method: 'POST', body: form })
		.then(r => r.json())
		.then(() => { refreshNotificationCount(); loadNotifications(); })
		.catch(()=>{});
}

function escapeHtml(str){
	if (str === null || str === undefined) return '';
	return String(str)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

// initial and periodic refresh
document.addEventListener('DOMContentLoaded', function(){
	refreshNotificationCount();
	setInterval(refreshNotificationCount, 30000);
});
</script>

