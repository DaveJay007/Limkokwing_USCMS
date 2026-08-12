<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

$student_id = (int)($_GET['student_id'] ?? 0);
if ($student_id < 1) {
    die('Student ID required.');
}

// Get student info
$stmt = $pdo->prepare("
    SELECT s.id, s.student_id, u.full_name, u.email, u.phone, s.programme, s.department
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
if (!$student) {
    die('Student not found.');
}

// Get all results for this student, grouped by semester/year
$results = $pdo->prepare("
    SELECT r.*, c.code, c.name as course_name, c.credits
    FROM results r
    JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
    ORDER BY r.academic_year DESC, FIELD(r.semester, 'Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6','Summer'), c.code
");
$results->execute([$student_id]);
$all_results = $results->fetchAll();

// Calculate overall CGPA (assuming 4.0 scale)
$total_points = 0;
$total_credits = 0;
foreach ($all_results as $r) {
    if ($r['grade_point'] !== null) {
        $total_points += $r['grade_point'] * $r['credits'];
        $total_credits += $r['credits'];
    }
}
$cgpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transcript — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none; }
            .card { border: none !important; box-shadow: none !important; }
            .container { max-width: 100% !important; }
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-scroll me-2"></i> Academic Transcript</h4>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h5><?php echo htmlspecialchars($student['full_name']); ?></h5>
                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                    <p><strong>Programme:</strong> <?php echo htmlspecialchars($student['programme']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($student['department']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <h4>CGPA: <span class="badge bg-success" style="font-size:1.5rem;"><?php echo number_format($cgpa, 2); ?></span></h4>
                </div>
            </div>

            <?php if (count($all_results) > 0): ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Code</th>
                            <th>Course</th>
                            <th>Semester</th>
                            <th>Year</th>
                            <th>Credits</th>
                            <th>Score</th>
                            <th>Grade</th>
                            <th>Grade Point</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_results as $row): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['semester']); ?></td>
                            <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                            <td><?php echo $row['credits']; ?></td>
                            <td><?php echo number_format($row['score'], 1); ?></td>
                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['grade'] ?? '-'); ?></span></td>
                            <td><?php echo number_format($row['grade_point'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-muted">No grades recorded for this student.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="text-center mt-3 no-print">
        <button onclick="window.print()" class="btn btn-success"><i class="fas fa-print me-1"></i> Print Transcript</button>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </div>
</div>
</body>
</html>