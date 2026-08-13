<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/config.php';

// Fetch dynamic statistics
$studentCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$lecturerCount = $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
$courseCount   = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();

$attendanceStats = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
FROM attendance")->fetch();
$attendanceRate = ($attendanceStats['total'] > 0) 
    ? round(($attendanceStats['present'] / $attendanceStats['total']) * 100) 
    : 0;

$recentActivity = $pdo->query("
    SELECT 
        s.student_id,
        u.full_name as student_name,
        c.name as course_name,
        a.status,
        a.marked_at
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN attendance_sessions ats ON a.session_id = ats.id
    JOIN courses c ON ats.course_id = c.id
    ORDER BY a.marked_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Limkokwing USCMS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6c3bc9;
            --primary-dark: #4a1d8a;
            --primary-light: #8b6cd4;
            --secondary: #00b4d8;
            --accent: #f72585;
            --gradient-main: linear-gradient(135deg, #6c3bc9 0%, #00b4d8 100%);
            --shadow-sm: 0 4px 20px rgba(108, 59, 201, 0.12);
            --shadow-md: 0 8px 40px rgba(108, 59, 201, 0.18);
            --radius-md: 18px;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #f0eeff;
            padding: 1rem;
            transition: background 0.4s ease;
        }
        /* Color themes (applied via JS to body) */
        body.theme-black { background: #1a1a1a; }
        body.theme-black .topbar,
        body.theme-black .floating-nav,
        body.theme-black .panel-card,
        body.theme-black .stat-card { background: rgba(30,30,30,0.9); color: #eee; }
        body.theme-black .stat-content h4,
        body.theme-black .panel-card .card-title,
        body.theme-black .topbar-brand,
        body.theme-black .topbar-right .greeting { color: #eee; }
        body.theme-black .floating-nav a { color: #ccc; }
        body.theme-black .floating-nav a:hover,
        body.theme-black .floating-nav a.active { background: var(--primary); color: #fff; }

        body.theme-grey { background: #d0d0d0; }
        body.theme-sky { background: #b3e5fc; }
        body.theme-green { background: #2e7d32; }
        body.theme-green .topbar,
        body.theme-green .floating-nav,
        body.theme-green .panel-card,
        body.theme-green .stat-card { background: rgba(255,255,255,0.85); color: #000; }

        .dashboard-container { max-width: 1440px; margin: 0 auto; }

        /* Top Bar */
        .topbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-md);
            padding: 0.8rem 1.2rem;
            margin-bottom: 1.2rem;
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: var(--shadow-sm);
            gap: 0.5rem;
        }
        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 700;
            font-size: 1.2rem;
            color: #2d1d4a;
            flex-wrap: wrap;
        }
        .topbar-brand .logo-img {
            height: 36px;
            width: auto;
        }
        .topbar-brand .badge {
            background: var(--gradient-main);
            color: #fff;
            font-size: 0.6rem;
            padding: 0.2rem 0.7rem;
            border-radius: 40px;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }
        .topbar-right .greeting {
            font-weight: 500;
            font-size: 0.85rem;
            color: #3d2d5a;
        }
        .topbar-right .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--gradient-main);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
        }

        /* Floating Nav (scrollable) */
        .floating-nav {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 0.4rem 0.6rem;
            margin-bottom: 1.5rem;
            padding: 0.4rem 0.8rem;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            border-radius: 60px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .floating-nav::-webkit-scrollbar { display: none; }
        .floating-nav a {
            flex: 0 0 auto;
            font-size: 0.72rem;
            font-weight: 600;
            color: #4a3a6a;
            padding: 0.3rem 0.8rem;
            border-radius: 40px;
            text-decoration: none;
            transition: 0.3s;
            letter-spacing: 0.2px;
            cursor: pointer;
            white-space: nowrap;
        }
        .floating-nav a:hover,
        .floating-nav a.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 20px rgba(108, 59, 201, 0.12);
        }

        /* Stat Cards */
        .stat-card {
            background: #fff;
            border-radius: var(--radius-md);
            padding: 1.2rem 1rem;
            box-shadow: var(--shadow-sm);
            transition: 0.3s;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid rgba(255,255,255,0.6);
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
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
        .stat-icon.purple { background: var(--gradient-main); }
        .stat-icon.blue { background: linear-gradient(135deg, #00b4d8, #0077b6); }
        .stat-icon.pink { background: linear-gradient(135deg, #f72585, #b5179e); }
        .stat-icon.green { background: linear-gradient(135deg, #06d6a0, #059f8a); }
        .stat-content h4 { font-size: 1.4rem; font-weight: 800; margin: 0; color: #1d0d3a; }
        .stat-content p { margin: 0; font-size: 0.75rem; font-weight: 500; color: #6a5a8a; }
        .stat-content .trend {
            font-size: 0.65rem;
            font-weight: 600;
            color: #06d6a0;
            background: rgba(6,214,160,0.12);
            padding: 0.05rem 0.5rem;
            border-radius: 40px;
            display: inline-block;
            margin-top: 0.1rem;
        }

        /* Panel */
        .panel-card {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(8px);
            border-radius: var(--radius-md);
            padding: 1.2rem 1.2rem;
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: var(--shadow-sm);
            height: 100%;
        }
        .panel-card .card-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1d0d3a;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .table-usms {
            font-size: 0.8rem;
        }
        .table-usms thead th {
            background: rgba(108,59,201,0.06);
            font-weight: 700;
            color: #3d2d5a;
            border-bottom: 2px solid #e4def0;
            padding: 0.5rem 0.5rem;
            font-size: 0.75rem;
        }
        .table-usms tbody td {
            padding: 0.5rem 0.5rem;
            border-bottom: 1px solid #edeaf5;
            font-size: 0.8rem;
        }
        .badge-status {
            font-weight: 600;
            padding: 0.2rem 0.7rem;
            border-radius: 40px;
            font-size: 0.65rem;
        }
        .badge-status.present { background: rgba(6,214,160,0.15); color: #059f8a; }
        .badge-status.absent { background: rgba(247,37,133,0.10); color: #b5179e; }
        .badge-status.late { background: rgba(255,158,0,0.12); color: #f77f00; }

        /* Quick Actions */
        .quick-action {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.6rem 0.8rem;
            border-radius: 12px;
            background: rgba(108,59,201,0.04);
            transition: 0.3s;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            color: #2d1d4a;
            font-size: 0.85rem;
        }
        .quick-action:hover {
            background: rgba(108,59,201,0.08);
            border-color: #d5cee8;
            transform: translateX(4px);
        }
        .quick-action i {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: var(--gradient-main);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        /* Color Switcher */
        .color-switcher {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
            display: flex;
            gap: 8px;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(8px);
            padding: 8px 12px;
            border-radius: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .color-switcher .color-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.6);
            cursor: pointer;
            transition: 0.2s;
        }
        .color-switcher .color-btn:hover {
            transform: scale(1.15);
        }
        .color-switcher .color-btn.active {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px var(--primary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 0.5rem; }
            .topbar { flex-direction: column; align-items: stretch; gap: 0.6rem; padding: 0.8rem; }
            .topbar-brand { justify-content: center; }
            .topbar-right { justify-content: center; gap: 0.5rem; }
            .topbar-right .greeting { font-size: 0.8rem; }
            .floating-nav { border-radius: 30px; padding: 0.3rem 0.6rem; gap: 0.3rem 0.5rem; }
            .floating-nav a { font-size: 0.65rem; padding: 0.25rem 0.6rem; }
            .stat-card { padding: 0.8rem; gap: 0.8rem; }
            .stat-icon { width: 40px; height: 40px; font-size: 1.2rem; }
            .stat-content h4 { font-size: 1.2rem; }
            .stat-content p { font-size: 0.7rem; }
            .panel-card { padding: 0.8rem; }
            .table-usms { font-size: 0.7rem; }
            .table-usms thead th { font-size: 0.65rem; padding: 0.3rem; }
            .table-usms tbody td { font-size: 0.7rem; padding: 0.3rem; }
            .quick-action { font-size: 0.8rem; padding: 0.5rem 0.7rem; }
            .quick-action i { width: 28px; height: 28px; font-size: 0.8rem; }
            .color-switcher { bottom: 10px; right: 10px; padding: 6px 10px; gap: 6px; }
            .color-switcher .color-btn { width: 26px; height: 26px; }
        }
        @media (max-width: 576px) {
            .stat-card { flex-direction: column; text-align: center; }
            .stat-icon { width: 44px; height: 44px; font-size: 1.3rem; }
            .stat-content h4 { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

<div class="dashboard-container" id="mainContainer">

    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-brand">
            <img src="assets/images/limkokwing-logo.png" alt="Limkokwing University" class="logo-img">
            <span>Limkokwing USCMS</span>
            <span class="badge">v2.0</span>
            <span style="font-size:0.65rem;font-weight:400;color:#6a5a8a;margin-left:0.2rem;">
                Limkokwing University
            </span>
        </div>
        <div class="topbar-right">
            <span class="greeting">
                <i class="far fa-smile me-1"></i> Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
            </span>
            <div class="avatar" title="Profile"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?></div>
            <a href="logout.php" class="btn btn-outline-secondary btn-sm" style="border-radius:40px;padding:0.2rem 0.8rem;font-size:0.75rem;">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <!-- Floating Navigation (Role‑Based) -->
    <div class="floating-nav">
        <?php if ($_SESSION['role_id'] == 1): // Admin ?>
            <a href="modules/students/index.php" class="active"><i class="fas fa-users me-1"></i> Students</a>
            <a href="modules/lecturers/index.php"><i class="fas fa-chalkboard-teacher me-1"></i> Lecturers</a>
            <a href="modules/courses/index.php"><i class="fas fa-book-open me-1"></i> Courses</a>
            <a href="modules/attendance/index.php"><i class="fas fa-clipboard-check me-1"></i> Attendance</a>
            <a href="modules/timetable/index.php"><i class="fas fa-clock me-1"></i> Timetable</a>
            <a href="modules/academic_records/index.php"><i class="fas fa-graduation-cap me-1"></i> Academic</a>
            <a href="modules/learning/index.php"><i class="fas fa-book-open me-1"></i> Learning</a>
            <a href="modules/analytics/index.php"><i class="fas fa-chart-bar me-1"></i> Analysis</a>
            <a href="modules/super_admin/index.php"><i class="fas fa-user-shield me-1"></i> Super Admin</a>
        <?php elseif ($_SESSION['role_id'] == 2): // Lecturer ?>
            <a href="modules/students/index.php" class="active"><i class="fas fa-users me-1"></i> Students</a>
            <a href="modules/lecturers/index.php"><i class="fas fa-chalkboard-teacher me-1"></i> Lecturers</a>
            <a href="modules/courses/index.php"><i class="fas fa-book-open me-1"></i> Courses</a>
            <a href="modules/timetable/index.php"><i class="fas fa-clock me-1"></i> Timetable</a>
            <a href="modules/academic_records/index.php"><i class="fas fa-graduation-cap me-1"></i> Academic</a>
            <a href="modules/learning/index.php"><i class="fas fa-book-open me-1"></i> Learning</a>
            <a href="modules/analytics/index.php"><i class="fas fa-chart-bar me-1"></i> Analysis</a>
        <?php else: // Student ?>
            <a href="modules/courses/index.php" class="active"><i class="fas fa-book-open me-1"></i> Courses</a>
            <a href="modules/timetable/index.php"><i class="fas fa-clock me-1"></i> Timetable</a>
            <a href="modules/academic_records/index.php"><i class="fas fa-graduation-cap me-1"></i> Academic</a>
            <a href="modules/learning/index.php"><i class="fas fa-book-open me-1"></i> Learning</a>
            <a href="modules/analytics/index.php"><i class="fas fa-chart-bar me-1"></i> Analysis</a>
        <?php endif; ?>
    </div>

    <!-- Stats -->
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

    <!-- Main Grid -->
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="panel-card">
                <div class="card-title">
                    <span><i class="fas fa-clock me-2" style="color:var(--primary);"></i> Recent Activity</span>
                    <a href="#" style="font-size:0.7rem;font-weight:600;color:var(--primary);text-decoration:none;">View all <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-usms">
                        <thead><tr><th>Student</th><th>Course</th><th>Status</th><th>Time</th></tr></thead>
                        <tbody>
                            <?php if (count($recentActivity) > 0): ?>
                                <?php foreach ($recentActivity as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br><small><?php echo htmlspecialchars($row['student_id']); ?></small></td>
                                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                    <td><span class="badge-status <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><?php echo date('h:i A', strtotime($row['marked_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted">No recent activity yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <?php if ($_SESSION['role_id'] == 1): // Only Admin sees Quick Actions ?>
            <div class="panel-card">
                <div class="card-title"><span><i class="fas fa-bolt me-2" style="color:var(--primary);"></i> Quick Actions</span></div>
                <div class="d-flex flex-column gap-2">
                    <a href="modules/students/add.php" class="quick-action"><i class="fas fa-user-plus"></i><span>Register new student</span></a>
                    <a href="modules/attendance/create.php" class="quick-action"><i class="fas fa-calendar-plus"></i><span>Create attendance session</span></a>
                    <a href="#" class="quick-action" onclick="return modulePlaceholder(this)"><i class="fas fa-file-alt"></i><span>Generate report</span></a>
                    <a href="#" class="quick-action" onclick="return modulePlaceholder(this)"><i class="fas fa-bell"></i><span>Send announcement</span></a>
                    <a href="#" class="quick-action" onclick="return modulePlaceholder(this)"><i class="fas fa-upload"></i><span>Upload course material</span></a>
                </div>
            </div>
            <?php endif; ?>
            <div class="panel-card mt-3">
                <div class="card-title"><span><i class="fas fa-bell me-2" style="color:var(--primary);"></i> Notifications</span><span class="badge bg-primary rounded-pill">3</span></div>
                <ul class="list-unstyled" style="font-size:0.8rem;">
                    <li class="py-2 border-bottom border-light"><i class="fas fa-circle text-primary me-2" style="font-size:0.4rem;"></i> Assignment deadline: tomorrow 5pm</li>
                    <li class="py-2 border-bottom border-light"><i class="fas fa-circle text-primary me-2" style="font-size:0.4rem;"></i> New student enrolled in Graphic Design</li>
                    <li class="py-2"><i class="fas fa-circle text-primary me-2" style="font-size:0.4rem;"></i> System update scheduled for Sunday</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="text-center mt-4" style="font-size:0.7rem;color:#a094b8;">
        <i class="fas fa-copyright me-1"></i> 2026 Limkokwing USCMS — Limkokwing University of Creative Technology, Sierra Leone
        <span class="mx-2">•</span> Built with <i class="fas fa-heart" style="color:var(--accent);"></i>
    </div>
</div>

<!-- Color Switcher -->
<div class="color-switcher" id="colorSwitcher">
    <div class="color-btn" style="background: #f0eeff;" data-color="default" title="Default"></div>
    <div class="color-btn" style="background: #1a1a1a;" data-color="black" title="Black"></div>
    <div class="color-btn" style="background: #d0d0d0;" data-color="grey" title="Grey"></div>
    <div class="color-btn" style="background: #b3e5fc;" data-color="sky" title="Sky Blue"></div>
    <div class="color-btn" style="background: #2e7d32;" data-color="green" title="Bottle Green"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.modulePlaceholder = function(link) {
        alert('🔔 This module is under construction. Please check back later.');
        return false;
    };

    // Color switcher logic
    (function() {
        const body = document.body;
        const colorBtns = document.querySelectorAll('.color-btn');
        const currentTheme = localStorage.getItem('uscms_theme') || 'default';

        // Apply saved theme
        function applyTheme(theme) {
            body.classList.remove('theme-black', 'theme-grey', 'theme-sky', 'theme-green');
            if (theme !== 'default') {
                body.classList.add('theme-' + theme);
            }
            colorBtns.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.color === theme);
            });
            localStorage.setItem('uscms_theme', theme);
        }

        colorBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const color = this.dataset.color;
                applyTheme(color);
            });
        });

        applyTheme(currentTheme);
    })();
</script>
</body>
</html>