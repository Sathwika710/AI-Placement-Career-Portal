USE ai_placement_portal;

-- Clean existing data except admin
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE interviews;
TRUNCATE TABLE applications;
TRUNCATE TABLE jobs;
TRUNCATE TABLE students_profile;
DELETE FROM users WHERE role != 'admin';
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Insert ONE Shared Recruiter (Password: recruiter123)
INSERT INTO users (id, name, email, password, role) VALUES
(2, 'Company Recruiter', 'recruiter@placement.com', '$2y$10$91ILOBr53SqYk5nSO3xveO1IsNQvUDixbkKFhFJfiKfWUbtFOect.', 'recruiter');

-- 2. Insert Students (Password: student123)
INSERT INTO users (id, name, email, password, role) VALUES
(7, 'Aarav Sharma', 'student_aarav@placement.com', '$2y$10$Q9VPjmhd//IUIjWttAhX.e8HFLJBey5y8ux63/aC9Irvv4wt8xera', 'student'),
(8, 'Ananya Patel', 'student_ananya@placement.com', '$2y$10$Q9VPjmhd//IUIjWttAhX.e8HFLJBey5y8ux63/aC9Irvv4wt8xera', 'student'),
(9, 'Rahul Kumar', 'student_rahul@placement.com', '$2y$10$Q9VPjmhd//IUIjWttAhX.e8HFLJBey5y8ux63/aC9Irvv4wt8xera', 'student'),
(10, 'Pusapati Sathwika', 'sathwikapusapati@gmail.com', '$2y$10$Q9VPjmhd//IUIjWttAhX.e8HFLJBey5y8ux63/aC9Irvv4wt8xera', 'student');

-- 3. Insert Students Profiles
INSERT INTO students_profile (user_id, phone, skills, experience, education, resume_path, ai_score, ai_feedback) VALUES
(7, '+91 9876501010', '["Java", "Spring Boot", "MySQL", "Git", "HTML", "CSS"]', '1 year internship at CodeLabs', 'B.Tech in CSE', 'uploads/resume_aarav.txt', 82, '["Excellent foundation in core Java and Spring Boot.","Consider adding more Docker and Cloud hosting skills to increase score."]'),
(8, '+91 9876502020', '["Python", "Machine Learning", "Deep Learning", "SQL", "Git"]', 'Personal projects in NLP', 'M.Tech in Data Science', 'uploads/resume_ananya.pdf', 90, '["Exceptional machine learning portfolio.","Highly competitive profile for AI engineer roles."]'),
(9, '+91 9876503030', '["React", "HTML", "CSS", "JavaScript", "Node", "MongoDB"]', 'Freelance Web Developer', 'B.Tech in Information Technology', 'uploads/resume_rahul.txt', 75, '["Great frontend framework experience.","Recommend learning Python or SQL basics to broaden job prospects."]'),
(10, '+91 9876543210', '["Java", "Python", "MySQL", "HTML", "CSS", "JavaScript", "Git"]', 'B.Tech Computer Science student with ML project work.', 'B.Tech in CSE', 'uploads/sample_aarav_sharma.txt', 95, '["Excellent academic credentials and skill match.","Great score! Ready for placements."]');

