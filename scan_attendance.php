<?php
require_once 'includes/config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    die('Invalid QR code. Please contact your lecturer.');
}

// Find the session
$stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE qr_code = ?");
$stmt->execute([$token]);
$session = $stmt->fetch();

if (!$session) {
    die('Invalid QR code. This session does not exist.');
}

// Check if session date is today or in past (optional)
// Allow marking within a day? We'll allow any time.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');

    if (empty($student_id) || empty($full_name)) {
        $error = 'Please enter your student ID and full name.';
    } else {
        // Validate student exists and name matches
        $check = $pdo->prepare("
            SELECT s.id, u.full_name 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.student_id = ? AND u.status = 'active'
        ");
        $check->execute([$student_id]);
        $student = $check->fetch();

        if (!$student) {
            $error = 'Student ID not found or inactive. Please contact admin.';
        } elseif (strcasecmp($student['full_name'], $full_name) !== 0) {
            $error = 'Name does not match our records. Please check your details.';
        } else {
            // Check if already marked attendance for this session
            $check_att = $pdo->prepare("SELECT id FROM attendance WHERE session_id = ? AND student_id = ?");
            $check_att->execute([$session['id'], $student['id']]);
            if ($check_att->fetch()) {
                $error = 'You have already marked attendance for this session.';
            } else {
                // Mark attendance
                $insert = $pdo->prepare("INSERT INTO attendance (session_id, student_id, status, marked_at) VALUES (?, ?, 'present', NOW())");
                if ($insert->execute([$session['id'], $student['id']])) {
                    $success = 'Attendance marked successfully! Thank you.';
                } else {
                    $error = 'Failed to mark attendance. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f0eeff; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { max-width: 500px; width: 100%; }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow p-4">
        <h3 class="text-center"><i class="fas fa-clipboard-check me-2"></i> Attendance</h3>
        <hr>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?php echo htmlspecialchars($success); ?></div>
            <div class="text-center mt-3">
                <i class="fas fa-check-circle text-success" style="font-size:3rem;"></i>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Student ID</label>
                    <input type="text" name="student_id" class="form-control" placeholder="e.g. STU2024001" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. Abu Bakarr Sesay" required>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-check me-1"></i> Mark Attendance</button>
            </form>
        <?php endif; ?>
        <div class="text-center mt-3 text-muted" style="font-size:0.8rem;">
            <i class="fas fa-info-circle"></i> This session is for course: <strong><?php echo htmlspecialchars($session['course_id']); ?></strong>
            (you can fetch course name by joining with courses table if needed)
        </div>
    </div>
</div>
</body>
</html>