<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Fetch all attendance with student/course details
$stmt = $pdo->query("
    SELECT 
        u.full_name as student_name,
        s.student_id,
        c.code as course_code,
        c.name as course_name,
        a.status,
        a.marked_at
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN attendance_sessions ats ON a.session_id = ats.id
    JOIN courses c ON ats.course_id = c.id
    ORDER BY a.marked_at DESC
    LIMIT 200
");
$records = $stmt->fetchAll();

// Summary stats
$total = count($records);
$present = array_count_values(array_column($records, 'status'))['present'] ?? 0;
$absent = array_count_values(array_column($records, 'status'))['absent'] ?? 0;
$late = array_count_values(array_column($records, 'status'))['late'] ?? 0;
$rate = $total > 0 ? round(($present / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report — Limkokwing USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; padding: 0; }
            .card { border: none !important; box-shadow: none !important; }
        }
        body {
            background: #f0eeff;
            font-family: 'Inter', -apple-system, sans-serif;
            padding: 2rem;
        }
        .report-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .report-header h2 { font-weight: 700; color: #1d0d3a; }
        .report-header .sub { color: #6a5a8a; }
        .summary-card {
            background: #fff;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 20px rgba(108,59,201,0.08);
            text-align: center;
        }
        .summary-card h3 { font-weight: 800; color: #1d0d3a; margin: 0; }
        .summary-card p { margin: 0; font-size: 0.8rem; color: #6a5a8a; }
        .table-sm-custom { font-size: 0.8rem; }
        .badge-status {
            font-weight: 600;
            padding: 0.2rem 0.7rem;
            border-radius: 40px;
            font-size: 0.7rem;
        }
        .badge-status.present { background: rgba(6,214,160,0.15); color: #059f8a; }
        .badge-status.absent { background: rgba(247,37,133,0.10); color: #b5179e; }
        .badge-status.late { background: rgba(255,158,0,0.12); color: #f77f00; }
        .footer-text { font-size: 0.7rem; color: #a094b8; margin-top: 2rem; }
    </style>
</head>
<body>

<div class="container">

    <!-- Header -->
    <div class="report-header">
        <h2><i class="fas fa-clipboard-list me-2"></i> Attendance Report</h2>
        <p class="sub">Limkokwing University of Creative Technology</p>
        <p class="sub">Generated: <?php echo date('d M Y, h:i A'); ?></p>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="summary-card">
                <h3><?php echo $total; ?></h3>
                <p>Total Marks</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="summary-card">
                <h3><?php echo $present; ?></h3>
                <p>Present</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="summary-card">
                <h3><?php echo $absent; ?></h3>
                <p>Absent</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="summary-card">
                <h3><?php echo $rate; ?>%</h3>
                <p>Attendance Rate</p>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <table class="table table-sm-custom table-hover">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Date/Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($records) > 0): ?>
                        <?php foreach ($records as $row): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br><small><?php echo htmlspecialchars($row['student_id']); ?></small></td>
                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                            <td><span class="badge-status <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td><?php echo date('d M Y h:i A', strtotime($row['marked_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">No attendance records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer-text text-center">
        <i class="fas fa-copyright me-1"></i> 2026 Limkokwing USCMS — This report is for internal use only.
    </div>

    <!-- Print button -->
    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i> Print / Save as PDF</button>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Back</a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>