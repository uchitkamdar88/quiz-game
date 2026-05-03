<?php
/**
 * Quiz Result Page
 * Recalculates result from attempt_answers
 * Supports session_key and legacy attempt_id
 * No emojis
 */

session_start();

require_once '../config/database.php';
require_once '../includes/helpers.php';

$pdo = Database::getInstance()->getConnection();

$sessionKey = $_GET['session_key'] ?? '';
$attemptId = isset($_GET['attempt_id']) ? (int) $_GET['attempt_id'] : 0;

$sessionId = 0;
$userId = 0;
$quizId = 0;
$quizTitle = '';

if (!empty($sessionKey)) {
    $stmt = $pdo->prepare("
        SELECT
            s.id AS session_id,
            s.user_id,
            s.quiz_id,
            q.title
        FROM quiz_sessions s
        JOIN quizzes q ON s.quiz_id = q.id
        WHERE s.session_key = ?
        LIMIT 1
    ");
    $stmt->execute([$sessionKey]);

    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        $sessionId = (int) $session['session_id'];
        $userId = (int) $session['user_id'];
        $quizId = (int) $session['quiz_id'];
        $quizTitle = $session['title'];
    }
} elseif ($attemptId > 0) {
    $stmt = $pdo->prepare("
        SELECT
            a.session_id,
            a.user_id,
            a.quiz_id,
            q.title
        FROM attempts a
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE a.id = ?
        LIMIT 1
    ");
    $stmt->execute([$attemptId]);

    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($attempt) {
        $sessionId = (int) $attempt['session_id'];
        $userId = (int) $attempt['user_id'];
        $quizId = (int) $attempt['quiz_id'];
        $quizTitle = $attempt['title'];
    }
}

if ($sessionId <= 0) {
    die('Result not found. Please complete a quiz first.');
}

/*
|--------------------------------------------------------------------------
| Fetch answers
|--------------------------------------------------------------------------
*/
$answersStmt = $pdo->prepare("
    SELECT
        aa.question_id,
        aa.selected_answer,
        aa.is_correct,
        q.marks,
        q.correct_answer
    FROM attempt_answers aa
    JOIN questions q ON aa.question_id = q.id
    WHERE aa.session_id = ?
");
$answersStmt->execute([$sessionId]);

$answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Calculate score
|--------------------------------------------------------------------------
*/
$totalPossible = 0;
$totalEarned = 0;
$correctCount = 0;
$incorrectCount = 0;
$unansweredCount = 0;

foreach ($answers as $ans) {
    $marks = (int) $ans['marks'];
    $totalPossible += $marks;

    if ($ans['selected_answer'] === null) {
        $unansweredCount++;
        continue;
    }

    if ((int) $ans['is_correct'] === 1) {
        $totalEarned += $marks;
        $correctCount++;
    } else {
        $incorrectCount++;
    }
}

$totalAnswered = $correctCount + $incorrectCount + $unansweredCount;

$percentage = 0;
if ($totalPossible > 0) {
    $percentage = round(($totalEarned / $totalPossible) * 100, 2);
}

/*
|--------------------------------------------------------------------------
| Update session score
|--------------------------------------------------------------------------
*/
$updateScore = $pdo->prepare("
    UPDATE quiz_sessions
    SET total_score = ?
    WHERE id = ?
");
$updateScore->execute([$totalEarned, $sessionId]);

$updateAttempt = $pdo->prepare("
    UPDATE attempts
    SET score = ?
    WHERE session_id = ?
");
$updateAttempt->execute([$totalEarned, $sessionId]);

/*
|--------------------------------------------------------------------------
| Leaderboard
|--------------------------------------------------------------------------
*/
$leaderStmt = $pdo->prepare("
    SELECT
        u.first_name,
        u.last_name,
        s.total_score
    FROM quiz_sessions s
    JOIN users u ON s.user_id = u.id
    WHERE s.quiz_id = ?
      AND s.status = 'completed'
    ORDER BY s.total_score DESC, s.last_activity ASC
    LIMIT 5
");
$leaderStmt->execute([$quizId]);

$leaderboard = $leaderStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quiz Results - <?php echo htmlspecialchars($quizTitle); ?></title>
<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}
body{
    font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;
    background:#f0f4f8;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    padding:20px;
}
.container{
    max-width:760px;
    width:100%;
    background:#ffffff;
    border-radius:32px;
    overflow:hidden;
    box-shadow:0 20px 35px rgba(0,0,0,.08);
}
.header{
    background:#1e293b;
    color:#ffffff;
    padding:30px 28px;
    text-align:center;
}
.quiz-title{
    font-size:26px;
    font-weight:600;
    margin-bottom:10px;
}
.score-circle{
    width:150px;
    height:150px;
    margin:20px auto 0;
    border-radius:50%;
    background:#0f172a;
    border:4px solid #3b82f6;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
}
.score-number{
    font-size:46px;
    font-weight:700;
    line-height:1;
}
.score-out-of{
    font-size:14px;
    opacity:.75;
    margin-top:4px;
}
.percentage{
    margin-top:18px;
    font-size:28px;
    font-weight:700;
}
.details{
    padding:24px 28px;
    background:#f8fafc;
    border-bottom:1px solid #e2e8f0;
}
.stat-row{
    display:flex;
    justify-content:space-between;
    padding:11px 0;
    border-bottom:1px solid #e2e8f0;
}
.stat-row:last-child{
    border-bottom:none;
}
.stat-label{
    font-weight:500;
    color:#1e293b;
}
.stat-value{
    font-weight:600;
}
.correct{
    color:#16a34a;
}
.incorrect{
    color:#dc2626;
}
.unanswered{
    color:#b45309;
}
.leaderboard{
    padding:24px 28px;
}
.leaderboard h3{
    font-size:20px;
    font-weight:600;
    color:#0f172a;
    margin-bottom:16px;
}
.leaderboard-entry{
    display:flex;
    align-items:center;
    gap:12px;
    padding:10px 0;
    border-bottom:1px solid #e2e8f0;
}
.leaderboard-entry:last-child{
    border-bottom:none;
}
.rank{
    width:42px;
    font-weight:700;
    color:#2563eb;
}
.name{
    flex:1;
}
.score{
    font-weight:600;
}
.empty{
    color:#64748b;
    text-align:center;
    padding:14px 0;
}
.actions{
    padding:24px 28px 32px;
    display:flex;
    gap:14px;
    justify-content:center;
}
.btn{
    text-decoration:none;
    padding:12px 24px;
    border-radius:40px;
    font-weight:500;
    display:inline-block;
}
.btn-secondary{
    background:#e2e8f0;
    color:#1e293b;
}
.btn-secondary:hover{
    background:#cbd5e1;
}
.btn-primary{
    background:#3b82f6;
    color:#ffffff;
}
.btn-primary:hover{
    background:#2563eb;
}
</style>
</head>
<body>

<div class="container">

    <div class="header">
        <div class="quiz-title"><?php echo htmlspecialchars($quizTitle); ?></div>

        <div class="score-circle">
            <div class="score-number"><?php echo $totalEarned; ?></div>
            <div class="score-out-of">out of <?php echo $totalPossible; ?></div>
        </div>

        <div class="percentage"><?php echo $percentage; ?>%</div>
    </div>

    <div class="details">
        <div class="stat-row">
            <span class="stat-label">Total Questions</span>
            <span class="stat-value"><?php echo $totalAnswered; ?></span>
        </div>

        <div class="stat-row">
            <span class="stat-label">Correct Answers</span>
            <span class="stat-value correct"><?php echo $correctCount; ?></span>
        </div>

        <div class="stat-row">
            <span class="stat-label">Incorrect Answers</span>
            <span class="stat-value incorrect"><?php echo $incorrectCount; ?></span>
        </div>

        <div class="stat-row">
            <span class="stat-label">Unanswered</span>
            <span class="stat-value unanswered"><?php echo $unansweredCount; ?></span>
        </div>

        <div class="stat-row">
            <span class="stat-label">Total Score</span>
            <span class="stat-value"><?php echo $totalEarned; ?> / <?php echo $totalPossible; ?></span>
        </div>
    </div>

    <div class="leaderboard">
        <h3>Top Scores - Leaderboard</h3>

        <?php if (!empty($leaderboard)): ?>
            <?php $rank = 1; ?>
            <?php foreach ($leaderboard as $entry): ?>
                <div class="leaderboard-entry">
                    <div class="rank">#<?php echo $rank++; ?></div>
                    <div class="name">
                        <?php echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']); ?>
                    </div>
                    <div class="score"><?php echo (int) $entry['total_score']; ?> pts</div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">No completed attempts yet.</div>
        <?php endif; ?>
    </div>

    <div class="actions">
        <a href="available_quizzes.php" class="btn btn-secondary">Back to Quizzes</a>
        <a href="available_quizzes.php?start=<?php echo $quizId; ?>" class="btn btn-primary">Retake Quiz</a>
    </div>

</div>

</body>
</html>