<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Fetch all timetable entries with course, lecturer, room details
$stmt = $pdo->query("
    SELECT 
        t.*,
        c.code as course_code, c.name as course_name,
        u.full_name as lecturer_name,
        r.name as room_name
    FROM timetable t
    JOIN courses c ON t.course_id = c.id
    JOIN lecturers l ON t.lecturer_id = l.id
    JOIN users u ON l.user_id = u.id
    JOIN rooms r ON t.room_id = r.id
    ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), t.start_time
");
$schedules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clock me-2"></i> Timetable</h2>
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Schedule</a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <?php if (count($schedules) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Course</th>
                                <th>Lecturer</th>
                                <th>Room</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $row): ?>
                            <tr>
                                <td><strong><?php echo $row['day_of_week']; ?></strong></td>
                                <td><?php echo date('h:i A', strtotime($row['start_time'])); ?> – <?php echo date('h:i A', strtotime($row['end_time'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['course_code']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($row['course_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['lecturer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['room_name']); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this schedule?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No timetable entries yet. <a href="add.php">Add one now</a>.</p>
            <?php endif; ?>
        </div>
    </div>
    <a href="../../dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
</div>
</body>
</html>