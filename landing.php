<?php
session_start();
// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome — Limkokwing USCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6c3bc9;
            --gradient-main: linear-gradient(135deg, #6c3bc9 0%, #00b4d8 100%);
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #f0eeff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .landing-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-main);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        .landing-wrapper::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: #f72585;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.3;
            top: -200px;
            right: -150px;
        }
        .landing-wrapper::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: #00b4d8;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.3;
            bottom: -180px;
            left: -120px;
        }
        .landing-card {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 3rem 3.5rem;
            max-width: 700px;
            width: 100%;
            text-align: center;
            box-shadow: 0 24px 80px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .landing-card .logo-img {
            width: 80px;
            height: auto;
            margin-bottom: 1rem;
        }
        .landing-card h1 {
            font-weight: 700;
            font-size: 2.5rem;
            background: var(--gradient-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .landing-card .subtitle {
            color: #5b4a7a;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .landing-card .campus-name {
            color: #7a6a99;
            font-size: 0.9rem;
            background: rgba(108,59,201,0.08);
            display: inline-block;
            padding: 0.3rem 1.5rem;
            border-radius: 40px;
            margin-bottom: 1.5rem;
        }
        .landing-card .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 2rem 0;
            text-align: center;
        }
        .landing-card .features .feature {
            padding: 0.8rem;
            background: rgba(108,59,201,0.04);
            border-radius: 16px;
            border: 1px solid rgba(108,59,201,0.08);
        }
        .landing-card .features .feature i {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 0.3rem;
        }
        .landing-card .features .feature span {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #3d2d5a;
        }
        .landing-card .btn-primary {
            background: var(--gradient-main);
            border: none;
            padding: 0.8rem 3rem;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            transition: 0.3s;
        }
        .landing-card .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(108,59,201,0.3);
        }
        @media (max-width: 600px) {
            .landing-card { padding: 2rem 1.5rem; }
            .landing-card .features { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
<div class="landing-wrapper">
    <div class="landing-card">
        <img src="assets/images/limkokwing-logo.png" alt="Limkokwing" class="logo-img">
        <h1>Limkokwing USCMS</h1>
        <div class="subtitle">University Smart Campus Management System</div>
        <div class="campus-name">
            <i class="fas fa-map-pin me-1"></i> Limkokwing University of Creative Technology
        </div>

        <div class="features">
            <div class="feature">
                <i class="fas fa-users"></i>
                <span>Student Management</span>
            </div>
            <div class="feature">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Lecturer Management</span>
            </div>
            <div class="feature">
                <i class="fas fa-book-open"></i>
                <span>Course Management</span>
            </div>
            <div class="feature">
                <i class="fas fa-clipboard-check"></i>
                <span>Attendance</span>
            </div>
            <div class="feature">
                <i class="fas fa-clock"></i>
                <span>Timetable</span>
            </div>
            <div class="feature">
                <i class="fas fa-graduation-cap"></i>
                <span>Academic Records</span>
            </div>
        </div>

        <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-right me-2"></i> Get Started</a>
        <p style="margin-top:1rem;font-size:0.8rem;color:#a094b8;">Already have an account? <a href="index.php" style="color:var(--primary);font-weight:600;">Sign in</a></p>
    </div>
</div>
</body>
</html>