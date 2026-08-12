<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name   = trim($_POST['full_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $student_id  = trim($_POST['student_id'] ?? '');
    $programme   = trim($_POST['programme'] ?? '');
    $department  = trim($_POST['department'] ?? '');
    $password    = trim($_POST['password'] ?? '');

    // Validate
    if (empty($full_name) || empty($email) || empty($student_id) || empty($password)) {
        $error = 'Full name, email, student ID, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email or student ID already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? UNION SELECT id FROM students WHERE student_id = ?");
        $check->execute([$email, $student_id]);
        if ($check->fetch()) {
            $error = 'Email or Student ID already exists.';
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Insert user (role = 3 for student)
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, phone, password_hash, status) VALUES (3, ?, ?, ?, ?, 'active')");
                $stmt->execute([$full_name, $email, $phone, $hashed]);
                $user_id = $pdo->lastInsertId();

                // 2. Insert student record
                $stmt2 = $pdo->prepare("INSERT INTO students (user_id, student_id, programme, department, status) VALUES (?, ?, ?, ?, 'active')");
                $stmt2->execute([$user_id, $student_id, $programme, $department]);

                $pdo->commit();
                $success = "Student '$full_name' added successfully!";
                // Clear form fields on success
                $_POST = [];
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
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
    <title>Add Student — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-user-plus me-2"></i> Register New Student</h2>
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
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Student ID *</label>
                    <input type="text" name="student_id" class="form-control" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Programme</label>
                    <input type="text" name="programme" class="form-control" value="<?php echo htmlspecialchars($_POST['programme'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Password * (min 6 characters)</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add Student</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <a href="../../dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
</div>
</body>
</html>