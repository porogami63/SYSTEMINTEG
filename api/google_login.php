<?php
/**
 * Google Sign-In endpoint: verifies ID token and creates/logs-in a user.
 */
require_once '../config.php';

header('Content-Type: application/json');

try {
	$raw = file_get_contents('php://input');
	$data = json_decode($raw, true);
	$idToken = $data['credential'] ?? '';

	if (empty($idToken)) {
		echo JsonHelper::encode(['status' => 'error', 'message' => 'Missing credential']);
		exit;
	}

	// Verify ID token via Google tokeninfo (server-side verification)
	$verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
	$ch = curl_init($verifyUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$resp = curl_exec($ch);
	$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($http !== 200 || !$resp) {
		echo JsonHelper::encode(['status' => 'error', 'message' => 'Failed to verify token']);
		exit;
	}

	$payload = json_decode($resp, true);
	$aud = $payload['aud'] ?? '';
	$googleId = $payload['sub'] ?? '';
	$email = $payload['email'] ?? '';
	$name = $payload['name'] ?? ($payload['given_name'] ?? 'Google User');
	$pictureUrl = $payload['picture'] ?? '';

	// Optional: enforce Client ID if configured
	if (!empty(GOOGLE_CLIENT_ID) && $aud !== GOOGLE_CLIENT_ID) {
		echo JsonHelper::encode(['status' => 'error', 'message' => 'Client ID mismatch']);
		exit;
	}

	if (empty($googleId) || empty($email)) {
		echo JsonHelper::encode(['status' => 'error', 'message' => 'Invalid Google profile']);
		exit;
	}

	$db = Database::getInstance();

	// Try to find user by email
	$user = $db->fetch("SELECT id, username, full_name, role, profile_photo FROM users WHERE email = ? LIMIT 1", [$email]);

	if (!$user) {
		// Create new user (default role: patient)
		$username = $email;
		$fullName = $name;
		$role = 'patient';
		$randomPassword = bin2hex(random_bytes(16));
		$hashed = password_hash($randomPassword, PASSWORD_BCRYPT);

		$db->execute("INSERT INTO users (username, email, password, full_name, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
			[$username, $email, $hashed, $fullName, $role]);
		$userId = $db->lastInsertId();
		$user = ['id' => $userId, 'username' => $username, 'full_name' => $fullName, 'role' => $role];

		// Ensure patient profile exists for new Google sign-ups
		$patientCode = 'PAT-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);
		$db->execute(
			"INSERT INTO patients (user_id, patient_code) VALUES (?, ?)",
			[$userId, $patientCode]
		);

		// Attempt to persist Google profile picture locally
		if (!empty($pictureUrl)) {
			try {
				$imgData = @file_get_contents($pictureUrl);
				if ($imgData !== false) {
					$filename = 'avatar_google_' . $userId . '.jpg';
					$path = UPLOAD_DIR . $filename;
					file_put_contents($path, $imgData);
					$relative = 'uploads/' . $filename;
					$db->execute("UPDATE users SET profile_photo = ? WHERE id = ?", [$relative, $userId]);
					$user['profile_photo'] = $relative;
				}
			} catch (Exception $e) {
				// Ignore failures silently
			}
		}
	} else {
		// Optionally update email/username coherence
		if (empty($user['username'])) {
			$db->execute("UPDATE users SET username = ? WHERE id = ?", [$email, $user['id']]);
		}
		// Backfill profile photo if missing
		if (empty($user['profile_photo']) && !empty($pictureUrl)) {
			try {
				$imgData = @file_get_contents($pictureUrl);
				if ($imgData !== false) {
					$filename = 'avatar_google_' . $user['id'] . '.jpg';
					$path = UPLOAD_DIR . $filename;
					file_put_contents($path, $imgData);
					$relative = 'uploads/' . $filename;
					$db->execute("UPDATE users SET profile_photo = ? WHERE id = ?", [$relative, $user['id']]);
					$user['profile_photo'] = $relative;
				}
			} catch (Exception $e) {
				// Ignore failures silently
			}
		}
	}

	// Log user in
	SessionManager::createSession($user['id'], $user['username'] ?? $email, $user['full_name'] ?? $name, $user['role'] ?? 'patient');

	if (!empty($user['profile_photo'])) {
		$_SESSION['profile_photo'] = $user['profile_photo'];
	}

	$role = $user['role'] ?? 'patient';
	$needsProfile = false;
	if ($role === 'clinic_admin') {
		$clinic = $db->fetch("SELECT clinic_name, medical_license, specialization, address, contact_phone FROM clinics WHERE user_id = ? LIMIT 1", [$user['id']]);
		if (!$clinic || empty($clinic['clinic_name']) || empty($clinic['medical_license']) || empty($clinic['specialization']) || empty($clinic['address']) || empty($clinic['contact_phone'])) {
			$needsProfile = true;
		}
	} else {
		$p = $db->fetch("SELECT date_of_birth, gender, address FROM patients WHERE user_id = ? LIMIT 1", [$user['id']]);
		if (!$p || empty($p['date_of_birth']) || empty($p['gender']) || empty($p['address'])) {
			$needsProfile = true;
		}
	}

	echo JsonHelper::encode(['status' => 'success', 'needs_profile' => $needsProfile]);
} catch (Exception $e) {
	error_log('Google login error: ' . $e->getMessage());
	echo JsonHelper::encode(['status' => 'error', 'message' => 'Server error']);
}

?>

