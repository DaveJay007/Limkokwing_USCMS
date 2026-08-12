<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/config.php';

$assignment_id = (int)($_GET['assignment_id'] ?? 0);
if ($assignment_id < 1) {
    die('Invalid assignment.');
}

// Get assignment details
$stmt = $pdo->prepare("
    SELECT a.*, c.code as course_code, c.name as course_name
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();
if (!$assignment) {
    die('Assignment not found.');
}

// Check if student already submitted
$student_id = $pdo->prepare("SELECT id FROM students WHERE user_id = ?")->execute([$_SESSION['user_id']]);
// Actually fetch
$s_stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$s_stmt->execute([$_SESSION['user_id']]);
$student = $s_stmt->fetch();
if (!$student) {
    die('Student record not found.');
}
$student_id = $student['id'];

$submitted = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
$submitted->execute([$assignment_id, $student_id]);
$existing = $submitted->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    if ($existing) {
        $error = 'You have already submitted for this assignment.';
    } elseif (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'zip', 'rar', 'txt'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = 'File type not allowed. Allowed: ' . implode(', ', $allowed);
        } else {
            $uploadDir = '../../uploads/submissions/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = time() . '_' . $_SESSION['user_id'] . '_' . basename($_FILES['file']['name']);
            $filepath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
                $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file_path) VALUES (?, ?, ?)");
                if ($stmt->execute([$assignment_id, $student_id, 'uploads/submissions/' . $filename])) {
                    $success = 'Assignment submitted successfully!';
                } else {
                    $error = 'Database error.';
                }
            } else {
                $error = 'Failed to upload file.';
            }
        }
    } else {
        $error = 'Please select a file to upload.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-upload me-2"></i> Submit Assignment</h2>
    <hr>
    <div class="card shadow p-4">
        <h5><?php echo htmlspecialchars($assignment['title']); ?></h5>
        <p><strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></p>
        <p><strong>Due Date:</strong> <?php echo date('d M Y H:i', strtotime($assignment['due_date'])); ?></p>
        <p><strong>Max Score:</strong> <?php echo $assignment['max_score']; ?></p>
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>

        <?php if ($existing): ?>
            <div class="alert alert-info">You have already submitted this assignment. Your submission is being reviewed.</div>
            <a href="my_submissions.php" class="btn btn-secondary">View My Submissions</a>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Upload your work (PDF, DOC, ZIP, etc.)</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <button type="submit" name="submit_assignment" class="btn btn-primary"><i class="fas fa-upload me-1"></i> Submit</button>
                <a href="assignments.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success mt-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>