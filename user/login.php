<?php
/**
 * User Login for Real-Time Quiz System
 * Authenticates participants from users table
 * No emojis
 */

session_start();

// If already logged in, redirect to available quizzes
if (isset($_SESSION['user_id'])) {
    header('Location: available_quizzes.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/helpers.php';

$pdo = Database::getInstance()->getConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active'] != 1) {
                $error = 'Your account is deactivated. Contact administrator.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                header('Location: available_quizzes.php');
                exit;
            }
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
    <title>Login - Quiz System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 420px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 32px 28px;
        }
        h2 {
            font-size: 28px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .sub {
            color: #475569;
            margin-bottom: 28px;
            font-size: 14px;
            border-left: 3px solid #3b82f6;
            padding-left: 12px;
        }
        label {
            display: block;
            font-weight: 500;
            margin-top: 14px;
            margin-bottom: 6px;
            color: #1e293b;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
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
            margin-top: 24px;
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #2563eb;
        }
        .alert-error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            color: #b91c1c;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #475569;
        }
        .register-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        hr {
            margin: 20px 0 10px;
            border: none;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Welcome Back</h2>
    <div class="sub">Login to continue your quiz</div>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Email Address</label>
        <input type="email" name="email" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <hr>
    <div class="register-link">
        Don't have an account? <a href="register.php">Register here</a>
    </div>
</div>
</body>
</html>