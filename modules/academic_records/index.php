<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Filters
$student_filter = $_GET['student_id'] ?? '';
$course_filter = $_GET['course_id'] ?? '';

$query = "
    SELECT 
        r.*,
        u.full_name as student_name,
        s.student_id as student_code,
        c.code as course_code,
        c.name as course_name,
        lec.full_name as lecturer_name
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN courses c ON r.course_id = c.id
    LEFT JOIN lecturers l ON r.lecturer_id = l.id
    LEFT JOIN users lec ON l.user_id = lec.id
    WHERE 1=1
";

$params = [];
if ($student_filter) {
    $query .= " AND s.student_id LIKE ?";
    $params[] = "%$student_filter%";
}
if ($course_filter) {
    $query .= " AND c.id = ?";
    $params[] = $course_filter;
}

$query .= " ORDER BY u.full_name, c.code";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

// Get list of courses for filter dropdown
$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY code")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Records — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-graduation-cap me-2"></i> Academic Records</h2>
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Grade</a>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" name="student_id" class="form-control" placeholder="Search Student ID" value="<?php echo htmlspecialchars($student_filter); ?>">
        </div>
        <div class="col-md-4">
            <select name="course_id" class="form-select">
                <option value="">All Courses</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($course_filter == $c['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card shadow">
        <div class="card-body">
            <?php if (count($results) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Semester</th>
                                <th>Year</th>
                                <th>Score</th>
                                <th>Grade</th>
                                <th>GP</th>
                                <th>Lecturer</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($row['student_code']); ?></small>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['course_code']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($row['course_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                <td><?php echo $row['score'] !== null ? number_format($row['score'], 1) : '-'; ?></td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['grade'] ?? '-'); ?></span></td>
                                <td><?php echo $row['grade_point'] !== null ? number_format($row['grade_point'], 2) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($row['lecturer_name'] ?? 'Not assigned'); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this grade?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No grades entered yet. <a href="add.php">Add one now</a>.</p>
            <?php endif; ?>
        </div>
    </div>
    <a href="../../dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
</div>
</body>
</html>