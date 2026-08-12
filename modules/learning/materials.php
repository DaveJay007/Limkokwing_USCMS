<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/config.php';

$error = '';
$success = '';

// Get courses for dropdown
$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY code")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_material'])) {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($course_id < 1 || empty($title)) {
        $error = 'Please select a course and provide a title.';
    } elseif (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = 'File type not allowed. Allowed: ' . implode(', ', $allowed);
        } else {
            // Create uploads directory if not exists
            $uploadDir = '../../uploads/materials/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = time() . '_' . basename($_FILES['file']['name']);
            $filepath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
                $stmt = $pdo->prepare("INSERT INTO materials (course_id, title, description, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$course_id, $title, $description, 'uploads/materials/' . $filename, $_SESSION['user_id']])) {
                    $success = 'Material uploaded successfully!';
                } else {
                    $error = 'Database error.';
                }
            } else {
                $error = 'Failed to move uploaded file.';
            }
        }
    } else {
        $error = 'Please select a file to upload.';
    }
}

// List materials
$materials = $pdo->query("
    SELECT m.*, c.code as course_code, c.name as course_name, u.full_name as uploaded_by_name
    FROM materials m
    JOIN courses c ON m.course_id = c.id
    JOIN users u ON m.uploaded_by = u.id
    ORDER BY m.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials — USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-alt me-2"></i> Course Materials</h2>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <!-- Upload Form -->
    <div class="card shadow p-4 mb-4">
        <h5><i class="fas fa-upload me-2"></i> Upload New Material</h5>
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-4">
                    <select name="course_id" class="form-select" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="title" class="form-control" placeholder="Title" required>
                </div>
                <div class="col-md-4">
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="col-md-12">
                    <textarea name="description" class="form-control" placeholder="Description (optional)" rows="2"></textarea>
                </div>
                <div class="col-md-12">
                    <button type="submit" name="upload_material" class="btn btn-primary"><i class="fas fa-upload me-1"></i> Upload</button>
                </div>
            </div>
        </form>
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success mt-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
    </div>

    <!-- List Materials -->
    <div class="card shadow">
        <div class="card-body">
            <h5>Uploaded Materials</h5>
            <?php if (count($materials) > 0): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Uploaded By</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['uploaded_by_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            <td><a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No materials uploaded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>