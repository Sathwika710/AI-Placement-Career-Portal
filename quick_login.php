<?php
require_once 'includes/config.php';

// Log out current user
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Start fresh session to pass hints
session_start();

$role = $_GET['role'] ?? '';
if (in_array($role, ['student', 'recruiter', 'admin'])) {
    $_SESSION['login_hint'] = "Please enter your ID and password to access the " . ucfirst($role) . " Portal.";
    $_SESSION['selected_role'] = $role;
}

// Redirect to login page to prompt for credentials
redirect('login.php?role=' . urlencode($role));
?>
