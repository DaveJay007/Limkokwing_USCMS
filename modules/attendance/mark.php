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

// Fetch all students (we'll later filter by course/programme)
$students = $pdo->query("
    SELECT s.id as student_id, s.student_id, u.full_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.status = 'active'
    ORDER BY u.full_name
")->fetchAll();

// Get existing attendance for this session
$existing = $pdo->prepare("SELECT student_id, status FROM attendance WHERE session_id = ?");
$existing->execute([$session_id]);
$attendanceMap = [];
while ($row = $existing->fetch()) {
    $attendanceMap[$row['student_id']] = $row['status'];
}

// Handle form submission (manual marking)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    // Get the attendance data
    $statuses = $_POST['status'] ?? [];
    // For each student, update or insert
    foreach ($students as $student) {
        $sid = $student['student_id'];
        $status = $statuses[$sid] ?? 'absent'; // default absent
        // Check if already exists
        $check = $pdo->prepare("SELECT id FROM attendance WHERE session_id = ? AND student_id = ?");
        $check->execute([$session_id, $sid]);
        if ($check->fetch()) {
            // Update
            $update = $pdo->prepare("UPDATE attendance SET status = ? WHERE session_id = ? AND student_id = ?");
            $update->execute([$status, $session_id, $sid]);
        } else {
            // Insert
            $insert = $pdo->prepare("INSERT INTO attendance (session_id, student_id, status) VALUES (?, ?, ?)");
            $insert->execute([$session_id, $sid, $status]);
        }
    }
    // Refresh the page to show updated statuses
    header("Location: mark.php?session_id=$session_id&updated=1");
    exit;
}

// Handle QR scan: if we get a token via GET
$qr_success = '';
$qr_error = '';
if (isset($_GET['qr_token'])) {
    $token = $_GET['qr_token'];
    // Verify token matches session's qr_code
    if ($token === $session['qr_code']) {
        // Mark the current logged-in user as present? But we need a student user.
        // For simplicity, we'll show a success message and let the user mark manually.
        // Actually, QR scan should be done via a mobile app or a separate page.
        // We'll just display a message.
        $qr_success = 'QR code verified! You can mark yourself present.';
    } else {
        $qr_error = 'Invalid QR code.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-pen me-2"></i> Mark Attendance</h2>
        <div>
            <a href="index.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Sessions</a>
            <a href="report.php?session_id=<?php echo $session_id; ?>" class="btn btn-info btn-sm"><i class="fas fa-chart-bar"></i> Report</a>
        </div>
    </div>

    <div class="card shadow p-3 mb-3">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Course:</strong> <?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($session['session_date'])); ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <?php if ($session['qr_code']): ?>
                    <button class="btn btn-outline-primary" onclick="showQR('<?php echo $session['qr_code']; ?>')">
                        <i class="fas fa-qrcode"></i> Show QR Code
                    </button>
                <?php endif; ?>
                <span class="badge bg-secondary ms-2">Session ID: <?php echo $session_id; ?></span>
            </div>
        </div>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success mt-2">Attendance updated successfully!</div>
        <?php endif; ?>
        <?php if ($qr_success): ?>
            <div class="alert alert-success mt-2"><?php echo $qr_success; ?></div>
        <?php endif; ?>
        <?php if ($qr_error): ?>
            <div class="alert alert-danger mt-2"><?php echo $qr_error; ?></div>
        <?php endif; ?>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="mark_attendance" value="1">
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td>
                                    <select name="status[<?php echo $student['student_id']; ?>]" class="form-select">
                                        <option value="present" <?php echo (($attendanceMap[$student['student_id']] ?? '') == 'present') ? 'selected' : ''; ?>>Present</option>
                                        <option value="absent" <?php echo (($attendanceMap[$student['student_id']] ?? '') == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                        <option value="late" <?php echo (($attendanceMap[$student['student_id']] ?? '') == 'late') ? 'selected' : ''; ?>>Late</option>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Attendance</button>
            </div>
        </div>
    </form>
    <a href="../../dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
</div>

<!-- QR Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="qrImage" src="" alt="QR Code" class="img-fluid">
                <p class="mt-2"><small>Scan to mark attendance (token: <span id="qrToken"></span>)</small></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showQR(token) {
        const img = document.getElementById('qrImage');
        img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(token);
        document.getElementById('qrToken').textContent = token;
        const modal = new bootstrap.Modal(document.getElementById('qrModal'));
        modal.show();
    }
</script>
</body>
</html>