<?php
/**
 * List all published quizzes for authenticated users
 * Real-time quiz system - creates randomized session on start
 * No emojis
 */

session_start();

require_once '../config/database.php';
require_once '../includes/helpers.php';

// Check if user is logged in (using new users table session)
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$pdo = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Fetch all published quizzes
$stmt = $pdo->prepare("
    SELECT id, title, description, duration_minutes, questions_to_show
    FROM quizzes
    WHERE status = 'published'
    ORDER BY created_at DESC
");
$stmt->execute();
$quizzes = $stmt->fetchAll();

// Handle quiz start (create session with randomized question order)
if (isset($_GET['start']) && is_numeric($_GET['start'])) {
    $quizId = (int)$_GET['start'];
    
    // Check if user already has an in-progress session for this quiz
    $checkStmt = $pdo->prepare("
        SELECT id, session_key FROM quiz_sessions 
        WHERE user_id = ? AND quiz_id = ? AND status = 'in_progress'
    ");
    $checkStmt->execute([$userId, $quizId]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        // Resume existing session
        redirect("attempt_quiz.php?session_key=" . $existing['session_key']);
    }
    
    // Get all question IDs for this quiz
    $qStmt = $pdo->prepare("SELECT id FROM questions WHERE quiz_id = ?");
    $qStmt->execute([$quizId]);
    $questions = $qStmt->fetchAll();
    $questionIds = array_column($questions, 'id');
    
    // Determine how many questions to show (questions_to_show from quiz)
    $quizStmt = $pdo->prepare("SELECT questions_to_show FROM quizzes WHERE id = ?");
    $quizStmt->execute([$quizId]);
    $quizData = $quizStmt->fetch();
    $questionsToShow = $quizData['questions_to_show'] ?? count($questionIds);
    
    // Randomize and pick subset if needed
    shuffle($questionIds);
    $selectedQuestions = array_slice($questionIds, 0, $questionsToShow);
    
    // Generate unique session key
    $sessionKey = bin2hex(random_bytes(32));
    
    // Store randomized question order as JSON
    $randomizedJson = json_encode($selectedQuestions);
    
    // Create quiz session
    $insertStmt = $pdo->prepare("
        INSERT INTO quiz_sessions 
        (user_id, quiz_id, session_key, current_question_index, randomized_questions, 
         start_time, last_activity, ip_address, user_agent, status)
        VALUES (?, ?, ?, 0, ?, NOW(), NOW(), ?, ?, 'in_progress')
    ");
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $insertStmt->execute([
        $userId, $quizId, $sessionKey, $randomizedJson, $ip, $userAgent
    ]);
    
    // Redirect to the real-time attempt page
    redirect("attempt_quiz.php?session_key=" . $sessionKey);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Quizzes - Real-Time Quiz System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            padding: 30px 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .header {
            background: white;
            border-radius: 24px;
            padding: 24px 28px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        h1 {
            font-size: 28px;
            font-weight: 600;
            color: #0f172a;
        }
        .welcome {
            color: #475569;
            margin-top: 6px;
            border-left: 3px solid #3b82f6;
            padding-left: 12px;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            transition: transform 0.1s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        .quiz-title {
            font-size: 22px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .description {
            color: #334155;
            margin: 12px 0;
            line-height: 1.4;
        }
        .meta {
            display: flex;
            gap: 24px;
            margin: 16px 0 20px;
            font-size: 14px;
            color: #475569;
        }
        .meta span {
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 40px;
        }
        .btn-start {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 500;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-start:hover {
            background: #2563eb;
        }
        .empty {
            text-align: center;
            background: white;
            padding: 50px;
            border-radius: 24px;
            color: #64748b;
        }
        .logout {
            float: right;
            background: none;
            border: 1px solid #cbd5e1;
            padding: 6px 16px;
            border-radius: 40px;
            text-decoration: none;
            color: #475569;
            font-size: 14px;
        }
        .logout:hover {
            background: #f1f5f9;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        footer {
            text-align: center;
            margin-top: 40px;
            font-size: 13px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header clearfix">
        <div style="float: left;">
            <h1>Available Quizzes</h1>
            <div class="welcome">Welcome, <?php echo htmlspecialchars($userName); ?></div>
        </div>
        <a href="logout.php" class="logout" style="float: right;">Sign Out</a>
    </div>

    <?php if (empty($quizzes)): ?>
        <div class="empty">
            No quizzes are currently available. Please check back later.
        </div>
    <?php else: ?>
        <?php foreach ($quizzes as $quiz): ?>
            <div class="card">
                <div class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></div>
                <div class="description">
                    <?php 
                        $desc = htmlspecialchars($quiz['description']);
                        echo nl2br($desc);
                    ?>
                </div>
                <div class="meta">
                    <span>Duration: <?php echo (int)$quiz['duration_minutes']; ?> min</span>
                    <span>Questions: <?php echo (int)$quiz['questions_to_show']; ?></span>
                </div>
                <a href="?start=<?php echo $quiz['id']; ?>" class="btn-start">Start Quiz</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <footer>
        Real-Time Quiz System | Your progress is saved automatically
    </footer>
</div>
</body>
</html>