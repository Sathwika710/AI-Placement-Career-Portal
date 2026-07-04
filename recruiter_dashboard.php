<?php
require_once 'includes/config.php';
checkRole('recruiter');

$recruiter_id = $_SESSION['user_id'];
$msg = '';
$error = '';

// Handle Job Posting
if (isset($_POST['post_job'])) {
    $title = trim($_POST['title']);
    $company = trim($_POST['company']);
    $location = trim($_POST['location']);
    $salary = trim($_POST['salary']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']); // comma-separated skills
    
    if (!empty($title) && !empty($company) && !empty($description)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO jobs (recruiter_id, title, company, location, salary, description, requirements) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$recruiter_id, $title, $company, $location, $salary, $description, $requirements]);
            $msg = "New job opening posted successfully!";
        } catch (PDOException $e) {
            $error = "Failed to post job: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields (Job Title, Company, Description).";
    }
}

// Handle Interview Scheduling
if (isset($_POST['schedule_interview'])) {
    $app_id = intval($_POST['application_id']);
    $date_time = $_POST['scheduled_time'];
    $mode = $_POST['mode'];
    $link = trim($_POST['link']);
    $notes = trim($_POST['notes']);
    
    if ($app_id > 0 && !empty($date_time)) {
        try {
            $pdo->beginTransaction();
            
            // Insert interview details
            $stmt_int = $pdo->prepare("
                INSERT INTO interviews (application_id, scheduled_time, mode, link, notes) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt_int->execute([$app_id, $date_time, $mode, $link, $notes]);
            
            // Update application status to 'scheduled'
            $stmt_status = $pdo->prepare("UPDATE applications SET status = 'scheduled' WHERE id = ?");
            $stmt_status->execute([$app_id]);
            
            // Fetch student ID to notify
            $stmt_stud = $pdo->prepare("
                SELECT a.student_id, j.title, j.company 
                FROM applications a
                JOIN jobs j ON a.job_id = j.id
                WHERE a.id = ?
            ");
            $stmt_stud->execute([$app_id]);
            $app_info = $stmt_stud->fetch();
            
            if ($app_info) {
                addNotification($pdo, $app_info['student_id'], "An interview has been scheduled for your application for " . $app_info['title'] . " at " . $app_info['company'] . " on " . date('M d, H:i', strtotime($date_time)));
            }
            
            $pdo->commit();
            $msg = "Interview scheduled successfully!";
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to schedule interview: " . $e->getMessage();
        }
    } else {
        $error = "Please select a valid application and interview time.";
    }
}

// Handle Applicant Status Updates (Accept/Reject)
if (isset($_GET['action']) && isset($_GET['app_id'])) {
    $app_id = intval($_GET['app_id']);
    $action = $_GET['action'];
    $status = '';
    
    if ($action === 'shortlist') $status = 'shortlisted';
    if ($action === 'reject') $status = 'rejected';
    if ($action === 'select') $status = 'selected';
    if ($action === 'review') $status = 'reviewed';
    
    if ($status) {
        try {
            $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
            $stmt->execute([$status, $app_id]);
            
            // Notify student
            $stmt_info = $pdo->prepare("
                SELECT a.student_id, j.title 
                FROM applications a 
                JOIN jobs j ON a.job_id = j.id 
                WHERE a.id = ?
            ");
            $stmt_info->execute([$app_id]);
            $info = $stmt_info->fetch();
            if ($info) {
                addNotification($pdo, $info['student_id'], "Your application status for " . $info['title'] . " has been updated to: " . ucfirst($status));
            }
            
            $msg = "Applicant status updated to " . ucfirst($status);
        } catch (PDOException $e) {
            $error = "Failed to update status: " . $e->getMessage();
        }
    }
}

// Fetch all jobs posted by this recruiter
$stmt_jobs = $pdo->prepare("SELECT * FROM jobs WHERE recruiter_id = ? ORDER BY created_at DESC");
$stmt_jobs->execute([$recruiter_id]);
$posted_jobs = $stmt_jobs->fetchAll();

// Fetch all applicants for this recruiter's jobs
$stmt_apps = $pdo->prepare("
    SELECT a.id as application_id, a.status, a.applied_at, 
           u.name as student_name, u.email as student_email, 
           p.skills, p.experience, p.resume_path, p.ai_score,
           j.title as job_title, j.requirements as job_reqs, j.id as job_id
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.student_id = u.id
    JOIN students_profile p ON u.id = p.user_id
    WHERE j.recruiter_id = ?
    ORDER BY a.applied_at DESC
");
$stmt_apps->execute([$recruiter_id]);
$applicants = $stmt_apps->fetchAll();

include 'includes/header.php';
?>

<div class="container">
    <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="grid grid-2" style="margin-bottom: 3rem; align-items: start;">
        <!-- Post a Job Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Post New Job Opening</h3>
            </div>
            
            <form method="POST" action="recruiter_dashboard.php">
                <input type="hidden" name="post_job" value="1">
                
                <div class="grid grid-2" style="gap: 1rem;">
                    <div class="form-group">
                        <label for="title">Job Title *</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="Software Engineer" required>
                    </div>
                    <div class="form-group">
                        <label for="company">Company Name *</label>
                        <input type="text" name="company" id="company" class="form-control" placeholder="Acme Corp" required>
                    </div>
                </div>
                
                <div class="grid grid-2" style="gap: 1rem;">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" class="form-control" placeholder="Remote / New York">
                    </div>
                    <div class="form-group">
                        <label for="salary">Offered Salary (LPA / Range)</label>
                        <input type="text" name="salary" id="salary" class="form-control" placeholder="12 - 15 LPA">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="requirements">Core Target Skills (Comma-separated for AI matching) *</label>
                    <input type="text" name="requirements" id="requirements" class="form-control" placeholder="Java, Spring Boot, MySQL, React" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="description">Job Description *</label>
                    <textarea name="description" id="description" class="form-control" rows="4" placeholder="Brief details about the role, expectations, and requirements..." required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Publish Job Opening</button>
            </form>
        </div>

        <!-- Posted Jobs List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Active Job Postings</h3>
            </div>
            
            <?php if (empty($posted_jobs)): ?>
                <div style="text-align: center; color: var(--text-secondary); padding: 3rem 0;">
                    <p style="font-size: 1.5rem; margin-bottom: 0.5rem;">💼</p>
                    <p>You haven't posted any job openings yet.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Location</th>
                                <th>Salary</th>
                                <th>Skills</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posted_jobs as $job): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                        <div style="font-size:0.75rem; color:var(--text-secondary);"><?php echo htmlspecialchars($job['company']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?></td>
                                    <td><span class="badge badge-success"><?php echo htmlspecialchars($job['salary'] ?: 'N/A'); ?></span></td>
                                    <td>
                                        <div class="skills-tags">
                                            <?php 
                                            $reqs = explode(',', $job['requirements']);
                                            foreach (array_slice($reqs, 0, 3) as $req) {
                                                if (trim($req)) {
                                                    echo "<span class='skill-tag' style='font-size:0.7rem; padding:0.1rem 0.4rem;'>" . htmlspecialchars(trim($req)) . "</span>";
                                                }
                                            }
                                            if (count($reqs) > 3) echo "<span style='font-size:0.75rem; color:var(--text-secondary);'>+" . (count($reqs) - 3) . "</span>";
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Applicants & AI Matching Score Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Job Applicants & AI Profiles</h3>
        </div>
        
        <?php if (empty($applicants)): ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 3rem 0;">No candidate applications received yet.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Candidate Details</th>
                            <th>Applied Position</th>
                            <th>Overall AI Profile</th>
                            <th>AI Job Match</th>
                            <th>Resume</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applicants as $app): ?>
                            <?php 
                            // Dynamic PHP calculation for job-specific match score based on extracted skills
                            $job_reqs = array_map('trim', explode(',', $app['job_reqs']));
                            $student_skills = json_decode($app['skills'] ?? '[]', true) ?: [];
                            $match_count = 0;
                            foreach ($job_reqs as $req) {
                                if (in_array(strtolower($req), array_map('strtolower', $student_skills))) {
                                    $match_count++;
                                }
                            }
                            $job_match_score = count($job_reqs) > 0 ? round(($match_count / count($job_reqs)) * 100) : 100;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['student_name']); ?></strong>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo htmlspecialchars($app['student_email']); ?></div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['job_title']); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--text-secondary);">Applied <?php echo date('M d, Y', strtotime($app['applied_at'])); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $app['ai_score'] >= 70 ? 'badge-success' : ($app['ai_score'] >= 50 ? 'badge-warning' : 'badge-danger'); ?>">
                                        <?php echo $app['ai_score']; ?>% Score
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $job_match_score >= 70 ? 'badge-success' : ($job_match_score >= 40 ? 'badge-warning' : 'badge-danger'); ?>" style="font-weight: 700;">
                                        🎯 <?php echo $job_match_score; ?>% Match
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($app['resume_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($app['resume_path']); ?>" target="_blank" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                            📄 View Resume
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 0.85rem; color: var(--text-secondary);">No Resume</span>
                                    <?php endif; ?>
                                </td>
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
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <?php if ($app['status'] === 'applied'): ?>
                                            <a href="recruiter_dashboard.php?action=review&app_id=<?php echo $app['application_id']; ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">Review</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($app['status'] !== 'scheduled' && $app['status'] !== 'rejected' && $app['status'] !== 'selected'): ?>
                                            <button onclick="openScheduleModal(<?php echo $app['application_id']; ?>, '<?php echo htmlspecialchars($app['student_name']); ?>', '<?php echo htmlspecialchars($app['job_title']); ?>')" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">Schedule</button>
                                        <?php endif; ?>
                                        
                                        <?php if ($app['status'] === 'scheduled'): ?>
                                            <a href="recruiter_dashboard.php?action=select&app_id=<?php echo $app['application_id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: var(--success); box-shadow:none;">Hire</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($app['status'] !== 'rejected' && $app['status'] !== 'selected'): ?>
                                            <a href="recruiter_dashboard.php?action=reject&app_id=<?php echo $app['application_id']; ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; color:var(--danger);">Reject</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scheduling Modal -->
<div class="modal" id="schedule-modal">
    <div class="card modal-content" style="padding: 1.5rem;">
        <div class="card-header" style="margin-bottom: 1rem;">
            <h3 class="card-title" id="modal-title">Schedule Interview</h3>
            <button onclick="closeModal('schedule-modal')" style="background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size:1.2rem;">&times;</button>
        </div>
        
        <form method="POST" action="recruiter_dashboard.php">
            <input type="hidden" name="schedule_interview" value="1">
            <input type="hidden" name="application_id" id="modal-application-id">
            
            <div class="form-group">
                <label for="modal-candidate">Candidate</label>
                <input type="text" id="modal-candidate" class="form-control" readonly>
            </div>
            
            <div class="form-group">
                <label for="modal-role">Role</label>
                <input type="text" id="modal-role" class="form-control" readonly>
            </div>
            
            <div class="form-group">
                <label for="scheduled_time">Date & Time *</label>
                <input type="datetime-local" name="scheduled_time" id="scheduled_time" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="mode">Interview Mode</label>
                <select name="mode" id="mode" class="form-control">
                    <option value="Online">Online Video Call</option>
                    <option value="Offline / In-person">Offline / In-person</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="link">Meeting Link (e.g. Google Meet, Zoom)</label>
                <input type="url" name="link" id="link" class="form-control" placeholder="https://meet.google.com/abc-defg-hij">
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="notes">Special Notes for Candidate</label>
                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Please keep copy of resume, portfolio, etc. ready..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Confirm & Schedule</button>
        </form>
    </div>
</div>

<script>
function openScheduleModal(appId, name, role) {
    document.getElementById('modal-application-id').value = appId;
    document.getElementById('modal-candidate').value = name;
    document.getElementById('modal-role').value = role;
    
    // Set default datetime (tomorrow at 10 AM)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(10, 0, 0, 0);
    
    // Format to YYYY-MM-DDTHH:MM
    const offset = tomorrow.getTimezoneOffset();
    const localTomorrow = new Date(tomorrow.getTime() - (offset*60*1000));
    document.getElementById('scheduled_time').value = localTomorrow.toISOString().slice(0, 16);
    
    openModal('schedule-modal');
}
</script>

<?php include 'includes/footer.php'; ?>
