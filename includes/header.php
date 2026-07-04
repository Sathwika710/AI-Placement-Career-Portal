<?php
require_once 'includes/config.php';

// Fetch notifications if logged in
$unread_notifications_count = 0;
$user_notifications = [];
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$user_id]);
        $user_notifications = $stmt->fetchAll();
        
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt_count->execute([$user_id]);
        $unread_notifications_count = $stmt_count->fetchColumn();
    } catch (PDOException $e) {
        // Suppress DB errors in layout header
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Placement & Career Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="description" content="AI-Powered Placement and Career Portal for Students and Recruiters.">
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">🚀 CareerAI</a>
            
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if (getRole() === 'student'): ?>
                            <li><a href="student_dashboard.php">Dashboard</a></li>
                        <?php elseif (getRole() === 'recruiter'): ?>
                            <li><a href="recruiter_dashboard.php">Dashboard</a></li>
                        <?php elseif (getRole() === 'admin'): ?>
                            <li><a href="admin_dashboard.php">Admin Panel</a></li>
                        <?php endif; ?>
                        
                        <!-- Notifications Bell -->
                        <li class="notification-bell-container" id="notification-bell">
                            🔔
                            <?php if ($unread_notifications_count > 0): ?>
                                <span class="notification-badge"></span>
                            <?php endif; ?>
                            
                            <div class="notification-dropdown" id="notification-dropdown">
                                <h4 style="margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">Notifications</h4>
                                <?php if (empty($user_notifications)): ?>
                                    <div class="notification-item" style="color: var(--text-secondary);">No notifications</div>
                                <?php else: ?>
                                    <?php foreach ($user_notifications as $notif): ?>
                                        <div class="notification-item" style="<?php echo !$notif['is_read'] ? 'font-weight: 600;' : ''; ?>">
                                            <?php echo htmlspecialchars($notif['message']); ?>
                                            <span style="display: block; font-size: 0.7rem; color: var(--text-secondary); margin-top: 0.1rem;">
                                                <?php echo date('M d, H:i', strtotime($notif['created_at'])); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </li>

                        <!-- User Profile Name & Logout -->
                        <li style="color: var(--text-primary); font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 0.75rem;">
                            <span>Hi, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Guest'); ?> <small>(<?php echo ucfirst($_SESSION['role'] ?? ''); ?>)</small></span>
                            <a href="logout.php" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Logout</a>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn btn-outline" style="padding: 0.4rem 1rem;">Login</a></li>
                        <li><a href="register.php" class="btn btn-primary" style="padding: 0.4rem 1rem;">Register</a></li>
                    <?php endif; ?>
                    <!-- Quick Switcher -->
                    <li style="position: relative;">
                        <button class="btn btn-secondary" id="role-switcher-btn" onclick="event.stopPropagation(); document.getElementById('role-switcher-dropdown').classList.toggle('active')" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; border-color: var(--primary); display: flex; align-items: center; gap: 0.25rem;">
                            🔄 Switch Portal
                        </button>
                        <div class="notification-dropdown" id="role-switcher-dropdown" style="width: 180px; padding: 0.75rem; text-align: left;">
                            <h4 style="margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem; font-size: 0.8rem; text-transform: uppercase; color: var(--text-secondary);">Select Portal</h4>
                            <a href="quick_login.php?role=student" style="display:block; padding:0.4rem 0.25rem; text-decoration:none; color:var(--text-primary); font-size:0.85rem; font-weight:500;">👨‍🎓 Student Portal</a>
                            <a href="quick_login.php?role=recruiter" style="display:block; padding:0.4rem 0.25rem; text-decoration:none; color:var(--text-primary); font-size:0.85rem; font-weight:500;">💼 Recruiter Portal</a>
                            <a href="quick_login.php?role=admin" style="display:block; padding:0.4rem 0.25rem; text-decoration:none; color:var(--text-primary); font-size:0.85rem; font-weight:500;">🛡️ Admin Portal</a>
                        </div>
                    </li>
                    
                    <li>
                        <button class="theme-toggle" id="theme-toggle-btn" aria-label="Toggle Theme">☀️</button>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
