<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'ai_placement_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

// Attempt Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Utility Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function getRole() {
    return $_SESSION['role'] ?? null;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function checkRole($allowed_roles) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    if (!in_array(getRole(), (array)$allowed_roles)) {
        redirect('index.php');
    }
}

function addNotification($pdo, $user_id, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$user_id, $message]);
    } catch (PDOException $e) {
        // Silently ignore or log notification failures
    }
}

// Global base path
define('BASE_URL', '/AI-Placement-Career-Portal/');
?>
