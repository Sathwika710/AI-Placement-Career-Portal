<?php
require_once 'includes/config.php';
checkRole('admin');

$msg = '';
$error = '';

// Handle Delete User
if (isset($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    
    // Prevent self-deletion
    if ($uid == $_SESSION['user_id']) {
        $error = "You cannot delete your own admin account.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$uid]);
            $msg = "User deleted successfully.";
        } catch (PDOException $e) {
            $error = "Failed to delete user: " . $e->getMessage();
        }
    }
}

// Fetch stats counts
try {
    $student_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $recruiter_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'recruiter'")->fetchColumn();
    $job_count = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
    $app_count = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    
    // Applications Status Distribution
    $status_data = $pdo->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status")->fetchAll();
    
    // Fetch students list (including profiles and resumes)
    $stmt_students = $pdo->prepare("SELECT u.id, u.name, u.email, u.created_at, p.phone, p.skills, p.ai_score, p.resume_path FROM users u LEFT JOIN students_profile p ON u.id = p.user_id WHERE u.role = 'student' ORDER BY u.created_at DESC");
    $stmt_students->execute();
    $students_list = $stmt_students->fetchAll();

    // Fetch recruiters list
    $stmt_recruiters = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE role = 'recruiter' ORDER BY created_at DESC");
    $stmt_recruiters->execute();
    $recruiters_list = $stmt_recruiters->fetchAll();
    
    // Skills aggregation logic
    $skills_stmt = $pdo->query("SELECT skills FROM students_profile WHERE skills IS NOT NULL");
    $all_skills = [];
    while ($row = $skills_stmt->fetch()) {
        $skills_arr = json_decode($row['skills'], true);
        if (is_array($skills_arr)) {
            foreach ($skills_arr as $sk) {
                $all_skills[] = $sk;
            }
        }
    }
    
    $skills_freq = array_count_values($all_skills);
    arsort($skills_freq);
    $top_skills = array_slice($skills_freq, 0, 6, true);
    
} catch (PDOException $e) {
    $error = "Database queries failed: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container">
    <h2 style="font-family: var(--font-outfit); margin-bottom: 2rem;">Placement & Career Portal Analytics</h2>

    <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Summary Counters -->
    <div class="grid grid-3" style="margin-bottom: 3rem;">
        <div class="card stat-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div class="stat-value"><?php echo $student_count; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.85;">👨‍🎓</div>
        </div>
        
        <div class="card stat-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div class="stat-value"><?php echo $recruiter_count; ?></div>
                <div class="stat-label">Total Recruiters</div>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.85;">💼</div>
        </div>
        
        <div class="card stat-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div class="stat-value"><?php echo $job_count; ?></div>
                <div class="stat-label">Active Job Openings</div>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.85;">🚀</div>
        </div>
    </div>

    <!-- Analytics Charts Grid -->
    <div class="grid grid-2" style="margin-bottom: 3rem;">
        <!-- Application Distribution Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Application Status Distribution</h3>
            </div>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Top Parsed Skills Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Parsed Candidate Skills (AI Extracted)</h3>
            </div>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="skillsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Student Profiles & Resumes -->
    <div class="card" style="margin-bottom: 3rem;">
        <div class="card-header">
            <h3 class="card-title">Student Directory & AI Resumes</h3>
        </div>
        
        <?php if (empty($students_list)): ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 2rem 0;">No students registered yet.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student Details</th>
                            <th>Contact Phone</th>
                            <th>AI Score</th>
                            <th>Extracted Skills</th>
                            <th>Resume Path / File</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students_list as $stud): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($stud['name']); ?></strong>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo htmlspecialchars($stud['email']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($stud['phone'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php if ($stud['ai_score'] > 0): ?>
                                        <span class="badge <?php echo $stud['ai_score'] >= 70 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $stud['ai_score']; ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Not Uploaded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="skills-tags">
                                        <?php 
                                        $skills = json_decode($stud['skills'] ?? '[]', true);
                                        if (empty($skills)) {
                                            echo "<span style='font-size: 0.75rem; color: var(--text-secondary);'>None</span>";
                                        } else {
                                            foreach (array_slice($skills, 0, 4) as $sk) {
                                                echo "<span class='skill-tag' style='font-size: 0.7rem; padding: 0.1rem 0.4rem;'>" . htmlspecialchars($sk) . "</span>";
                                            }
                                            if (count($skills) > 4) {
                                                echo "<span style='font-size: 0.75rem; color: var(--text-secondary);'>+" . (count($skills) - 4) . "</span>";
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($stud['resume_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($stud['resume_path']); ?>" target="_blank" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                            📄 View Resume
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 0.85rem; color: var(--text-secondary);">No Resume</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="admin_dashboard.php?delete_user=<?php echo $stud['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this student? All their applications and details will be deleted.');" 
                                       class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; color: var(--danger);">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recruiter Accounts Directory -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recruiter Directory</h3>
        </div>
        
        <?php if (empty($recruiters_list)): ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 2rem 0;">No recruiters registered yet.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Recruiter / Company</th>
                            <th>Email Address</th>
                            <th>Registered Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recruiters_list as $rec): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($rec['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($rec['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($rec['created_at'])); ?></td>
                                <td>
                                    <a href="admin_dashboard.php?delete_user=<?php echo $rec['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this recruiter? All their jobs and candidate logs will be deleted.');" 
                                       class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; color: var(--danger);">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Status Chart Setup
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    
    // PHP variables mapped to JS arrays
    const statusLabels = [];
    const statusCounts = [];
    
    <?php foreach ($status_data as $s): ?>
        statusLabels.push("<?php echo ucfirst($s['status']); ?>");
        statusCounts.push(<?php echo $s['count']; ?>);
    <?php endforeach; ?>
    
    if (statusLabels.length === 0) {
        statusLabels.push("No Applications yet");
        statusCounts.push(0);
    }

    new Chart(statusCtx, {
        type: 'bar',
        data: {
            labels: statusLabels,
            datasets: [{
                label: 'Applications',
                data: statusCounts,
                backgroundColor: [
                    'rgba(14, 165, 233, 0.45)',
                    'rgba(245, 158, 11, 0.45)',
                    'rgba(16, 185, 129, 0.45)',
                    'rgba(244, 63, 94, 0.45)',
                    'rgba(156, 163, 175, 0.45)'
                ],
                borderColor: [
                    '#0ea5e9',
                    '#f59e0b',
                    '#10b981',
                    '#f43f5e',
                    '#9ca3af'
                ],
                borderWidth: 1.5,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // 2. Skills Chart Setup
    const skillsCtx = document.getElementById('skillsChart').getContext('2d');
    
    const skillLabels = [];
    const skillCounts = [];
    
    <?php foreach ($top_skills as $label => $val): ?>
        skillLabels.push("<?php echo $label; ?>");
        skillCounts.push(<?php echo $val; ?>);
    <?php endforeach; ?>
    
    if (skillLabels.length === 0) {
        skillLabels.push("No skills parsed yet");
        skillCounts.push(0);
    }

    new Chart(skillsCtx, {
        type: 'doughnut',
        data: {
            labels: skillLabels,
            datasets: [{
                data: skillCounts,
                backgroundColor: [
                    '#0ea5e9',
                    '#10b981',
                    '#f59e0b',
                    '#f43f5e',
                    '#8b5cf6',
                    '#ec4899'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: '#94a3b8', font: { size: 11 } }
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
