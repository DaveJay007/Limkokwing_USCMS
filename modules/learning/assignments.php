<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/config.php';

$error = '';
$success = '';

$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY code")->fetchAll();
$lecturers = $pdo->query("SELECT l.id, u.full_name FROM lecturers l JOIN users u ON l.user_id = u.id WHERE u.status = 'active' ORDER BY u.full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $lecturer_id = (int)($_POST['lecturer_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    $max_score = (float)($_POST['max_score'] ?? 100);

    if ($course_id < 1 || $lecturer_id < 1 || empty($title) || empty($due_date)) {
        $error = 'All fields except description are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO assignments (course_id, lecturer_id, title, description, due_date, max_score) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$course_id, $lecturer_id, $title, $description, $due_date, $max_score])) {
            $success = 'Assignment created!';
        } else {
            $error = 'Database error.';
        }
    }
}

// List assignments
$assignments = $pdo->query("
    SELECT a.*, c.code as course_code, u.full_name as lecturer_name
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN lecturers l ON a.lecturer_id = l.id
    JOIN users u ON l.user_id = u.id
    ORDER BY a.due_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tasks me-2"></i> Assignments</h2>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <!-- Create Assignment -->
    <div class="card shadow p-4 mb-4">
        <h5><i class="fas fa-plus-circle me-2"></i> Create New Assignment</h5>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-4">
                    <select name="course_id" class="form-select" required>
                        <option value="">Course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="lecturer_id" class="form-select" required>
                        <option value="">Lecturer</option>
                        <?php foreach ($lecturers as $l): ?>
                            <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="title" class="form-control" placeholder="Title" required>
                </div>
                <div class="col-md-6">
                    <textarea name="description" class="form-control" placeholder="Description" rows="2"></textarea>
                </div>
                <div class="col-md-3">
                    <label>Due Date</label>
                    <input type="datetime-local" name="due_date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label>Max Score</label>
                    <input type="number" step="1" name="max_score" class="form-control" value="100" required>
                </div>
                <div class="col-md-12">
                    <button type="submit" name="create_assignment" class="btn btn-success"><i class="fas fa-save me-1"></i> Create</button>
                </div>
            </div>
        </form>
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success mt-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
    </div>

    <!-- List Assignments -->
    <div class="card shadow">
        <div class="card-body">
            <h5>All Assignments</h5>
            <?php if (count($assignments) > 0): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Title</th>
                            <th>Lecturer</th>
                            <th>Due Date</th>
                            <th>Max Score</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['lecturer_name']); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($row['due_date'])); ?></td>
                            <td><?php echo $row['max_score']; ?></td>
                            <td><a href="submit.php?assignment_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-upload"></i> Submit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No assignments yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>