<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Get all courses for dropdown
$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY code")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $session_date = $_POST['session_date'] ?? date('Y-m-d');

    if ($course_id < 1) {
        $error = 'Please select a course.';
    } elseif (empty($session_date)) {
        $error = 'Please select a date.';
    } else {
        // Generate a unique token for QR
        $qr_token = bin2hex(random_bytes(16)); // 32-character hex

        // Insert session
        $stmt = $pdo->prepare("INSERT INTO attendance_sessions (course_id, session_date, qr_code) VALUES (?, ?, ?)");
        if ($stmt->execute([$course_id, $session_date, $qr_token])) {
            $session_id = $pdo->lastInsertId();
            $success = "Attendance session created!";
            // Redirect to mark page after creation
            header("Location: mark.php?session_id=$session_id&created=1");
            exit;
        } else {
            $error = 'Failed to create session.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Attendance Session — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-calendar-plus me-2"></i> Create Attendance Session</h2>
    <hr>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card shadow p-4">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Select Course *</label>
                <select name="course_id" class="form-select" required>
                    <option value="">-- Choose Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Date *</label>
                <input type="date" name="session_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Session & Mark Attendance</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <a href="../../dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
</div>
</body>
</html>