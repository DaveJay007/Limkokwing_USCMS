<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Get lists for dropdowns
$students = $pdo->query("
    SELECT s.id, u.full_name, s.student_id 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE u.status = 'active'
    ORDER BY u.full_name
")->fetchAll();

$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY code")->fetchAll();

$lecturers = $pdo->query("
    SELECT l.id, u.full_name 
    FROM lecturers l 
    JOIN users u ON l.user_id = u.id 
    WHERE u.status = 'active'
    ORDER BY u.full_name
")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id    = (int)($_POST['student_id'] ?? 0);
    $course_id     = (int)($_POST['course_id'] ?? 0);
    $lecturer_id   = (int)($_POST['lecturer_id'] ?? 0);
    $semester      = trim($_POST['semester'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '');
    $score         = (float)($_POST['score'] ?? -1);
    $grade         = trim($_POST['grade'] ?? '');
    $grade_point   = (float)($_POST['grade_point'] ?? 0);

    if ($student_id < 1 || $course_id < 1 || empty($semester) || empty($academic_year) || $score < 0) {
        $error = 'All fields except lecturer are required, and score must be >= 0.';
    } else {
        // Check for duplicate
        $check = $pdo->prepare("SELECT id FROM results WHERE student_id = ? AND course_id = ? AND semester = ? AND academic_year = ?");
        $check->execute([$student_id, $course_id, $semester, $academic_year]);
        if ($check->fetch()) {
            $error = 'This grade already exists. Use Edit to update.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO results (student_id, course_id, lecturer_id, semester, academic_year, score, grade, grade_point)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$student_id, $course_id, $lecturer_id ?: null, $semester, $academic_year, $score, $grade, $grade_point])) {
                $success = 'Grade added successfully!';
                $_POST = [];
            } else {
                $error = 'Failed to add grade.';
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
    <title>Add Grade — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-plus-circle me-2"></i> Add Grade</h2>
    <hr>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card shadow p-4">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Student *</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo ($_POST['student_id'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['student_id'] . ' - ' . $s['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course *</label>
                    <select name="course_id" class="form-select" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($_POST['course_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Lecturer (Optional)</label>
                    <select name="lecturer_id" class="form-select">
                        <option value="">Select Lecturer</option>
                        <?php foreach ($lecturers as $l): ?>
                            <option value="<?php echo $l['id']; ?>" <?php echo ($_POST['lecturer_id'] ?? '') == $l['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($l['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Semester *</label>
                    <select name="semester" class="form-select" required>
                        <option value="">Select Semester</option>
                        <?php
                        $semesters = ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6', 'Summer'];
                        foreach ($semesters as $s) {
                            $selected = ($_POST['semester'] ?? '') == $s ? 'selected' : '';
                            echo "<option value=\"$s\" $selected>$s</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Academic Year *</label>
                    <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2024-2025" value="<?php echo htmlspecialchars($_POST['academic_year'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Score *</label>
                    <input type="number" step="0.1" name="score" class="form-control" placeholder="e.g. 85.5" value="<?php echo htmlspecialchars($_POST['score'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Grade (Letter)</label>
                    <input type="text" name="grade" class="form-control" placeholder="e.g. A, B+, C" value="<?php echo htmlspecialchars($_POST['grade'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Grade Point</label>
                    <input type="number" step="0.01" name="grade_point" class="form-control" placeholder="e.g. 4.0" value="<?php echo htmlspecialchars($_POST['grade_point'] ?? ''); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add Grade</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <a href="../../dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
</div>
</body>
</html>