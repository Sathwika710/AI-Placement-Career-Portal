<?php
require_once 'includes/config.php';
checkRole('student');

$student_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resume'])) {
    $file = $_FILES['resume'];
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Upload failed with error code: " . $file['error'];
        redirect('student_dashboard.php');
    }
    
    // Validate Extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf' && $ext !== 'txt') {
        $_SESSION['error'] = "Invalid file type. Only PDF and TXT files are supported.";
        redirect('student_dashboard.php');
    }
    
    // Create uploads directory if not exists
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique name
    $new_filename = 'resume_' . $student_id . '_' . time() . '.' . $ext;
    $dest_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
        // Run python resume analyzer script
        $py_script = realpath('python/resume_analyzer.py');
        $resume_abs = realpath($dest_path);
        
        $cmd = "python " . escapeshellarg($py_script) . " --file " . escapeshellarg($resume_abs);
        $output = shell_exec($cmd);
        
        if ($output) {
            $data = json_decode($output, true);
            
            if (isset($data['error'])) {
                $_SESSION['error'] = "AI Analysis Error: " . $data['error'];
            } else {
                // Update DB
                $skills_json = json_encode($data['skills'] ?? []);
                $feedback_json = json_encode($data['feedback'] ?? []);
                $score = intval($data['overall_score'] ?? 0);
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE students_profile 
                        SET resume_path = ?, skills = ?, ai_score = ?, ai_feedback = ?, phone = ? 
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$dest_path, $skills_json, $score, $feedback_json, $data['phone'] ?? null, $student_id]);
                    
                    addNotification($pdo, $student_id, "AI Resume analysis updated successfully. Score: " . $score . "%");
                    $_SESSION['msg'] = "Resume uploaded and analyzed by AI successfully!";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Database insertion error: " . $e->getMessage();
                }
            }
        } else {
            $_SESSION['error'] = "Failed to execute AI analysis engine.";
        }
    } else {
        $_SESSION['error'] = "Failed to save uploaded file.";
    }
} else {
    $_SESSION['error'] = "Invalid upload request.";
}

// Redirect back to dashboard to display outcomes
redirect('student_dashboard.php');
?>
