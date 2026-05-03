<?php
/**
 * Admin: Create a new quiz
 * Real-time quiz system - High performance
 * No emojis
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$pdo = Database::getInstance()->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $total_questions = filter_input(INPUT_POST, 'total_questions', FILTER_VALIDATE_INT);
    $questions_to_show = filter_input(INPUT_POST, 'questions_to_show', FILTER_VALIDATE_INT);
    $duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
    
    if (empty($title)) {
        $error = 'Quiz title is required.';
    } elseif ($total_questions === false || $total_questions < 1) {
        $error = 'Total questions must be a positive integer.';
    } elseif ($questions_to_show === false || $questions_to_show < 1) {
        $error = 'Questions to show must be a positive integer.';
    } elseif ($questions_to_show > $total_questions) {
        $error = 'Questions to show cannot exceed total questions.';
    } elseif ($duration_minutes === false || $duration_minutes < 1) {
        $error = 'Duration must be at least 1 minute.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO quizzes 
                (title, description, total_questions, questions_to_show, duration_minutes, status, created_by_admin_id) 
                VALUES (?, ?, ?, ?, ?, 'draft', ?)
            ");
            $stmt->execute([
                $title, 
                $description, 
                $total_questions, 
                $questions_to_show, 
                $duration_minutes,
                $_SESSION['admin_id']
            ]);
            $success = 'Quiz created successfully. You can now add questions.';
            // Optionally redirect to manage_questions.php?quiz_id=...
            // header('Location: manage_questions.php?quiz_id=' . $pdo->lastInsertId());
            // exit;
        } catch (PDOException $e) {
            // Log error silently
            error_log('Create quiz error: ' . $e->getMessage());
            $error = 'Failed to create quiz. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Quiz - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fb;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: 50px auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
        }
        h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
        }
        .sub {
            color: #64748b;
            margin-bottom: 28px;
            border-left: 3px solid #3b82f6;
            padding-left: 12px;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            color: #0f172a;
        }
        input, textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
            transition: 0.2s;
            margin-bottom: 20px;
            font-family: inherit;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
        }
        button:hover {
            background: #2563eb;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Create a New Quiz</h1>
    <div class="sub">Set up the quiz structure. You will add questions afterwards.</div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="title">Quiz Title *</label>
        <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">

        <label for="description">Description (optional)</label>
        <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>

        <label for="total_questions">Total Questions *</label>
        <input type="number" id="total_questions" name="total_questions" required min="1" value="<?php echo htmlspecialchars($_POST['total_questions'] ?? '5'); ?>">

        <label for="questions_to_show">Questions to Show per Attempt *</label>
        <input type="number" id="questions_to_show" name="questions_to_show" required min="1" value="<?php echo htmlspecialchars($_POST['questions_to_show'] ?? '5'); ?>">

        <label for="duration_minutes">Duration (minutes) *</label>
        <input type="number" id="duration_minutes" name="duration_minutes" required min="1" value="<?php echo htmlspecialchars($_POST['duration_minutes'] ?? '10'); ?>">

        <button type="submit">Create Quiz</button>
    </form>
    <hr>
    <a href="dashboard.php" class="back-link">Back to Dashboard</a>
</div>
</body>
</html>