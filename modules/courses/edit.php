<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    header('Location: index.php');
    exit;
}

// Check if course exists
$stmt = $pdo->prepare("SELECT name FROM courses WHERE id = ?");
$stmt->execute([$id]);
$course = $stmt->fetch();
if (!$course) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    // Delete the course
    $delete = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    if ($delete->execute([$id])) {
        $success = "Course '{$course['name']}' deleted successfully.";
        // Redirect after 2 seconds
        header("Refresh: 2; url=index.php");
    } else {
        $error = 'Failed to delete course.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Course — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Confirm Deletion</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <p><a href="index.php" class="btn btn-primary">Go back to course list</a></p>
            <?php else: ?>
                <p>Are you sure you want to delete the course <strong>"<?php echo htmlspecialchars($course['name']); ?>"</strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
                <form method="POST" action="">
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Yes, Delete</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>