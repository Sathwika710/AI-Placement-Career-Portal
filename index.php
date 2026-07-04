<?php
require_once 'includes/config.php';
include 'includes/header.php';
?>

<div class="hero">
    <div class="hero-glow"></div>
    <h1>The Smart Way to Get <span>Hired</span> & <span>Recruit</span></h1>
    <p>Upload your resume, let our AI engine analyze your technical matches, and land your dream job instantly. Recruiters get automated applicant ranking matching their requirements.</p>
    
    <div style="display: flex; justify-content: center; gap: 1.5rem;">
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-primary" style="padding: 0.8rem 2rem; font-size: 1.05rem;">Get Started</a>
            <a href="login.php" class="btn btn-secondary" style="padding: 0.8rem 2rem; font-size: 1.05rem;">Explore Jobs</a>
        <?php else: ?>
            <?php if (getRole() === 'student'): ?>
                <a href="student_dashboard.php" class="btn btn-primary" style="padding: 0.8rem 2rem; font-size: 1.05rem;">Go to Student Dashboard</a>
            <?php elseif (getRole() === 'recruiter'): ?>
                <a href="recruiter_dashboard.php" class="btn btn-primary" style="padding: 0.8rem 2rem; font-size: 1.05rem;">Go to Recruiter Dashboard</a>
            <?php else: ?>
                <a href="admin_dashboard.php" class="btn btn-primary" style="padding: 0.8rem 2rem; font-size: 1.05rem;">Go to Admin Panel</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="container" style="padding-top: 0;">
    <div class="grid grid-3" style="margin-bottom: 5rem;">
        <div class="card feature-card">
            <div class="feature-icon">🤖</div>
            <h3 style="font-family: var(--font-outfit); margin-bottom: 0.75rem;">AI Resume Analysis</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Upload your PDF resume. Our AI NLP engine extracts your skills, grades your experience, and gives instantly actionable suggestions.</p>
        </div>
        
        <div class="card feature-card">
            <div class="feature-icon">🎯</div>
            <h3 style="font-family: var(--font-outfit); margin-bottom: 0.75rem;">Precision Matching</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Recruiters post roles with target skill tags. Applications are ranked using an AI relevance score, so the best fit is found first.</p>
        </div>
        
        <div class="card feature-card">
            <div class="feature-icon">📅</div>
            <h3 style="font-family: var(--font-outfit); margin-bottom: 0.75rem;">Seamless Interviews</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Schedule interviews with automatic calendars, integrated links, alerts, and live notifications on applicants' dashboards.</p>
        </div>
    </div>
    
    <div class="card" style="text-align: center; margin-bottom: 4rem;">
        <h2 style="font-family: var(--font-outfit); margin-bottom: 2rem;">Portal Placement Achievements</h2>
        <div class="grid grid-3">
            <div class="stat-card">
                <div class="stat-value">94%</div>
                <div class="stat-label">Hiring Rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">4.2 Hrs</div>
                <div class="stat-label">Avg. Review Time</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">12.4 LPA</div>
                <div class="stat-label">Average Package</div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
