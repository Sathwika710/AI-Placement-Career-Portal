<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    if (getRole() === 'student') redirect('student_dashboard.php');
    if (getRole() === 'recruiter') redirect('recruiter_dashboard.php');
    if (getRole() === 'admin') redirect('admin_dashboard.php');
}

$error = '';
$selected_role = $_SESSION['selected_role'] ?? '';
if (isset($_GET['role']) && in_array($_GET['role'], ['student', 'recruiter', 'admin'])) {
    $selected_role = $_GET['role'];
    $_SESSION['selected_role'] = $selected_role;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                
                // Clear temporary login role choice on success
                unset($_SESSION['selected_role']);
                
                addNotification($pdo, $user['id'], "Welcome back to CareerAI!");
                
                if ($user['role'] === 'student') redirect('student_dashboard.php');
                if ($user['role'] === 'recruiter') redirect('recruiter_dashboard.php');
                if ($user['role'] === 'admin') redirect('admin_dashboard.php');
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 500px; display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 180px);">
    <div class="card" style="width: 100%;">
        <div class="card-header" style="text-align: center; justify-content: center;">
            <h2 class="card-title">
                <?php 
                if ($selected_role === 'admin') {
                    echo '🛡️ Admin Portal';
                } elseif ($selected_role === 'student') {
                    echo '👨‍🎓 Student Portal';
                } elseif ($selected_role === 'recruiter') {
                    echo '💼 Recruiter Portal';
                } else {
                    echo 'Sign In';
                }
                ?>
            </h2>
        </div>
        
        <?php 
        if (isset($_SESSION['login_hint'])) {
            echo '<div class="alert alert-warning">ℹ️ ' . htmlspecialchars($_SESSION['login_hint']) . '</div>';
            unset($_SESSION['login_hint']);
        }
        ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label for="password" style="margin-bottom: 0;">Password</label>
                    <a href="forgot_password.php" style="color: var(--primary); text-decoration: none; font-size: 0.8rem; font-weight: 600;">Forgot Password?</a>
                </div>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem;">Login</button>
        </form>
        
        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
            Don't have an account? <a href="register.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Register here</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
