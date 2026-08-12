<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Fetch all sessions with course name
$stmt = $pdo->query("
    SELECT s.*, c.name as course_name, c.code as course_code,
           (SELECT COUNT(*) FROM attendance WHERE session_id = s.id) as total_marks,
           (SELECT COUNT(*) FROM attendance WHERE session_id = s.id AND status = 'present') as present_count
    FROM attendance_sessions s
    JOIN courses c ON s.course_id = c.id
    ORDER BY s.session_date DESC, s.created_at DESC
");
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Sessions — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clipboard-check me-2"></i> Attendance Sessions</h2>
        <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Session</a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Date</th>
                            <th>QR Code</th>
                            <th>Attendance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($sessions) > 0): ?>
                            <?php foreach ($sessions as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['course_code']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($row['course_name']); ?></small>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['session_date'])); ?></td>
                                <td>
                                    <?php if ($row['qr_code']): ?>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="showQR('<?php echo $row['qr_code']; ?>')">
                                            <i class="fas fa-qrcode"></i> View QR
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">No QR</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $total = $row['total_marks'];
                                        $present = $row['present_count'];
                                        $percent = $total > 0 ? round(($present / $total) * 100) : 0;
                                    ?>
                                    <?php echo $present; ?>/<?php echo $total; ?> (<?php echo $percent; ?>%)
                                </td>
                                <td>
                                    <a href="mark.php?session_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-pen"></i> Mark</a>
                                    <a href="report.php?session_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-chart-bar"></i> Report</a>
                                    <a href="delete.php?session_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this session?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">No attendance sessions yet. <a href="create.php">Create one</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <a href="../../dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
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
                <p class="mt-2"><small>Scan to mark attendance</small></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showQR(token) {
        const img = document.getElementById('qrImage');
        img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(token);
        const modal = new bootstrap.Modal(document.getElementById('qrModal'));
        modal.show();
    }
</script>
</body>
</html>