-- 4. Insert Jobs (All linked to the shared Recruiter ID: 2)
INSERT INTO jobs (id, recruiter_id, title, company, location, salary, description, requirements) VALUES
(1, 2, 'Software Engineer (ML/AI)', 'Google', 'Bengaluru, Karnataka', '32 LPA', 'We are looking for a Machine Learning Engineer to design and implement advanced models for search queries and user behavior understanding.', 'Python, Machine Learning, Deep Learning, SQL'),
(2, 2, 'Full Stack Developer', 'Microsoft', 'Hyderabad, Telangana / Remote', '24 LPA', 'Join the Azure Portal Core team. You will be building user interfaces and highly scalable backend APIs using Java and React.', 'Java, Spring Boot, React, MySQL, JavaScript'),
(3, 2, 'Frontend Engineer', 'Meta', 'Gurugram, Haryana', '28 LPA', 'Responsible for building responsive web interfaces for Facebook and Instagram features using modern React concepts.', 'React, HTML, CSS, JavaScript, Git'),
(4, 2, 'Cloud Support Engineer', 'Amazon', 'Chennai, Tamil Nadu', '22 LPA', 'Assist enterprise customers in architecting and running Docker/Kubernetes containerized applications on AWS cloud backend systems.', 'AWS, Docker, Kubernetes, Linux'),
(5, 2, 'Senior Backend Engineer', 'Netflix', 'Mumbai, Maharashtra', '45 LPA', 'Design and scale the media streaming ingest APIs. Strong multithreaded programming and relational/in-memory data architecture experience required.', 'Java, Spring Boot, MySQL, Redis, Docker'),
(6, 2, 'DevOps Engineer', 'Netflix', 'Remote (India)', '35 LPA', 'Design, build, and support infrastructure deployment pipelines using Docker, Kubernetes, AWS, and automation tooling.', 'AWS, Docker, Kubernetes, Linux, Git'),
(7, 2, 'AI Research Scientist', 'OpenAI', 'Bengaluru, Karnataka', '55 LPA', 'Research and develop large-scale language models, multi-modal systems, and artificial general intelligence architectures.', 'Python, Machine Learning, Deep Learning, NLP, AI'),
(8, 2, 'Mobile Application Developer', 'Spotify', 'Pune, Maharashtra', '20 LPA', 'Craft beautiful user experiences and high-fidelity interfaces for Spotify Android and iOS apps.', 'Kotlin, Java, Swift, Git'),
(9, 2, 'Backend Node.js Developer', 'Uber', 'Noida, Uttar Pradesh', '28 LPA', 'Build scalable distributed systems and backend APIs in Node.js for real-time dispatch systems.', 'Node, Express, JavaScript, MongoDB, Redis'),
(10, 2, 'Data Analyst', 'Apple', 'Hyderabad, Telangana', '18 LPA', 'Query large-scale databases, build insights dashboards, and perform deep-dive analyses to guide product decisions.', 'Python, SQL, MySQL, Git'),
(11, 2, 'Cybersecurity Engineer', 'IBM', 'Kochi, Kerala', '26 LPA', 'Analyze security systems, secure networks, and identify vulnerabilities across containerized deployment patterns.', 'Linux, Docker, SQL, Git'),
(12, 2, 'Python Full Stack Engineer', 'Airbnb', 'Remote (India)', '18 LPA', 'Work on both backend web services in Python Flask/Django and frontend interactive components in React.', 'Python, Flask, Django, React, JavaScript, HTML, CSS, Git');


-- 5. Insert Job Applications
INSERT INTO applications (id, job_id, student_id, status) VALUES
(1, 1, 8, 'scheduled'),   -- Ananya applied to Google
(2, 2, 7, 'scheduled'),   -- Aarav applied to Microsoft
(3, 3, 9, 'scheduled'),   -- Rahul applied to Meta
(4, 4, 7, 'applied'),     -- Aarav applied to Amazon
(5, 5, 8, 'applied'),     -- Ananya applied to Netflix
(6, 1, 10, 'scheduled'),  -- Sathwika applied to Google
(7, 2, 10, 'applied');    -- Sathwika applied to Microsoft

-- 6. Insert Scheduled Interviews
INSERT INTO interviews (application_id, scheduled_time, mode, link, notes) VALUES
(1, '2026-07-08 10:00:00', 'Online', 'https://meet.google.com/abc-defg-hij', 'Focus will be on Machine Learning concepts, algorithms, and deep learning architectures.'),
(2, '2026-07-09 14:30:00', 'Online', 'https://teams.microsoft.com/l/meetup-join/12345', 'Will include coding challenge (Java/Data Structures) and questions about Spring Boot.'),
(3, '2026-07-10 11:00:00', 'Online', 'https://meet.google.com/xyz-qprs-tuv', 'Frontend system design and coding challenge in React / CSS animations.'),
(6, '2026-07-12 09:30:00', 'Online', 'https://meet.google.com/sathwika-google-meet', 'ML Algorithm round for Google. Keep resume copy ready.');

-- 7. Insert Notifications
INSERT INTO notifications (user_id, message, is_read) VALUES
(8, 'Your application status for Software Engineer (ML/AI) at Google is updated to Scheduled.', 0),
(7, 'Your application status for Full Stack Developer at Microsoft is updated to Scheduled.', 0),
(9, 'Your application status for Frontend Engineer at Meta is updated to Scheduled.', 0),
(10, 'Your application status for Software Engineer (ML/AI) at Google is updated to Scheduled.', 0);
