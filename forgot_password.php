<?php
require_once 'includes/config.php';

$error = '';
$success = '';
$step = 1;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_email'])) {
        $email = trim($_POST['email']);
        if (!empty($email)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $step = 2; // Move to reset password step
                } else {
                    $error = "No account found with this email address.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Please enter your email.";
        }
    } elseif (isset($_POST['reset_password'])) {
        $email = trim($_POST['email']);
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);
        
        if (!empty($new_password) && !empty($confirm_password)) {
            if ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
                $step = 2;
            } else {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $stmt->execute([$hashed_password, $email]);
                    
                    // Create notification for reset
                    $stmt_user = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt_user->execute([$email]);
                    $usr = $stmt_user->fetch();
                    if ($usr) {
                        addNotification($pdo, $usr['id'], "Your account password was successfully reset.");
                    }
                    
                    $success = "Your password has been reset successfully! You can now log in.";
                    $step = 3;
                } catch (PDOException $e) {
                    $error = "Failed to update password: " . $e->getMessage();
                    $step = 2;
                }
            }
        } else {
            $error = "Please fill in all fields.";
            $step = 2;
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 500px; display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 180px);">
    <div class="card" style="width: 100%;">
        <div class="card-header" style="text-align: center; justify-content: center;">
            <h2 class="card-title">Reset Password</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($step === 1): ?>
            <!-- Step 1: Verify Email -->
            <form method="POST" action="forgot_password.php">
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="email">Enter Registered Email</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required>
                    <small style="color: var(--text-secondary); margin-top: 0.5rem; display: block;">We will verify this account email in our database.</small>
                </div>
                <button type="submit" name="check_email" class="btn btn-primary" style="width: 100%; padding: 0.8rem;">Verify Email</button>
            </form>
        <?php elseif ($step === 2): ?>
            <!-- Step 2: New Password Form -->
            <form method="POST" action="forgot_password.php">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="form-group">
                    <label>Account Email</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled style="background: rgba(255,255,255,0.01);">
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" name="reset_password" class="btn btn-primary" style="width: 100%; padding: 0.8rem;">Update Password</button>
            </form>
        <?php elseif ($step === 3): ?>
            <!-- Step 3: Success and redirection link -->
            <div style="text-align: center; margin-top: 1rem;">
                <a href="login.php" class="btn btn-primary" style="padding: 0.75rem 2rem;">Go to Login</a>
            </div>
        <?php endif; ?>
        
        <?php if ($step !== 3): ?>
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
                Remember your password? <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Sign in here</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
