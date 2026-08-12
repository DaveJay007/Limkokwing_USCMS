<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Fetch all courses with lecturer name
$stmt = $pdo->query("
    SELECT c.*, u.full_name as lecturer_name
    FROM courses c
    LEFT JOIN lecturers l ON c.lecturer_id = l.id
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY c.code
");
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-book-open me-2"></i> Course Management</h2>
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Course</a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Credits</th>
                            <th>Semester</th>
                            <th>Lecturer</th>
                            <th>Programme</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($courses) > 0): ?>
                            <?php foreach ($courses as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['credits']); ?></td>
                                <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                <td><?php echo htmlspecialchars($row['lecturer_name'] ?? 'Not assigned'); ?></td>
                                <td><?php echo htmlspecialchars($row['programme'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this course?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted">No courses found. <a href="add.php">Add your first course</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <a href="../../dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
</div>
</body>
</html>