<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    if (getRole() === 'student') redirect('student_dashboard.php');
    if (getRole() === 'recruiter') redirect('recruiter_dashboard.php');
    if (getRole() === 'admin') redirect('admin_dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    
    if (!empty($name) && !empty($email) && !empty($password) && !empty($role)) {
        if ($role !== 'student' && $role !== 'recruiter') {
            $error = "Invalid role selected.";
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email address already registered.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $pdo->beginTransaction();
                    
                    $stmt_ins = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt_ins->execute([$name, $email, $hashed_password, $role]);
                    $user_id = $pdo->lastInsertId();
                    
                    if ($role === 'student') {
                        $stmt_prof = $pdo->prepare("INSERT INTO students_profile (user_id) VALUES (?)");
                        $stmt_prof->execute([$user_id]);
                    }
                    
                    $pdo->commit();
                    $success = "Registration successful! You can now log in.";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Database error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 550px; display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 180px);">
    <div class="card" style="width: 100%;">
        <div class="card-header" style="text-align: center; justify-content: center;">
            <h2 class="card-title">Create Account</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="name">Full Name / Company Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Aarav Sharma" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="aarav@domain.com" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="role">Register As</label>
                <select name="role" id="role" class="form-control" required style="appearance: none; background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 12 12%22%3E%3Cpath fill=%22%2394a3b8%22 d=%22M6 8.825L1.175 4 2.587 2.587 6 6l3.413-3.413L10.825 4z%22/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 10px;">
                    <option value="student">Student (Upload resume and apply to jobs)</option>
                    <option value="recruiter">Recruiter (Post jobs and search candidates)</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem;">Register</button>
        </form>
        
        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
            Already have an account? <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Sign in here</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
