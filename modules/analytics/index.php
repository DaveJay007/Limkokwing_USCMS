<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Fetch stats for cards
$studentCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$lecturerCount = $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
$courseCount   = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();

// Attendance rate
$attendanceStats = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
FROM attendance")->fetch();
$attendanceRate = ($attendanceStats['total'] > 0) 
    ? round(($attendanceStats['present'] / $attendanceStats['total']) * 100) 
    : 0;

// Data for charts
// 1. Attendance by course (per course attendance percentage)
$attendanceByCourse = $pdo->query("
    SELECT 
        c.code,
        c.name,
        COUNT(a.id) as total_marks,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count
    FROM courses c
    LEFT JOIN attendance_sessions ats ON c.id = ats.course_id
    LEFT JOIN attendance a ON ats.id = a.session_id
    GROUP BY c.id
    HAVING total_marks > 0
    ORDER BY c.code
")->fetchAll();

// 2. Student distribution by programme
$studentByProgramme = $pdo->query("
    SELECT programme, COUNT(*) as count
    FROM students
    WHERE programme IS NOT NULL AND programme != ''
    GROUP BY programme
    ORDER BY count DESC
    LIMIT 6
")->fetchAll();

// 3. Recent attendance activity (last 10)
$recentAttendance = $pdo->query("
    SELECT 
        u.full_name as student_name,
        s.student_id,
        c.code as course_code,
        a.status,
        a.marked_at
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN attendance_sessions ats ON a.session_id = ats.id
    JOIN courses c ON ats.course_id = c.id
    ORDER BY a.marked_at DESC
    LIMIT 10
")->fetchAll();

// 4. Monthly attendance trend (last 6 months)
$monthlyTrend = $pdo->query("
    SELECT 
        DATE_FORMAT(marked_at, '%b %Y') as month,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
    FROM attendance
    WHERE marked_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(marked_at), MONTH(marked_at)
    ORDER BY marked_at ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics — Limkokwing USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body {
            background: #f0eeff;
            font-family: 'Inter', -apple-system, sans-serif;
            padding: 1rem;
        }
        .dashboard-container {
            max-width: 1440px;
            margin: 0 auto;
        }
        .stat-card {
            background: #fff;
            border-radius: 18px;
            padding: 1.2rem 1.2rem;
            box-shadow: 0 4px 20px rgba(108, 59, 201, 0.08);
            height: 100%;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid rgba(255,255,255,0.6);
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 40px rgba(108, 59, 201, 0.15); }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            flex-shrink: 0;
        }
        .stat-icon.purple { background: linear-gradient(135deg, #6c3bc9, #8b6cd4); }
        .stat-icon.blue { background: linear-gradient(135deg, #00b4d8, #0077b6); }
        .stat-icon.pink { background: linear-gradient(135deg, #f72585, #b5179e); }
        .stat-icon.green { background: linear-gradient(135deg, #06d6a0, #059f8a); }
        .stat-content h4 { font-size: 1.5rem; font-weight: 800; margin: 0; color: #1d0d3a; }
        .stat-content p { margin: 0; font-size: 0.8rem; font-weight: 500; color: #6a5a8a; }
        .stat-content .trend {
            font-size: 0.7rem;
            font-weight: 600;
            color: #06d6a0;
            background: rgba(6,214,160,0.12);
            padding: 0.05rem 0.6rem;
            border-radius: 40px;
            display: inline-block;
            margin-top: 0.1rem;
        }
        .panel-card {
            background: #fff;
            border-radius: 18px;
            padding: 1.2rem 1.2rem;
            box-shadow: 0 4px 20px rgba(108, 59, 201, 0.08);
            height: 100%;
            border: 1px solid rgba(255,255,255,0.5);
        }
        .panel-card .card-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1d0d3a;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chart-container {
            position: relative;
            height: 200px;
        }
        .chart-container.tall {
            height: 250px;
        }
        .table-sm-custom {
            font-size: 0.8rem;
        }
        .table-sm-custom th {
            background: rgba(108,59,201,0.06);
            font-weight: 600;
        }
        .badge-status {
            font-weight: 600;
            padding: 0.2rem 0.7rem;
            border-radius: 40px;
            font-size: 0.7rem;
        }
        .badge-status.present { background: rgba(6,214,160,0.15); color: #059f8a; }
        .badge-status.absent { background: rgba(247,37,133,0.10); color: #b5179e; }
        .badge-status.late { background: rgba(255,158,0,0.12); color: #f77f00; }
        .btn-export {
            border-radius: 40px;
            padding: 0.3rem 1.2rem;
            font-size: 0.8rem;
        }
        @media (max-width: 768px) {
            .chart-container { height: 150px; }
            .chart-container.tall { height: 180px; }
            .stat-card { padding: 0.8rem; }
            .stat-content h4 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-bar me-2" style="color:var(--primary);"></i> Reports & Analytics</h2>
        <div>
            <a href="export_students.php" class="btn btn-success btn-export me-2"><i class="fas fa-file-csv me-1"></i> Export CSV</a>
            <a href="report_attendance.php" class="btn btn-primary btn-export"><i class="fas fa-file-pdf me-1"></i> PDF Report</a>
            <a href="../../dashboard.php" class="btn btn-secondary btn-export ms-2"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6 col-6">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                <div class="stat-content">
                    <h4><?php echo number_format($studentCount); ?></h4>
                    <p>Total Students</p>
                    <span class="trend"><i class="fas fa-arrow-up me-1"></i> +12%</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-6">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-content">
                    <h4><?php echo number_format($lecturerCount); ?></h4>
                    <p>Lecturers</p>
                    <span class="trend"><i class="fas fa-arrow-up me-1"></i> +4%</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-6">
            <div class="stat-card">
                <div class="stat-icon pink"><i class="fas fa-book-open"></i></div>
                <div class="stat-content">
                    <h4><?php echo number_format($courseCount); ?></h4>
                    <p>Active Courses</p>
                    <span class="trend"><i class="fas fa-arrow-up me-1"></i> +8%</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-6">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-clipboard-check"></i></div>
                <div class="stat-content">
                    <h4><?php echo $attendanceRate; ?>%</h4>
                    <p>Attendance Rate</p>
                    <span class="trend"><i class="fas fa-arrow-up me-1"></i> +2.3%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">

        <!-- Chart 1: Attendance by Course -->
        <div class="col-lg-6">
            <div class="panel-card">
                <div class="card-title">
                    <span><i class="fas fa-chart-pie me-2" style="color:var(--primary);"></i> Attendance by Course</span>
                </div>
                <div class="chart-container tall">
                    <canvas id="attendanceByCourseChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Student Distribution by Programme -->
        <div class="col-lg-6">
            <div class="panel-card">
                <div class="card-title">
                    <span><i class="fas fa-chart-bar me-2" style="color:var(--primary);"></i> Students by Programme</span>
                </div>
                <div class="chart-container tall">
                    <canvas id="studentProgrammeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 3: Monthly Attendance Trend -->
        <div class="col-lg-12">
            <div class="panel-card">
                <div class="card-title">
                    <span><i class="fas fa-chart-line me-2" style="color:var(--primary);"></i> Monthly Attendance Trend (Last 6 Months)</span>
                </div>
                <div class="chart-container" style="height: 180px;">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance Table -->
    <div class="panel-card">
        <div class="card-title">
            <span><i class="fas fa-clock me-2" style="color:var(--primary);"></i> Recent Attendance Activity</span>
            <span class="badge bg-primary rounded-pill"><?php echo count($recentAttendance); ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm-custom table-hover">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentAttendance) > 0): ?>
                        <?php foreach ($recentAttendance as $row): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br><small><?php echo htmlspecialchars($row['student_id']); ?></small></td>
                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                            <td><span class="badge-status <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td><?php echo date('d M Y h:i A', strtotime($row['marked_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">No attendance records yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-4" style="font-size:0.7rem;color:#a094b8;">
        <i class="fas fa-copyright me-1"></i> 2026 Limkokwing USCMS — Limkokwing University of Creative Technology
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ---- Chart 1: Attendance by Course (Bar) ----
        const ctx1 = document.getElementById('attendanceByCourseChart').getContext('2d');
        const labels1 = <?php echo json_encode(array_column($attendanceByCourse, 'code')); ?>;
        const presentData = <?php echo json_encode(array_column($attendanceByCourse, 'present_count')); ?>;
        const totalData = <?php echo json_encode(array_column($attendanceByCourse, 'total_marks')); ?>;

        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: labels1,
                datasets: [
                    {
                        label: 'Present',
                        data: presentData,
                        backgroundColor: 'rgba(6, 214, 160, 0.7)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Total Marks',
                        data: totalData,
                        backgroundColor: 'rgba(108, 59, 201, 0.4)',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // ---- Chart 2: Students by Programme (Pie) ----
        const ctx2 = document.getElementById('studentProgrammeChart').getContext('2d');
        const labels2 = <?php echo json_encode(array_column($studentByProgramme, 'programme')); ?>;
        const counts2 = <?php echo json_encode(array_column($studentByProgramme, 'count')); ?>;
        const colors = ['#6c3bc9', '#00b4d8', '#f72585', '#06d6a0', '#ff9e00', '#b5179e'];

        new Chart(ctx2, {
            type: 'pie',
            data: {
                labels: labels2,
                datasets: [{
                    data: counts2,
                    backgroundColor: colors.slice(0, labels2.length),
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'right' }
                }
            }
        });

        // ---- Chart 3: Monthly Attendance Trend (Line) ----
        const ctx3 = document.getElementById('monthlyTrendChart').getContext('2d');
        const labels3 = <?php echo json_encode(array_column($monthlyTrend, 'month')); ?>;
        const present3 = <?php echo json_encode(array_column($monthlyTrend, 'present')); ?>;
        const total3 = <?php echo json_encode(array_column($monthlyTrend, 'total')); ?>;

        new Chart(ctx3, {
            type: 'line',
            data: {
                labels: labels3,
                datasets: [
                    {
                        label: 'Present',
                        data: present3,
                        borderColor: '#06d6a0',
                        backgroundColor: 'rgba(6, 214, 160, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#06d6a0',
                    },
                    {
                        label: 'Total',
                        data: total3,
                        borderColor: '#6c3bc9',
                        backgroundColor: 'rgba(108, 59, 201, 0.05)',
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#6c3bc9',
                        borderDash: [5, 5],
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

    });
</script>
</body>
</html>