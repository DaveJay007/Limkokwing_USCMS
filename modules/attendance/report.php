<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if ($session_id < 1) {
    header('Location: index.php');
    exit;
}

// Fetch session details
$stmt = $pdo->prepare("
    SELECT s.*, c.name as course_name, c.code as course_code
    FROM attendance_sessions s
    JOIN courses c ON s.course_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch();
if (!$session) {
    header('Location: index.php');
    exit;
}

// Get all attendance records for this session with student details
$records = $pdo->prepare("
    SELECT a.status, s.student_id, u.full_name
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE a.session_id = ?
    ORDER BY u.full_name
");
$records->execute([$session_id]);
$attendanceData = $records->fetchAll();

// Calculate stats
$total = count($attendanceData);
$present = 0;
$absent = 0;
$late = 0;
foreach ($attendanceData as $row) {
    if ($row['status'] == 'present') $present++;
    elseif ($row['status'] == 'absent') $absent++;
    elseif ($row['status'] == 'late') $late++;
}
$percent = $total > 0 ? round(($present / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-chart-bar me-2"></i> Attendance Report</h2>

    <div class="card shadow p-3 mb-3">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Course:</strong> <?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($session['session_date'])); ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="badge bg-info">Total: <?php echo $total; ?></span>
                <span class="badge bg-success">Present: <?php echo $present; ?></span>
                <span class="badge bg-warning">Late: <?php echo $late; ?></span>
                <span class="badge bg-danger">Absent: <?php echo $absent; ?></span>
                <span class="badge bg-primary">Attendance: <?php echo $percent; ?>%</span>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($attendanceData) > 0): ?>
                            <?php foreach ($attendanceData as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($row['status'] == 'present') ? 'bg-success' : (($row['status'] == 'late') ? 'bg-warning' : 'bg-danger'); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center text-muted">No attendance records yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <a href="mark.php?session_id=<?php echo $session_id; ?>" class="btn btn-primary"><i class="fas fa-pen"></i> Mark Attendance</a>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Sessions</a>
        <a href="../../dashboard.php" class="btn btn-link">Dashboard</a>
    </div>
</div>
</body>
</html>