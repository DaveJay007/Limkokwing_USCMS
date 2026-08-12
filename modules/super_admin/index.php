<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Handle user status/role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $status = $_POST['status'] ?? 'active';
    $role_id = (int)($_POST['role_id'] ?? 0);

    if ($user_id > 0 && $role_id > 0) {
        $stmt = $pdo->prepare("UPDATE users SET status = ?, role_id = ? WHERE id = ?");
        $stmt->execute([$status, $role_id, $user_id]);
        
        // Log the action
        $log = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $log->execute([$_SESSION['user_id'], 'Updated User', "User ID $user_id status=$status role_id=$role_id", $_SERVER['REMOTE_ADDR']]);
        
        $success = 'User updated successfully!';
    }
}

// Get all users with role and student/lecturer details
$users = $pdo->query("
    SELECT 
        u.*,
        r.name as role_name,
        s.student_id,
        l.staff_id
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN students s ON u.id = s.user_id
    LEFT JOIN lecturers l ON u.id = l.user_id
    ORDER BY u.created_at DESC
")->fetchAll();

// Get all roles for dropdown
$roles = $pdo->query("SELECT id, name FROM roles ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — Limkokwing USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-shield me-2"></i> Super Admin — User Management</h2>
        <div>
            <a href="audit_logs.php" class="btn btn-outline-secondary me-2"><i class="fas fa-history me-1"></i> Audit Logs</a>
            <a href="../../dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Student/Lecturer ID</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['role_name']); ?></span></td>
                            <td>
                                <?php 
                                    if ($row['role_id'] == 3) echo htmlspecialchars($row['student_id'] ?? 'N/A');
                                    elseif ($row['role_id'] == 2) echo htmlspecialchars($row['staff_id'] ?? 'N/A');
                                    else echo '-';
                                ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $row['status'] == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['id'] != $_SESSION['user_id']): // prevent self-modification ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <select name="role_id" class="form-select form-select-sm d-inline w-auto">
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?php echo $r['id']; ?>" <?php echo $r['id'] == $row['role_id'] ? 'selected' : ''; ?>>
                                                <?php echo $r['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="status" class="form-select form-select-sm d-inline w-auto">
                                        <option value="active" <?php echo $row['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $row['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <button type="submit" name="update_user" class="btn btn-sm btn-primary"><i class="fas fa-save"></i></button>
                                </form>
                                <?php else: ?>
                                    <span class="text-muted">(self)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>