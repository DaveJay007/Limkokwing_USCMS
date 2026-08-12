<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/config.php';

$error = '';
$success = '';

// If grading a specific submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = (int)($_POST['submission_id'] ?? 0);
    $score = (float)($_POST['score'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');

    if ($submission_id < 1) {
        $error = 'Invalid submission.';
    } else {
        $stmt = $pdo->prepare("UPDATE submissions SET score = ?, feedback = ?, graded_by = ?, graded_at = NOW() WHERE id = ?");
        if ($stmt->execute([$score, $feedback, $_SESSION['user_id'], $submission_id])) {
            $success = 'Submission graded!';
        } else {
            $error = 'Update failed.';
        }
    }
}

// List submissions (ungraded first)
$submissions = $pdo->query("
    SELECT s.*, a.title as assignment_title, u.full_name as student_name, st.student_id as student_code
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN students st ON s.student_id = st.id
    JOIN users u ON st.user_id = u.id
    ORDER BY s.graded_at IS NULL DESC, s.submitted_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submissions — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-check-double me-2"></i> Grade Submissions</h2>
    <hr>
    <div class="card shadow p-4">
        <?php if (count($submissions) > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Assignment</th>
                        <th>Submitted</th>
                        <th>File</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_code'] . ' - ' . $row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['assignment_title']); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['submitted_at'])); ?></td>
                        <td><a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a></td>
                        <td><?php echo $row['score'] !== null ? number_format($row['score'], 1) : '—'; ?></td>
                        <td><?php echo $row['graded_at'] ? '<span class="badge bg-success">Graded</span>' : '<span class="badge bg-warning">Pending</span>'; ?></td>
                        <td>
                            <?php if (!$row['graded_at']): ?>
                                <!-- Grade form inline -->
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="submission_id" value="<?php echo $row['id']; ?>">
                                    <input type="number" step="0.1" name="score" class="form-control d-inline w-25" placeholder="Score" required>
                                    <input type="text" name="feedback" class="form-control d-inline w-25" placeholder="Feedback">
                                    <button type="submit" name="grade_submission" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No submissions yet.</p>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success mt-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
    </div>
    <a href="index.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>
</body>
</html>s