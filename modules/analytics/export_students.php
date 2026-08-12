<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/config.php';

// Fetch all students
$stmt = $pdo->query("
    SELECT s.student_id, u.full_name, u.email, u.phone, s.programme, s.department, s.status
    FROM students s
    JOIN users u ON s.user_id = u.id
    ORDER BY u.full_name
");
$students = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Student ID', 'Full Name', 'Email', 'Phone', 'Programme', 'Department', 'Status']);

foreach ($students as $row) {
    fputcsv($output, [
        $row['student_id'],
        $row['full_name'],
        $row['email'],
        $row['phone'],
        $row['programme'],
        $row['department'],
        $row['status']
    ]);
}

fclose($output);
exit;