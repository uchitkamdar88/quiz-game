<?php
/**
 * Real-time Quiz Attempt Engine
 * Single-question AJAX loading with timer, anti-cheat, and server-side validation
 * No emojis
 */

session_start();

require_once '../config/database.php';
require_once '../includes/helpers.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$pdo = Database::getInstance()->getConnection();
$userId = (int) $_SESSION['user_id'];

$sessionKey = $_GET['session_key'] ?? '';

if (empty($sessionKey)) {
    die('Invalid quiz session. Please start the quiz again.');
}

/*
|--------------------------------------------------------------------------
| Load current session
|--------------------------------------------------------------------------
*/
function getQuizSession(PDO $pdo, string $sessionKey, int $userId)
{
    $stmt = $pdo->prepare("
        SELECT
            s.*,
            q.title AS quiz_title,
            q.duration_minutes,
            q.total_questions,
            q.questions_to_show
        FROM quiz_sessions s
        JOIN quizzes q ON s.quiz_id = q.id
        WHERE s.session_key = ?
          AND s.user_id = ?
          AND s.status IN ('in_progress', 'paused')
        LIMIT 1
    ");
    $stmt->execute([$sessionKey, $userId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$session = getQuizSession($pdo, $sessionKey, $userId);

if (!$session) {
    die('Quiz session not found or already completed.');
}

$sessionId = (int) $session['id'];
$quizId = (int) $session['quiz_id'];

/*
|--------------------------------------------------------------------------
| AJAX requests
|--------------------------------------------------------------------------
*/
if (isset($_GET['ajax_action'])) {
    header('Content-Type: application/json');

    $session = getQuizSession($pdo, $sessionKey, $userId);

    if (!$session) {
        echo json_encode([
            'error' => 'Quiz session expired or completed.'
        ]);
        exit;
    }

    $sessionId = (int) $session['id'];
    $quizId = (int) $session['quiz_id'];

    $currentIndex = (int) $session['current_question_index'];
    $randomizedQuestions = json_decode($session['randomized_questions'], true);

    if (!is_array($randomizedQuestions)) {
        echo json_encode([
            'error' => 'Invalid quiz session data.'
        ]);
        exit;
    }

    $totalQuestions = count($randomizedQuestions);

    $durationMinutes = max(5, (int) $session['duration_minutes']);

    /*
    |--------------------------------------------------------------------------
    | GET QUESTION
    |--------------------------------------------------------------------------
    */
    if ($_GET['ajax_action'] === 'get_question') {
        if ($currentIndex >= $totalQuestions) {
            echo json_encode([
                'completed' => true
            ]);
            exit;
        }

        $questionId = (int) $randomizedQuestions[$currentIndex];

        $qStmt = $pdo->prepare("
            SELECT *
            FROM questions
            WHERE id = ?
            LIMIT 1
        ");
        $qStmt->execute([$questionId]);

        $question = $qStmt->fetch(PDO::FETCH_ASSOC);

        if (!$question) {
            echo json_encode([
                'error' => 'Question not found.'
            ]);
            exit;
        }

        $attemptStmt = $pdo->prepare("
            SELECT id
            FROM attempts
            WHERE session_id = ?
            LIMIT 1
        ");
        $attemptStmt->execute([$sessionId]);

        if (!$attemptStmt->fetch()) {
            $createAttempt = $pdo->prepare("
                INSERT INTO attempts
                (session_id, user_id, quiz_id, started_at)
                VALUES (?, ?, ?, NOW())
            ");
            $createAttempt->execute([$sessionId, $userId, $quizId]);
        }

        $startTime = new DateTime($session['start_time']);
        $now = new DateTime();

        $elapsed = $now->getTimestamp() - $startTime->getTimestamp();
        $totalSeconds = $durationMinutes * 60;
        $remaining = $totalSeconds - $elapsed;

        if ($remaining <= 0) {
            $complete = $pdo->prepare("
                UPDATE quiz_sessions
                SET status = 'completed'
                WHERE id = ?
            ");
            $complete->execute([$sessionId]);

            $finalScore = $pdo->prepare("
                UPDATE attempts
                SET
                    score = (
                        SELECT total_score
                        FROM quiz_sessions
                        WHERE id = ?
                    ),
                    submitted_at = NOW()
                WHERE session_id = ?
            ");
            $finalScore->execute([$sessionId, $sessionId]);

            echo json_encode([
                'completed' => true
            ]);
            exit;
        }

$remaining = max(0, $remaining);

        $ping = $pdo->prepare("
            UPDATE quiz_sessions
            SET last_activity = NOW()
            WHERE id = ?
        ");
        $ping->execute([$sessionId]);

        echo json_encode([
            'question' => [
                'id' => (int) $question['id'],
                'text' => $question['question_text'],
                'option_a' => $question['option_a'],
                'option_b' => $question['option_b'],
                'option_c' => $question['option_c'],
                'option_d' => $question['option_d'],
                'media_type' => $question['media_type'],
                'media_path' => $question['media_path']
            ],
            'current_question_num' => $currentIndex + 1,
            'total_questions' => $totalQuestions,
            'time_remaining' => $remaining,
            'quiz_title' => $session['quiz_title']
        ]);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | SUBMIT ANSWER
    |--------------------------------------------------------------------------
    */
    if ($_GET['ajax_action'] === 'submit_answer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $questionId = (int) ($input['question_id'] ?? 0);
        $selectedAnswer = $input['selected_answer'] ?? null;

        if (!in_array($selectedAnswer, ['a', 'b', 'c', 'd'], true)) {
            $selectedAnswer = null;
        }

        if ($currentIndex >= $totalQuestions) {
            echo json_encode([
                'completed' => true
            ]);
            exit;
        }

        $expectedQuestionId = (int) $randomizedQuestions[$currentIndex];

        if ($questionId !== $expectedQuestionId) {
            echo json_encode([
                'error' => 'Invalid question order.'
            ]);
            exit;
        }

        $alreadyAnswered = $pdo->prepare("
            SELECT id
            FROM attempt_answers
            WHERE session_id = ?
              AND question_id = ?
            LIMIT 1
        ");
        $alreadyAnswered->execute([$sessionId, $questionId]);

        if ($alreadyAnswered->fetch()) {
            echo json_encode([
                'error' => 'Question already answered.'
            ]);
            exit;
        }

        $qStmt = $pdo->prepare("
            SELECT correct_answer, marks
            FROM questions
            WHERE id = ?
            LIMIT 1
        ");
        $qStmt->execute([$questionId]);

        $question = $qStmt->fetch(PDO::FETCH_ASSOC);

        if (!$question) {
            echo json_encode([
                'error' => 'Question not found.'
            ]);
            exit;
        }

        $isCorrect = ($selectedAnswer !== null && $selectedAnswer === $question['correct_answer']) ? 1 : 0;
        $marksEarned = $isCorrect ? (int) $question['marks'] : 0;

        $lastAnswerStmt = $pdo->prepare("
            SELECT MAX(answered_at)
            FROM attempt_answers
            WHERE session_id = ?
        ");
        $lastAnswerStmt->execute([$sessionId]);

        $lastAnsweredAt = $lastAnswerStmt->fetchColumn();

        $now = new DateTime();

        if ($lastAnsweredAt) {
            $last = new DateTime($lastAnsweredAt);
            $timeTaken = $now->getTimestamp() - $last->getTimestamp();
        } else {
            $start = new DateTime($session['start_time']);
            $timeTaken = $now->getTimestamp() - $start->getTimestamp();
        }

        $timeTaken = max(0, min($timeTaken, 300));

        $insertAnswer = $pdo->prepare("
            INSERT INTO attempt_answers
            (
                session_id,
                question_id,
                selected_answer,
                is_correct,
                time_taken_seconds,
                answered_at
            )
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $insertAnswer->execute([
            $sessionId,
            $questionId,
            $selectedAnswer,
            $isCorrect,
            $timeTaken
        ]);

        if ($marksEarned > 0) {
            $updateScore = $pdo->prepare("
                UPDATE quiz_sessions
                SET total_score = total_score + ?
                WHERE id = ?
            ");
            $updateScore->execute([$marksEarned, $sessionId]);
        }

        $newIndex = $currentIndex + 1;

        $updateSession = $pdo->prepare("
            UPDATE quiz_sessions
            SET current_question_index = ?, last_activity = NOW()
            WHERE id = ?
        ");
        $updateSession->execute([$newIndex, $sessionId]);

        if ($newIndex >= $totalQuestions) {
            $complete = $pdo->prepare("
                UPDATE quiz_sessions
                SET status = 'completed'
                WHERE id = ?
            ");
            $complete->execute([$sessionId]);

            $finalScore = $pdo->prepare("
                UPDATE attempts
                SET
                    score = (
                        SELECT total_score
                        FROM quiz_sessions
                        WHERE id = ?
                    ),
                    submitted_at = NOW()
                WHERE session_id = ?
            ");
            $finalScore->execute([$sessionId, $sessionId]);

            echo json_encode([
                'completed' => true
            ]);
            exit;
        }

        echo json_encode([
            'success' => true
        ]);
        exit;
    }

    echo json_encode([
        'error' => 'Invalid action.'
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($session['quiz_title']); ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{
    font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;
    background:#f0f4f8;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    padding:20px;
}
.quiz-container{
    max-width:800px;
    width:100%;
    background:#fff;
    border-radius:28px;
    overflow:hidden;
    box-shadow:0 15px 35px rgba(0,0,0,.08);
}
.quiz-header{
    background:#1e293b;
    color:#fff;
    padding:22px 28px;
}
.quiz-title{
    font-size:22px;
    font-weight:600;
}
.quiz-meta{
    margin-top:6px;
    font-size:14px;
    opacity:.85;
}
.timer{
    background:#0f172a;
    color:#fff;
    padding:14px 28px;
    display:flex;
    justify-content:space-between;
}
.question-area{
    padding:30px 28px;
}
.question-text{
    font-size:22px;
    line-height:1.4;
    margin-bottom:24px;
}
.options{
    display:flex;
    flex-direction:column;
    gap:14px;
}
.option{
    padding:14px 18px;
    border:1px solid #e2e8f0;
    border-radius:16px;
    cursor:pointer;
}
.option:hover{
    background:#f8fafc;
}
.option input{
    margin-right:10px;
}
.btn{
    width:100%;
    margin-top:24px;
    padding:14px;
    border:none;
    border-radius:40px;
    background:#2563eb;
    color:#fff;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
}
.loader{
    text-align:center;
    padding:30px;
}
.warning{
    margin-top:20px;
    font-size:13px;
    color:#92400e;
    background:#fef3c7;
    padding:12px 14px;
    border-left:4px solid #f59e0b;
    border-radius:8px;
}
</style>
</head>
<body>

<div class="quiz-container">
    <div class="quiz-header">
        <div class="quiz-title"><?php echo htmlspecialchars($session['quiz_title']); ?></div>
        <div class="quiz-meta" id="questionCounter">Loading...</div>
    </div>

    <div class="timer">
        <span>Time Remaining</span>
        <span id="timerDisplay">00:00</span>
    </div>

    <div class="question-area" id="questionArea">
        <div class="loader">Loading question...</div>
    </div>
</div>

<script>
const sessionKey = '<?php echo htmlspecialchars($sessionKey); ?>';

let currentQuestionId = null;
let timeRemaining = 0;
let timer = null;
let isSubmitting = false;

const questionArea = document.getElementById('questionArea');
const timerDisplay = document.getElementById('timerDisplay');
const questionCounter = document.getElementById('questionCounter');

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;'
        }[m];
    });
}

function formatTime(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
}

function startTimer() {
    if (timer) clearInterval(timer);

    timerDisplay.textContent = formatTime(timeRemaining);

    timer = setInterval(() => {
        if (timeRemaining <= 0) {
            clearInterval(timer);
            window.location.href = `result.php?session_key=${sessionKey}`;
            return;
        }

        timeRemaining--;
        timerDisplay.textContent = formatTime(timeRemaining);
    }, 1000);
}

async function fetchQuestion() {
    const res = await fetch(`?session_key=${sessionKey}&ajax_action=get_question`);
    const data = await res.json();

    if (data.error) {
        questionArea.innerHTML = `<div class="warning">${escapeHtml(data.error)}</div>`;
        return;
    }

    if (data.completed) {
        window.location.href = `result.php?session_key=${sessionKey}`;
        return;
    }

    currentQuestionId = data.question.id;
    timeRemaining = data.time_remaining;

    questionCounter.textContent =
        `Question ${data.current_question_num} of ${data.total_questions}`;

    startTimer();

    questionArea.innerHTML = `
        <div class="question-text">${escapeHtml(data.question.text)}</div>

        <div class="options">
            <label class="option"><input type="radio" name="answer" value="a"> ${escapeHtml(data.question.option_a)}</label>
            <label class="option"><input type="radio" name="answer" value="b"> ${escapeHtml(data.question.option_b)}</label>
            <label class="option"><input type="radio" name="answer" value="c"> ${escapeHtml(data.question.option_c)}</label>
            <label class="option"><input type="radio" name="answer" value="d"> ${escapeHtml(data.question.option_d)}</label>
        </div>

        <button class="btn" id="submitBtn">Submit Answer</button>

        <div class="warning">
            Do not refresh the page. Progress is saved automatically.
        </div>
    `;

    document.getElementById('submitBtn').addEventListener('click', () => {
        const selected = document.querySelector('input[name="answer"]:checked');
        submitAnswer(selected ? selected.value : null);
    });
}

async function submitAnswer(selectedAnswer) {
    if (isSubmitting) return;

    isSubmitting = true;

    const btn = document.getElementById('submitBtn');
    if (btn) btn.disabled = true;

    const res = await fetch(
        `?session_key=${sessionKey}&ajax_action=submit_answer`,
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                question_id: currentQuestionId,
                selected_answer: selectedAnswer
            })
        }
    );

    const data = await res.json();

    if (data.error) {
        alert(data.error);
        isSubmitting = false;
        if (btn) btn.disabled = false;
        return;
    }

    if (data.completed) {
        window.location.href = `result.php?session_key=${sessionKey}`;
        return;
    }

    isSubmitting = false;
    fetchQuestion();
}

document.addEventListener('contextmenu', e => e.preventDefault());

fetchQuestion();
</script>

</body>
</html>