<?php
/**
 * Landing Page - Quiz System
 * Redirects to appropriate login based on user choice
 * No emojis
 */
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz System - Real-Time Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 32px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            overflow: hidden;
            text-align: center;
        }
        .header {
            background: #1e293b;
            padding: 40px 28px;
            color: white;
        }
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .header p {
            color: #94a3b8;
            font-size: 16px;
        }
        .body {
            padding: 40px 28px;
        }
        .button-group {
            display: flex;
            gap: 24px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }
        .btn {
            padding: 14px 32px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-block;
        }
        .btn-admin {
            background: #1e293b;
            color: white;
            border: 2px solid #1e293b;
        }
        .btn-admin:hover {
            background: #0f172a;
            transform: translateY(-2px);
        }
        .btn-user {
            background: #3b82f6;
            color: white;
            border: 2px solid #3b82f6;
        }
        .btn-user:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        .info {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
            text-align: left;
        }
        .info h3 {
            color: #0f172a;
            margin-bottom: 12px;
            font-size: 18px;
        }
        .info ul {
            padding-left: 20px;
            color: #475569;
            line-height: 1.6;
        }
        footer {
            background: #f1f5f9;
            padding: 16px;
            font-size: 13px;
            color: #64748b;
        }
        @media (max-width: 480px) {
            .button-group {
                flex-direction: column;
                gap: 16px;
            }
            .btn {
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Quiz Platform</h1>
        <p>Real-Time • High Performance • Anti-Cheat</p>
    </div>
    <div class="body">
        <div class="button-group">
            <a href="admin/login.php" class="btn btn-admin">Admin Login</a>
            <a href="user/login.php" class="btn btn-user">User Login</a>
        </div>
        
        <div class="info">
            <h3>About This Platform</h3>
            <ul>
                <li>Real-time single-question AJAX loading</li>
                <li>Timer-based with server-side validation</li>
                <li>Randomized questions per participant</li>
                <li>Anti-cheat: disable copy, tab switching detection</li>
                <li>Live leaderboard and detailed results</li>
                <li>Network-based access on same Wi-Fi</li>
            </ul>
        </div>
    </div>
    <footer>
        &copy; 2025 Quiz System | Built for e-resume
    </footer>
</div>
</body>
</html>