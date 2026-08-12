<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Get all courses, lecturers, and rooms for dropdowns
$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY code")->fetchAll();
$lecturers = $pdo->query("
    SELECT l.id, u.full_name 
    FROM lecturers l 
    JOIN users u ON l.user_id = u.id 
    WHERE u.status = 'active'
    ORDER BY u.full_name
")->fetchAll();
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id   = (int)($_POST['course_id'] ?? 0);
    $lecturer_id = (int)($_POST['lecturer_id'] ?? 0);
    $room_id     = (int)($_POST['room_id'] ?? 0);
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time  = $_POST['start_time'] ?? '';
    $end_time    = $_POST['end_time'] ?? '';

    // Validate
    if ($course_id < 1 || $lecturer_id < 1 || $room_id < 1 || empty($day_of_week) || empty($start_time) || empty($end_time)) {
        $error = 'All fields are required.';
    } elseif ($start_time >= $end_time) {
        $error = 'End time must be after start time.';
    } else {
        // Check for conflicts: same room at same time
        $check_room = $pdo->prepare("
            SELECT id FROM timetable 
            WHERE room_id = ? AND day_of_week = ? 
            AND (
                (start_time <= ? AND end_time > ?) OR 
                (start_time < ? AND end_time >= ?) OR 
                (start_time >= ? AND end_time <= ?)
            )
        ");
        $check_room->execute([$room_id, $day_of_week, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
        if ($check_room->fetch()) {
            $error = 'This room is already booked at that time.';
        } else {
            // Check for lecturer conflict
            $check_lecturer = $pdo->prepare("
                SELECT id FROM timetable 
                WHERE lecturer_id = ? AND day_of_week = ? 
                AND (
                    (start_time <= ? AND end_time > ?) OR 
                    (start_time < ? AND end_time >= ?) OR 
                    (start_time >= ? AND end_time <= ?)
                )
            ");
            $check_lecturer->execute([$lecturer_id, $day_of_week, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
            if ($check_lecturer->fetch()) {
                $error = 'This lecturer is already booked at that time.';
            } else {
                // Insert
                $stmt = $pdo->prepare("
                    INSERT INTO timetable (course_id, lecturer_id, room_id, day_of_week, start_time, end_time)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                if ($stmt->execute([$course_id, $lecturer_id, $room_id, $day_of_week, $start_time, $end_time])) {
                    $success = 'Schedule added successfully!';
                    $_POST = [];
                } else {
                    $error = 'Failed to add schedule.';
                }
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
    <title>Add Timetable — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-plus-circle me-2"></i> Add Schedule</h2>
    <hr>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card shadow p-4">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course *</label>
                    <select name="course_id" class="form-select" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($_POST['course_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Lecturer *</label>
                    <select name="lecturer_id" class="form-select" required>
                        <option value="">Select Lecturer</option>
                        <?php foreach ($lecturers as $l): ?>
                            <option value="<?php echo $l['id']; ?>" <?php echo ($_POST['lecturer_id'] ?? '') == $l['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($l['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Room *</label>
                    <select name="room_id" class="form-select" required>
                        <option value="">Select Room</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo ($_POST['room_id'] ?? '') == $r['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Day of Week *</label>
                    <select name="day_of_week" class="form-select" required>
                        <option value="">Select Day</option>
                        <?php
                        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                        foreach ($days as $d):
                            $selected = ($_POST['day_of_week'] ?? '') == $d ? 'selected' : '';
                            echo "<option value=\"$d\" $selected>$d</option>";
                        endforeach;
                        ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Start Time *</label>
                    <input type="time" name="start_time" class="form-control" value="<?php echo htmlspecialchars($_POST['start_time'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">End Time *</label>
                    <input type="time" name="end_time" class="form-control" value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add Schedule</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <a href="../../dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
</div>
</body>
</html>