<?php
// api/auth.php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/jwt_helper.php';
require_once '../includes/mail_helper.php';

$action = $_GET['action'] ?? '';

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST; // fallback for form data
    }

    switch ($action) {
        case 'login':
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            if (empty($email) || empty($password)) {
                $response = ['success' => false, 'message' => 'Email and password required'];
                break;
            }
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if email verified (optional – you can skip this check)
                // if (!$user['email_verified']) {
                //     $response = ['success' => false, 'message' => 'Please verify your email first'];
                //     break;
                // }
                $payload = ['user_id' => $user['id'], 'role_id' => $user['role_id']];
                $token = JWT::encode($payload);
                $response = ['success' => true, 'token' => $token, 'user' => ['id' => $user['id'], 'name' => $user['full_name'], 'role' => $user['role_id']]];
            } else {
                $response = ['success' => false, 'message' => 'Invalid credentials'];
            }
            break;

        case 'register':
            $full_name = $data['full_name'] ?? '';
            $email = $data['email'] ?? '';
            $phone = $data['phone'] ?? '';
            $password = $data['password'] ?? '';
            $role_id = $data['role_id'] ?? 3; // default student

            if (empty($full_name) || empty($email) || empty($password)) {
                $response = ['success' => false, 'message' => 'Full name, email, and password required'];
                break;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response = ['success' => false, 'message' => 'Invalid email'];
                break;
            }
            if (strlen($password) < 6) {
                $response = ['success' => false, 'message' => 'Password must be at least 6 characters'];
                break;
            }
            // Check if email exists
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $response = ['success' => false, 'message' => 'Email already registered'];
                break;
            }
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
            if ($stmt->execute([$role_id, $full_name, $email, $phone, $hashed])) {
                $user_id = $pdo->lastInsertId();
                // Generate verification token (simplified)
                $verify_token = bin2hex(random_bytes(32));
                // Store verification token – we can add a verification_tokens table, or store in a new column
                // We'll add a temporary column if not exists (or use a separate table)
                // For simplicity, we'll use a separate table `email_verifications`
                // Run: CREATE TABLE email_verifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, token VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
                // But to keep it simple, we'll just send a link with user_id and token based on email hash.
                // We'll create a verification link using a simple method: include user_id and a hash (e.g., md5(email . salt))
                $verification_link = "http://localhost/uscms/api/auth.php?action=verify&user_id=$user_id&token=" . hash('sha256', $email . 'your_salt_here');
                $subject = "Verify your email - Limkokwing USCMS";
                $body = "Hello $full_name,<br><br>Please click the link below to verify your email address:<br><a href='$verification_link'>$verification_link</a>";
                sendMail($email, $subject, $body);
                $response = ['success' => true, 'message' => 'Registration successful. Please check your email for verification link.'];
            } else {
                $response = ['success' => false, 'message' => 'Registration failed'];
            }
            break;

        case 'verify':
            $user_id = $_GET['user_id'] ?? 0;
            $token = $_GET['token'] ?? '';
            if ($user_id && $token) {
                $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                if ($user && hash('sha256', $user['email'] . 'your_salt_here') === $token) {
                    $update = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
                    if ($update->execute([$user_id])) {
                        $response = ['success' => true, 'message' => 'Email verified successfully! You can now log in.'];
                    } else {
                        $response = ['success' => false, 'message' => 'Verification failed'];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Invalid verification link'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Missing parameters'];
            }
            // Since this is a GET request, we output directly.
            if (!isset($response)) {
                echo "Verification page – you can redirect or show a message.";
            }
            break;

        case 'forgot-password':
            $email = $data['email'] ?? '';
            if (empty($email)) {
                $response = ['success' => false, 'message' => 'Email required'];
                break;
            }
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user) {
                $response = ['success' => false, 'message' => 'Email not found'];
                break;
            }
            $reset_token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $update->execute([$reset_token, $expiry, $user['id']]);
            $reset_link = "http://localhost/uscms/api/auth.php?action=reset-password&token=$reset_token";
            $subject = "Password Reset - Limkokwing USCMS";
            $body = "Hello,<br><br>You requested a password reset. Click the link below to set a new password:<br><a href='$reset_link'>$reset_link</a><br><br>This link expires in 1 hour.";
            sendMail($email, $subject, $body);
            $response = ['success' => true, 'message' => 'Reset link sent to your email.'];
            break;

        case 'reset-password':
            $token = $_GET['token'] ?? '';
            $new_password = $data['password'] ?? '';
            if (empty($token) || empty($new_password)) {
                // If GET request, show a form to set new password
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    // Display a simple HTML form
                    echo "<html><body><h2>Reset Password</h2>
                    <form method='POST' action='api/auth.php?action=reset-password&token=$token'>
                        <input type='password' name='password' placeholder='New password' required>
                        <button type='submit'>Reset</button>
                    </form>
                    </body></html>";
                    exit;
                }
                $response = ['success' => false, 'message' => 'Missing token or password'];
                break;
            }
            // Validate token
            $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            if (!$user) {
                $response = ['success' => false, 'message' => 'Invalid or expired token'];
                break;
            }
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            $update->execute([$hashed, $user['id']]);
            $response = ['success' => true, 'message' => 'Password reset successfully. You can now log in.'];
            break;

        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
}

// Output JSON response (except for verify and reset-password GET which output HTML)
if (!isset($response)) {
    // already output
} else {
    echo json_encode($response);
}