<?php
require_once 'includes/config.php';
checkRole('student');

$student_id = $_SESSION['user_id'];
$msg = $_SESSION['msg'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['msg'], $_SESSION['error']);

// Handle Job Application
if (isset($_POST['apply_job'])) {
    $job_id = intval($_POST['job_id']);
    
    // Check if student has uploaded a resume first
    try {
        $stmt_profile = $pdo->prepare("SELECT resume_path FROM students_profile WHERE user_id = ?");
        $stmt_profile->execute([$student_id]);
        $profile = $stmt_profile->fetch();
        
        if (empty($profile['resume_path'])) {
            $error = "Please upload your resume before applying for jobs.";
        } else {
            // Apply for the job
            $stmt_apply = $pdo->prepare("INSERT INTO applications (job_id, student_id, status) VALUES (?, ?, 'applied')");
            $stmt_apply->execute([$job_id, $student_id]);
            
            // Create notification for student
            addNotification($pdo, $student_id, "You applied successfully to a new job post.");
            
            // Notify recruiter
            $stmt_rec = $pdo->prepare("SELECT recruiter_id, title FROM jobs WHERE id = ?");
            $stmt_rec->execute([$job_id]);
            $job = $stmt_rec->fetch();
            if ($job) {
                addNotification($pdo, $job['recruiter_id'], "A new candidate applied for your role: " . $job['title']);
            }
            
            $msg = "Application submitted successfully!";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "You have already applied to this job.";
        } else {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Delete Resume
if (isset($_POST['delete_resume'])) {
    try {
        $stmt_profile = $pdo->prepare("SELECT resume_path FROM students_profile WHERE user_id = ?");
        $stmt_profile->execute([$student_id]);
        $prof = $stmt_profile->fetch();
        
        if ($prof && !empty($prof['resume_path']) && file_exists($prof['resume_path'])) {
            unlink($prof['resume_path']);
        }
        
        $stmt_del = $pdo->prepare("
            UPDATE students_profile 
            SET resume_path = NULL, skills = NULL, ai_score = 0, ai_feedback = NULL, phone = NULL 
            WHERE user_id = ?
        ");
        $stmt_del->execute([$student_id]);
        
        addNotification($pdo, $student_id, "Your resume was deleted successfully.");
        $msg = "Resume deleted successfully.";
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Fetch Profile details
$stmt_profile = $pdo->prepare("SELECT * FROM students_profile WHERE user_id = ?");
$stmt_profile->execute([$student_id]);
$profile = $stmt_profile->fetch();

// Fetch applied jobs
$stmt_apps = $pdo->prepare("
    SELECT a.status, a.applied_at, j.title, j.company, j.location 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE a.student_id = ? 
    ORDER BY a.applied_at DESC
");
$stmt_apps->execute([$student_id]);
$applied_jobs = $stmt_apps->fetchAll();

// Fetch scheduled interviews
$stmt_int = $pdo->prepare("
    SELECT i.scheduled_time, i.mode, i.link, i.notes, j.title, j.company 
    FROM interviews i 
    JOIN applications a ON i.application_id = a.id 
    JOIN jobs j ON a.job_id = j.id 
    WHERE a.student_id = ? 
    ORDER BY i.scheduled_time ASC
");
$stmt_int->execute([$student_id]);
$interviews = $stmt_int->fetchAll();

// Fetch all available jobs (not applied yet)
$stmt_jobs = $pdo->prepare("
    SELECT j.*, u.name as recruiter_name 
    FROM jobs j
    JOIN users u ON j.recruiter_id = u.id
    WHERE j.id NOT IN (SELECT job_id FROM applications WHERE student_id = ?)
    ORDER BY j.created_at DESC
");
$stmt_jobs->execute([$student_id]);
$available_jobs = $stmt_jobs->fetchAll();

include 'includes/header.php';
?>

<div class="container">
    <div class="grid grid-2" style="margin-bottom: 3rem; align-items: start;">
        <!-- Resume Upload & AI Analysis -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">AI Resume Profile</h3>
            </div>
            
            <?php if ($msg): ?>
                <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div id="upload-form-container" style="<?php echo !empty($profile['resume_path']) ? 'display: none;' : ''; ?> margin-bottom: 2rem;">
                <form action="analyze_resume.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="resume"><?php echo !empty($profile['resume_path']) ? 'Update Resume (PDF or TXT format only)' : 'Upload Resume (PDF or TXT format only)'; ?></label>
                        <input type="file" name="resume" id="resume" class="form-control" accept=".pdf,.txt" required style="padding: 0.5rem;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;"><?php echo !empty($profile['resume_path']) ? 'Re-Analyze & Update' : 'Upload & AI Analyze'; ?></button>
                </form>
            </div>
            
            <?php if (!empty($profile['resume_path'])): ?>
                <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                    <div style="display: flex; gap: 2rem; align-items: center; margin-bottom: 1.5rem;">
                        <div class="score-circle" style="--score: <?php echo $profile['ai_score']; ?>;">
                            <div class="score-circle-inner">
                                <span class="score-num"><?php echo $profile['ai_score']; ?>%</span>
                                <span style="font-size: 0.65rem; color: var(--text-secondary); text-transform: uppercase;">Score</span>
                            </div>
                        </div>
                        <div>
                            <p style="font-size: 0.95rem; font-weight: 600;">Uploaded Resume:</p>
                            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap;">
                                <a href="<?php echo htmlspecialchars($profile['resume_path']); ?>" target="_blank" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    📄 View Resume
                                </a>
                                <button onclick="const el = document.getElementById('upload-form-container'); el.style.display = el.style.display === 'none' ? 'block' : 'none';" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    🔄 Update Resume
                                </button>
                                <form method="POST" action="student_dashboard.php" onsubmit="return confirm('Are you sure you want to delete your resume? This will reset your AI score and parsed skills.');" style="display: inline;">
                                    <button type="submit" name="delete_resume" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; color: var(--danger); border-color: rgba(244,63,94,0.2);">
                                        ❌ Delete Resume
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <h4 style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Extracted Skills:</h4>
                        <div class="skills-tags">
                            <?php 
                            $skills = json_decode($profile['skills'] ?? '[]', true);
                            if (empty($skills)) {
                                echo "<span style='font-size:0.85rem; color:var(--text-secondary);'>None parsed.</span>";
                            } else {
                                foreach ($skills as $skill) {
                                    echo "<span class='skill-tag'>" . htmlspecialchars($skill) . "</span>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div>
                        <h4 style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">AI Feedback / Suggestions:</h4>
                        <ul style="padding-left: 1.25rem; font-size: 0.85rem; color: var(--text-secondary);">
                            <?php 
                            $feedback = json_decode($profile['ai_feedback'] ?? '[]', true);
                            if (empty($feedback)) {
                                echo "<li>No feedback available. Analyze your resume to generate feedback.</li>";
                            } else {
                                foreach ($feedback as $fb) {
                                    echo "<li style='margin-bottom: 0.25rem;'>" . htmlspecialchars($fb) . "</li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-secondary); padding: 2rem 0;">
                    <p style="font-size: 1.5rem; margin-bottom: 0.5rem;">📂</p>
                    <p>No resume uploaded yet. Upload a resume to enable AI analysis and matching.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Interviews Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Scheduled Interviews</h3>
            </div>
            <?php if (empty($interviews)): ?>
                <div style="text-align: center; color: var(--text-secondary); padding: 3rem 0;">
                    <p style="font-size: 1.5rem; margin-bottom: 0.5rem;">📅</p>
                    <p>No upcoming interviews scheduled yet.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Role / Company</th>
                                <th>Schedule Time</th>
                                <th>Mode / Link</th>
                                <th>Recruiter Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($interviews as $int): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($int['title']); ?></strong>
                                        <div style="font-size:0.75rem; color:var(--text-secondary);"><?php echo htmlspecialchars($int['company']); ?></div>
                                    </td>
                                    <td><?php echo date('M d, Y - H:i', strtotime($int['scheduled_time'])); ?></td>
                                    <td>
                                        <span class="badge badge-info" style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($int['mode']); ?></span>
                                        <?php if (!empty($int['link'])): ?>
                                            <div><a href="<?php echo htmlspecialchars($int['link']); ?>" target="_blank" style="color:var(--primary); font-size:0.8rem; text-decoration:none;">Join Interview 🔗</a></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.8rem; color: var(--text-secondary); max-width: 200px;"><?php echo htmlspecialchars($int['notes'] ?? 'None'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Job Postings -->
    <div class="card" style="margin-bottom: 3rem;">
        <div class="card-header">
            <h3 class="card-title">Browse Career Opportunities</h3>
        </div>
        
        <?php if (empty($available_jobs)): ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 2rem 0;">All caught up! No new job openings available right now.</p>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($available_jobs as $job): ?>
                    <div class="card" style="background: rgba(255,255,255,0.01); border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <h4 style="font-family: var(--font-outfit); font-size: 1.15rem; font-weight: 700;"><?php echo htmlspecialchars($job['title']); ?></h4>
                                <p style="color: var(--text-secondary); font-size: 0.85rem; font-weight: 600;"><?php echo htmlspecialchars($job['company']); ?> - <?php echo htmlspecialchars($job['location']); ?></p>
                            </div>
                            <span class="badge badge-success"><?php echo htmlspecialchars($job['salary']); ?></span>
                        </div>
                        
                        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem; line-height: 1.5; height: 75px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                            <?php echo htmlspecialchars($job['description']); ?>
                        </p>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <span style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; display: block; margin-bottom: 0.25rem;">Required Skills:</span>
                            <div class="skills-tags">
                                <?php 
                                $reqs = explode(',', $job['requirements']);
                                foreach ($reqs as $req) {
                                    if (trim($req)) {
                                        echo "<span class='skill-tag' style='background: rgba(14,165,233,0.08); border-color: rgba(14,165,233,0.15); font-size: 0.75rem;'>" . htmlspecialchars(trim($req)) . "</span>";
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        
                        <form method="POST" action="student_dashboard.php">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <button type="submit" name="apply_job" class="btn btn-primary" style="width: 100%; padding: 0.5rem;">Apply Now</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Applied Jobs Tracking -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">My Applications</h3>
        </div>
        
        <?php if (empty($applied_jobs)): ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 2rem 0;">You haven't applied for any jobs yet.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Job Role</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Date Applied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applied_jobs as $app): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($app['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($app['company']); ?></td>
                                <td><?php echo htmlspecialchars($app['location']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                <td>
                                    <?php 
                                    $status = $app['status'];
                                    $class = 'badge-info';
                                    if ($status === 'shortlisted' || $status === 'selected') $class = 'badge-success';
                                    if ($status === 'scheduled') $class = 'badge-info';
                                    if ($status === 'rejected') $class = 'badge-danger';
                                    if ($status === 'reviewed') $class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
