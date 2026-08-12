<?php
session_start();
require_once 'includes/config.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$reg_error = '';
$reg_success = '';

// ---- LOGIN HANDLING ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

// ---- REGISTRATION HANDLING ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $role_id   = (int)($_POST['role'] ?? 3);
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    if (empty($full_name) || empty($email) || empty($password)) {
        $reg_error = 'Full name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $reg_error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $reg_error = 'Password must be at least 6 characters.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $reg_error = 'This email is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
            if ($stmt->execute([$role_id, $full_name, $email, $phone, $hashed])) {
                $reg_success = 'Account created! You can now log in.';
                $_POST = [];
            } else {
                $reg_error = 'Registration failed.';
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
    <title>Limkokwing USCMS — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6c3bc9;
            --primary-dark: #4a1d8a;
            --primary-light: #8b6cd4;
            --secondary: #00b4d8;
            --accent: #f72585;
            --gradient-main: linear-gradient(135deg, #6c3bc9 0%, #00b4d8 100%);
            --shadow-lg: 0 16px 60px rgba(108, 59, 201, 0.25);
            --radius-lg: 28px;
            --radius-sm: 12px;
            --transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0eeff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: var(--gradient-main);
            position: relative;
            overflow: hidden;
        }
        .auth-wrapper::before,
        .auth-wrapper::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.35;
            z-index: 0;
        }
        .auth-wrapper::before {
            width: 600px;
            height: 600px;
            background: #f72585;
            top: -200px;
            right: -150px;
        }
        .auth-wrapper::after {
            width: 500px;
            height: 500px;
            background: #00b4d8;
            bottom: -180px;
            left: -120px;
        }
        .auth-card {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: var(--radius-lg);
            padding: 2.8rem 2.8rem 3.2rem;
            max-width: 540px;
            width: 100%;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
        }
        .auth-card:hover {
            box-shadow: 0 24px 80px rgba(108, 59, 201, 0.30);
            transform: translateY(-4px);
        }
        .auth-brand {
            text-align: center;
            margin-bottom: 2.2rem;
        }
        .auth-brand .logo-img {
            width: 80px;
            height: auto;
            margin-bottom: 0.75rem;
        }
        .auth-brand h1 {
            font-weight: 700;
            font-size: 1.9rem;
            letter-spacing: -0.02em;
            background: var(--gradient-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }
        .auth-brand .subtitle {
            color: #5b4a7a;
            font-size: 0.92rem;
            font-weight: 500;
        }
        .auth-brand .campus-name {
            font-size: 0.78rem;
            font-weight: 500;
            color: #7a6a99;
            background: rgba(108, 59, 201, 0.08);
            display: inline-block;
            padding: 0.2rem 1.2rem;
            border-radius: 40px;
            margin-top: 0.3rem;
        }
        .floating-nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.4rem 0.8rem;
            margin-bottom: 1.8rem;
            padding: 0.4rem 0.8rem;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            border-radius: 60px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .floating-nav a {
            font-size: 0.75rem;
            font-weight: 600;
            color: #4a3a6a;
            padding: 0.3rem 1rem;
            border-radius: 40px;
            text-decoration: none;
            transition: var(--transition);
            letter-spacing: 0.2px;
            cursor: pointer;
        }
        .floating-nav a:hover,
        .floating-nav a.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 20px rgba(108, 59, 201, 0.12);
        }
        .auth-card .form-control {
            border-radius: var(--radius-sm);
            padding: 0.75rem 1.1rem;
            border: 2px solid #e4def0;
            background: rgba(255, 255, 255, 0.7);
            font-size: 0.92rem;
            transition: var(--transition);
        }
        .auth-card .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 59, 201, 0.15);
            background: #fff;
        }
        .auth-card .form-label {
            font-weight: 600;
            font-size: 0.82rem;
            color: #3d2d5a;
        }
        .auth-card .btn-primary {
            background: var(--gradient-main);
            border: none;
            padding: 0.8rem 1.8rem;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1rem;
            transition: var(--transition);
            box-shadow: 0 4px 20px rgba(108, 59, 201, 0.12);
            width: 100%;
            color: #fff;
        }
        .auth-card .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 40px rgba(108, 59, 201, 0.18);
            filter: brightness(1.05);
        }
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #a094b8;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 1.2rem 0;
        }
        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent, #d5cee8, transparent);
        }
        .auth-footer {
            text-align: center;
            font-size: 0.88rem;
            color: #5b4a7a;
            margin-top: 1.5rem;
        }
        .auth-footer a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .auth-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        /* Role pills */
        .role-pills {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            justify-content: center;
            margin: 0.5rem 0 1.5rem;
        }
        .role-pill {
            flex: 1 1 auto;
            min-width: 80px;
            padding: 0.6rem 0.8rem;
            border-radius: 60px;
            border: 2px solid #d5cee8;
            background: transparent;
            font-size: 0.78rem;
            font-weight: 600;
            color: #4a3a6a;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        .role-pill i { margin-right: 4px; }
        .role-pill:hover {
            border-color: var(--primary-light);
            background: rgba(108, 59, 201, 0.06);
        }
        .role-pill.active {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 20px rgba(108, 59, 201, 0.12);
        }
        /* Toggle forms */
        .form-toggle { display: none; }
        .form-toggle.active { display: block; }
        @media (max-width: 768px) {
            .auth-card { padding: 2rem 1.5rem; }
            .floating-nav { gap: 0.3rem 0.5rem; padding: 0.3rem 0.6rem; }
            .floating-nav a { font-size: 0.65rem; padding: 0.2rem 0.7rem; }
        }
        @media (max-width: 576px) {
            .auth-card { padding: 1.5rem 1.2rem; border-radius: 20px; }
            .auth-brand h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">

        <!-- Brand with Logo -->
        <div class="auth-brand">
            <img src="C:/xampp/htdocs/uscms/assets/images/limkokwing-logo.png" alt="Limkokwing University" class="logo-img">
            <h1>Limkokwing USCMS</h1>
            <div class="subtitle">University Smart Campus</div>
            <div class="campus-name">
                <i class="fas fa-map-pin me-1"></i> Limkokwing University of Creative Technology
            </div>
        </div>

        <!-- Floating Nav (links to actual modules) -->
        <div class="floating-nav">
            <a href="modules/students/index.php" class="active"><i class="fas fa-users me-1"></i> Students</a>
            <a href="modules/lecturers/index.php"><i class="fas fa-chalkboard-teacher me-1"></i> Lecturers</a>
            <a href="modules/courses/index.php"><i class="fas fa-book-open me-1"></i> Courses</a>
            <a href="modules/attendance/index.php"><i class="fas fa-clipboard-check me-1"></i> Attendance</a>
            <a href="modules/timetable/index.php"><i class="fas fa-clock me-1"></i> Timetable</a>
            <a href="#" onclick="return modulePlaceholder(this)"><i class="fas fa-chart-bar me-1"></i> Analysis</a>
        </div>

        <!-- ======== LOGIN FORM ======== -->
        <div id="loginForm" class="form-toggle active">
            <h5 class="fw-bold text-center mb-3" style="color:#1d0d3a;">
                Welcome back
                <span style="display:block;font-size:0.85rem;font-weight:400;color:#6a5a8a;">
                    Sign in to your campus account
                </span>
            </h5>

            <form method="POST" action="index.php" autocomplete="off">
                <input type="hidden" name="login" value="1">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label"><i class="far fa-envelope me-1"></i> Email address</label>
                    <input type="email" class="form-control" name="email" placeholder="you@limkokwing.edu.sl" required>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-lock me-1"></i> Password</label>
                    <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label" for="remember" style="font-size:0.8rem;color:#5b4a7a;">Remember me</label>
                    </div>
                    <a href="#" style="font-size:0.8rem;color:var(--primary);font-weight:600;text-decoration:none;">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right me-2"></i> Sign in</button>
            </form>

            <div class="auth-divider">or</div>
            <div class="auth-footer">
                Don't have an account? <a id="showRegisterLink">Create one</a>
            </div>
        </div>

        <!-- ======== REGISTRATION FORM ======== -->
        <div id="registerForm" class="form-toggle">
            <h5 class="fw-bold text-center mb-2" style="color:#1d0d3a;">
                Create your account
                <span style="display:block;font-size:0.85rem;font-weight:400;color:#6a5a8a;">
                    Join the campus management system
                </span>
            </h5>

            <form method="POST" action="index.php" autocomplete="off">
                <input type="hidden" name="register" value="1">
                <input type="hidden" name="role" id="selectedRole" value="3">

                <label class="form-label text-center d-block" style="font-size:0.8rem;color:#5b4a7a;">I am registering as</label>
                <div class="role-pills">
                    <button type="button" class="role-pill active" data-role="student"><i class="fas fa-user-graduate"></i> Student</button>
                    <button type="button" class="role-pill" data-role="lecturer"><i class="fas fa-chalkboard-teacher"></i> Lecturer</button>
                    <button type="button" class="role-pill" data-role="admin"><i class="fas fa-user-cog"></i> Admin</button>
                </div>

                <?php if ($reg_error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($reg_error); ?></div>
                <?php endif; ?>
                <?php if ($reg_success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($reg_success); ?></div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label"><i class="far fa-user me-1"></i> Full name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Abu Bakarr Sesay" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="far fa-envelope me-1"></i> Email address *</label>
                    <input type="email" name="email" class="form-control" placeholder="you@limkokwing.edu.sl" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-phone me-1"></i> Phone</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+232 76 123 456" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-lock me-1"></i> Password * <span style="font-weight:400;font-size:0.7rem;color:#a094b8;">(min 6 chars)</span></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i> Create account</button>
            </form>

            <div class="auth-divider">or</div>
            <div class="auth-footer">
                Already have an account? <a id="showLoginLink">Sign in</a>
            </div>
        </div>

        <div class="text-center mt-3" style="font-size:0.7rem;color:#a094b8;">
            <i class="fas fa-info-circle me-1"></i> Demo: admin@limkokwing.edu.sl / password
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function() {
        // Toggle between forms
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const showRegister = document.getElementById('showRegisterLink');
        const showLogin = document.getElementById('showLoginLink');

        function toggleForm(show) {
            loginForm.classList.remove('active');
            registerForm.classList.remove('active');
            if (show === 'login') loginForm.classList.add('active');
            else registerForm.classList.add('active');
        }

        showRegister.addEventListener('click', function(e) {
            e.preventDefault();
            toggleForm('register');
        });
        showLogin.addEventListener('click', function(e) {
            e.preventDefault();
            toggleForm('login');
        });

        // Role pills
        const pills = document.querySelectorAll('.role-pill');
        const roleInput = document.getElementById('selectedRole');
        const roleMap = { student: 3, lecturer: 2, admin: 1 };

        pills.forEach(pill => {
            pill.addEventListener('click', function() {
                pills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                roleInput.value = roleMap[this.dataset.role] || 3;
            });
        });

        // Floating nav placeholder for "Analysis"
        window.modulePlaceholder = function(link) {
            alert('🔔 This module is under construction. Please check back later.');
            return false;
        };

        // If registration success, switch to login after 2 seconds
        <?php if ($reg_success): ?>
            setTimeout(function() {
                toggleForm('login');
                document.querySelectorAll('.alert-success').forEach(el => el.style.display = 'none');
            }, 2000);
        <?php endif; ?>

        // Preserve selected role if error occurred
        <?php if ($reg_error && isset($_POST['role'])): ?>
            var roleId = <?php echo (int)$_POST['role']; ?>;
            var reverseMap = {1:'admin', 2:'lecturer', 3:'student'};
            var key = reverseMap[roleId] || 'student';
            pills.forEach(p => p.classList.toggle('active', p.dataset.role === key));
            roleInput.value = roleId;
        <?php endif; ?>
    })();
</script>
</body>
</html>