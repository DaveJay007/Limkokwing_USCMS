<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Get all courses for dropdown
$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY code")->fetchAll();

$error = '';
$success = '';
$qr_data = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $session_date = $_POST['session_date'] ?? date('Y-m-d');

    if ($course_id < 1) {
        $error = 'Please select a course.';
    } else {
        // Generate unique token
        $token = bin2hex(random_bytes(16));
        $check = $pdo->prepare("SELECT id FROM attendance_sessions WHERE qr_code = ?");
        $check->execute([$token]);
        if ($check->fetch()) {
            $error = 'Token collision, please try again.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance_sessions (course_id, session_date, qr_code) VALUES (?, ?, ?)");
            if ($stmt->execute([$course_id, $session_date, $token])) {
                $session_id = $pdo->lastInsertId();
                // 🔥 REPLACE WITH YOUR NGROK PUBLIC URL
                $public_url = 'https://abc123.ngrok.io/Limbkoking_USCMS/scan_attendance.php?token=' . $token;
                $qr_image = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($public_url);
                $success = "Attendance session created!";
                $qr_data = [
                    'image' => $qr_image,
                    'url' => $public_url,
                    'token' => $token
                ];
            } else {
                $error = 'Failed to create session.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Attendance Session — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-qrcode me-2"></i> Create Attendance Session</h2>
    <hr>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <div class="card shadow p-4 text-center">
            <h5>Scan this QR code to mark attendance</h5>
            <img src="<?php echo $qr_data['image']; ?>" alt="QR Code" class="img-fluid" style="max-width:300px;margin:0 auto;">
            <div class="mt-3">
                <p><strong>URL:</strong> <a href="<?php echo $qr_data['url']; ?>" target="_blank"><?php echo $qr_data['url']; ?></a></p>
                <p><strong>Token:</strong> <code><?php echo $qr_data['token']; ?></code></p>
            </div>
            <a href="index.php" class="btn btn-secondary mt-2">Back to List</a>
        </div>
    <?php else: ?>
        <div class="card shadow p-4">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Select Course *</label>
                    <select name="course_id" class="form-select" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($_POST['course_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="session_date" class="form-control" value="<?php echo htmlspecialchars($_POST['session_date'] ?? date('Y-m-d')); ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-qrcode me-1"></i> Generate QR Code</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    <?php endif; ?>
    <a href="../../dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
</div>
</body>
</html>