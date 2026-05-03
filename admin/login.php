<?php
/**
 * Admin Login - Real-time Quiz System
 * Secure authentication for administrators
 * No emojis
 */

session_start();
require_once '../config/database.php';

$pdo = Database::getInstance()->getConnection();

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, password FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Quiz System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-container {
            max-width: 440px;
            width: 100%;
            background: white;
            border-radius: 32px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: #1e293b;
            padding: 32px 28px;
            text-align: center;
        }
        .login-header h1 {
            color: white;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .login-header p {
            color: #94a3b8;
            font-size: 14px;
        }
        .login-body {
            padding: 32px 28px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #0f172a;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            font-size: 15px;
            transition: 0.2s;
            font-family: inherit;
        }
        input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
        }
        button {
            width: 100%;
            padding: 14px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 8px;
        }
        button:hover {
            background: #2563eb;
        }
        .alert-error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            color: #b91c1c;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 28px;
            font-size: 13px;
            color: #64748b;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        .demo-creds {
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 16px;
            margin-top: 24px;
            font-size: 12px;
            color: #475569;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <h1>Admin Portal</h1>
        <p>Secure access to quiz management</p>
    </div>
    <div class="login-body">
        <?php if ($error): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autofocus placeholder="admin@quiz.local" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••">
            </div>
            <button type="submit">Sign In</button>
        </form>
        <div class="footer">
            <a href="../user/login.php">Participant Login</a>
        </div>
    </div>
</div>
</body>
</html>