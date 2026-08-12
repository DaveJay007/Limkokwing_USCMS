<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Get all lecturers for dropdown
$lecturers = $pdo->query("
    SELECT l.id, u.full_name 
    FROM lecturers l 
    JOIN users u ON l.user_id = u.id 
    WHERE u.status = 'active'
    ORDER BY u.full_name
")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code        = trim($_POST['code'] ?? '');
    $name        = trim($_POST['name'] ?? '');
    $credits     = (int)($_POST['credits'] ?? 0);
    $semester    = trim($_POST['semester'] ?? '');
    $lecturer_id = (int)($_POST['lecturer_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if (empty($code) || empty($name) || $credits < 1 || empty($semester)) {
        $error = 'Code, name, credits, and semester are required.';
    } else {
        // Check if code already exists
        $check = $pdo->prepare("SELECT id FROM courses WHERE code = ?");
        $check->execute([$code]);
        if ($check->fetch()) {
            $error = 'Course code already exists. Please use a unique code.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO courses (code, name, credits, semester, lecturer_id, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$code, $name, $credits, $semester, $lecturer_id ?: null, $description])) {
                $success = "Course '$name' added successfully!";
                $_POST = [];
            } else {
                $error = 'Failed to add course. Please try again.';
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
    <title>Add Course — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-plus-circle me-2"></i> Add New Course</h2>
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
                    <label class="form-label">Course Code *</label>
                    <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>" required placeholder="e.g. CS301">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course Name *</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required placeholder="e.g. Database Systems">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Credits *</label>
                    <input type="number" name="credits" class="form-control" value="<?php echo htmlspecialchars($_POST['credits'] ?? ''); ?>" required min="1">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Semester *</label>
                    <select name="semester" class="form-select" required>
                        <option value="">Select Semester</option>
                        <?php
                        $semesters = ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6', 'Summer'];
                        foreach ($semesters as $s) {
                            $selected = ($_POST['semester'] ?? '') == $s ? 'selected' : '';
                            echo "<option value=\"$s\" $selected>$s</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Lecturer (Optional)</label>
                    <select name="lecturer_id" class="form-select">
                        <option value="">Select Lecturer</option>
                        <?php foreach ($lecturers as $l): ?>
                            <option value="<?php echo $l['id']; ?>" <?php echo ($_POST['lecturer_id'] ?? '') == $l['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($l['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add Course</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <a href="../../dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
</div>
</body>
</html>