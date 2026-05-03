<?php
/**
 * Admin: Global Leaderboard across all quizzes
 * Real-time quiz system
 * No emojis
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$pdo = Database::getInstance()->getConnection();

// Get top scores from completed sessions (status = 'completed')
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, q.title as quiz_title, s.total_score
    FROM quiz_sessions s
    JOIN users u ON s.user_id = u.id
    JOIN quizzes q ON s.quiz_id = q.id
    WHERE s.status = 'completed'
    ORDER BY s.total_score DESC
    LIMIT 50
");
$stmt->execute();
$leaderboard = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Admin Panel</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: #1e293b;
            color: white;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
        }
        .sidebar h2 {
            font-size: 22px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #334155;
        }
        .sidebar nav a {
            display: block;
            padding: 12px 16px;
            margin-bottom: 8px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 12px;
        }
        .sidebar nav a:hover {
            background: #334155;
            color: white;
        }
        .sidebar .logout {
            margin-top: 40px;
            border-top: 1px solid #334155;
            padding-top: 20px;
        }
        .main-content {
            margin-left: 280px;
            padding: 30px 40px;
            width: 100%;
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            color: #0f172a;
        }
        .leaderboard-table {
            background: white;
            border-radius: 24px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
        }
        .rank {
            font-weight: 700;
            color: #3b82f6;
        }
        footer {
            margin-top: 40px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>Quiz Manager</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="create_quiz.php">Create New Quiz</a>
        <a href="manage_questions.php">Manage Questions</a>
        <a href="participants.php">View Participants</a>
        <a href="leaderboard.php" class="active">Leaderboard</a>
        <div class="logout"><a href="logout.php">Sign Out</a></div>
    </nav>
</div>
<div class="main-content">
    <div class="header">
        <h1>Global Leaderboard</h1>
        <p>Top 50 scores across all completed quizzes.</p>
    </div>
    <div class="leaderboard-table">
        <table>
            <thead>
                <tr><th>Rank</th><th>Participant</th><th>Quiz</th><th>Score</th></tr>
            </thead>
            <tbody>
                <?php if (count($leaderboard) > 0): ?>
                    <?php $rank = 1; foreach ($leaderboard as $entry): ?>
                        <tr>
                            <td class="rank">#<?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($entry['quiz_title']); ?></td>
                            <td><?php echo $entry['total_score']; ?> pts</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:40px;">No completed quizzes yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <footer>Real-Time Quiz System | Top scores update automatically.</footer>
</div>
</body>
</html>