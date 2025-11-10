<?php
require_once '../config.php';

// Use SessionManager for secure logout
SessionManager::destroySession();

// Redirect to login
redirect('login.php');
?>

