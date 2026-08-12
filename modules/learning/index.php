<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$role = $_SESSION['role_id'];
require_once '../../includes/config.php';

// Get all courses the user is involved with (if lecturer, only their courses; if student, all)
// For simplicity, we'll show all courses for now.
$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY code")->fetchAll();

// Also count materials, assignments, submissions for stats
$materialCount = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
$assignmentCount = $pdo->query("SELECT COUNT(*) FROM assignments")->fetchColumn();
$submissionCount = $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Management — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-book-open me-2"></i> Learning Management</h2>
    <hr>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h3><?php echo $materialCount; ?></h3>
                    <p class="text-muted">Materials</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h3><?php echo $assignmentCount; ?></h3>
                    <p class="text-muted">Assignments</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h3><?php echo $submissionCount; ?></h3>
                    <p class="text-muted">Submissions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick links -->
    <div class="row g-3">
        <div class="col-md-3">
            <a href="materials.php" class="btn btn-outline-primary w-100"><i class="fas fa-file-pdf me-1"></i> Manage Materials</a>
        </div>
        <div class="col-md-3">
            <a href="assignments.php" class="btn btn-outline-success w-100"><i class="fas fa-tasks me-1"></i> Assignments</a>
        </div>
        <?php if ($role == 3): // student ?>
        <div class="col-md-3">
            <a href="my_submissions.php" class="btn btn-outline-info w-100"><i class="fas fa-upload me-1"></i> My Submissions</a>
        </div>
        <?php endif; ?>
        <?php if ($role == 1 || $role == 2): // admin/lecturer ?>
        <div class="col-md-3">
            <a href="grade.php" class="btn btn-outline-warning w-100"><i class="fas fa-check-double me-1"></i> Grade Submissions</a>
        </div>
        <?php endif; ?>
    </div>
    <a href="../../dashboard.php" class="btn btn-secondary mt-4"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
</div>
</body>
</html>