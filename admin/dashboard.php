<?php
/**
 * Admin Dashboard - Real-time Quiz System
 * Shows overview statistics and quick actions
 * No emojis
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$pdo = Database::getInstance()->getConnection();

// Fetch statistics
$stats = [];

// Total quizzes
$stmt = $pdo->query("SELECT COUNT(*) FROM quizzes");
$stats['total_quizzes'] = $stmt->fetchColumn();

// Total questions
$stmt = $pdo->query("SELECT COUNT(*) FROM questions");
$stats['total_questions'] = $stmt->fetchColumn();

// Total users (participants)
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// Total attempts completed
$stmt = $pdo->query("SELECT COUNT(*) FROM quiz_sessions WHERE status = 'completed'");
$stats['total_attempts'] = $stmt->fetchColumn();

// Recently added quizzes (last 5)
$stmt = $pdo->query("SELECT id, title, created_at, status FROM quizzes ORDER BY created_at DESC LIMIT 5");
$recentQuizzes = $stmt->fetchAll();

// Current admin name
$adminName = htmlspecialchars($_SESSION['admin_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Quiz System</title>
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
            min-height: 100vh;
        }
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #1e293b;
            color: white;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar h2 {
            font-size: 22px;
            font-weight: 600;
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
            transition: 0.2s;
        }
        .sidebar nav a:hover {
            background: #334155;
            color: white;
        }
        .sidebar nav a.active {
            background: #3b82f6;
            color: white;
        }
        .sidebar .logout {
            margin-top: 40px;
            border-top: 1px solid #334155;
            padding-top: 20px;
        }
        .sidebar .logout a {
            color: #f87171;
        }
        /* Main content */
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
        .header p {
            color: #475569;
            margin-top: 5px;
        }
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 24px 20px;
            border-radius: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
        }
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #0f172a;
        }
        .stat-label {
            color: #64748b;
            margin-top: 8px;
            font-size: 14px;
        }
        /* Recent quizzes */
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #0f172a;
        }
        .quiz-table {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
        }
        .quiz-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .quiz-table th, .quiz-table td {
            padding: 14px 20px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .quiz-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #1e293b;
        }
        .quiz-table tr:last-child td {
            border-bottom: none;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-published {
            background: #dcfce7;
            color: #166534;
        }
        .status-draft {
            background: #fef9c3;
            color: #854d0e;
        }
        .status-closed {
            background: #fee2e2;
            color: #b91c1c;
        }
        .btn-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .btn-link:hover {
            text-decoration: underline;
        }
        .empty-row td {
            text-align: center;
            color: #64748b;
            padding: 30px;
        }
        footer {
            margin-top: 40px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>Quiz Manager</h2>
    <nav>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="create_quiz.php">Create New Quiz</a>
        <a href="manage_questions.php">Manage Questions</a>
        <a href="participants.php">View Participants</a>
        <a href="leaderboard.php">Leaderboard</a>
        <div class="logout">
            <a href="logout.php">Sign Out</a>
        </div>
    </nav>
</div>
<div class="main-content">
    <div class="header">
        <h1>Welcome back, <?php echo $adminName; ?></h1>
        <p>Manage your quizzes, questions, and monitor participant activity.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_quizzes']; ?></div>
            <div class="stat-label">Total Quizzes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_questions']; ?></div>
            <div class="stat-label">Questions in Bank</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_users']; ?></div>
            <div class="stat-label">Registered Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_attempts']; ?></div>
            <div class="stat-label">Completed Attempts</div>
        </div>
    </div>

    <div class="section-title">Recently Added Quizzes</div>
    <div class="quiz-table">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recentQuizzes) > 0): ?>
                    <?php foreach ($recentQuizzes as $quiz): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                            <td>
                                <span class="status status-<?php echo $quiz['status']; ?>">
                                    <?php echo ucfirst($quiz['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></td>
                            <td>
                                <a href="manage_questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn-link">Manage Questions</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="empty-row">
                        <td colspan="4">No quizzes created yet. <a href="create_quiz.php">Create your first quiz</a></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer>
        Real-Time Quiz System | Secure Admin Panel
    </footer>
</div>
</body>
</html>