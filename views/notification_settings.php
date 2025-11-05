<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance();

// Supported categories
$categories = [
    'certificate_created' => 'Certificate Created',
    'expiry_warning' => 'Expiry Warning',
    'system_update' => 'System Updates',
];

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($categories as $key => $label) {
        $enabled = isset($_POST["enabled_{$key}"]) ? 1 : 0;
        $email = isset($_POST["email_{$key}"]) ? 1 : 0;
        $inApp = isset($_POST["inapp_{$key}"]) ? 1 : 0;

        // Upsert preference
        $db->execute(
            "INSERT INTO notification_preferences (user_id, category, enabled, email_notification, in_app_notification)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), email_notification = VALUES(email_notification), in_app_notification = VALUES(in_app_notification)",
            [$userId, $key, $enabled, $email, $inApp]
        );
    }

    $message = 'Notification preferences updated successfully.';
}

// Load current preferences
$rows = $db->fetchAll("SELECT category, enabled, email_notification, in_app_notification FROM notification_preferences WHERE user_id = ?", [$userId]);
$prefs = [];
foreach ($rows as $row) {
    $prefs[$row['category']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notification Settings - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
.sidebar { min-height: 100vh; background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%); }
.sidebar .nav-link { color: white; padding: 12px 20px; margin: 5px 0; }
.sidebar .nav-link.active { background: rgba(255,255,255,0.2); }
.main-content { padding: 30px; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h2 class="mb-0"><i class="bi bi-bell"></i> Notification Settings</h2>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <form method="post" class="card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted">Choose how you want to be notified for each category.</p>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-center">Enabled</th>
                                        <th class="text-center">Email</th>
                                        <th class="text-center">In-App</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($categories as $key => $label): 
                                    $p = $prefs[$key] ?? ['enabled' => 1, 'email_notification' => 1, 'in_app_notification' => 1];
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($label); ?></strong></td>
                                        <td class="text-center">
                                            <input type="checkbox" name="enabled_<?php echo $key; ?>" <?php echo ($p['enabled'] ?? 0) ? 'checked' : ''; ?> />
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="email_<?php echo $key; ?>" <?php echo ($p['email_notification'] ?? 0) ? 'checked' : ''; ?> />
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="inapp_<?php echo $key; ?>" <?php echo ($p['in_app_notification'] ?? 0) ? 'checked' : ''; ?> />
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Preferences</button>
                    </div>
                </form>

            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


