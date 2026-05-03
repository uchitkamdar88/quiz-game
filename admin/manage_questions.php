<?php
/**
 * Admin: Manage Questions for a Quiz
 * Real-time quiz system - High performance
 * No emojis
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$pdo = Database::getInstance()->getConnection();

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$error = '';
$success = '';
$edit_question = null; // For editing mode

// Fetch all quizzes for dropdown
$quizzes = $pdo->query("
    SELECT id, title, status
    FROM quizzes
    ORDER BY created_at DESC
")->fetchAll();

// Handle delete action
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
        $stmt->execute([$delete_id, $quiz_id]);
        $success = 'Question deleted successfully.';
        // Redirect to avoid resubmission
        header("Location: manage_questions.php?quiz_id=$quiz_id&msg=deleted");
        exit;
    } catch (PDOException $e) {
        error_log('Delete question error: ' . $e->getMessage());
        $error = 'Failed to delete question.';
    }
}

// Handle edit fetch (populate form)
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? AND quiz_id = ?");
    $stmt->execute([$edit_id, $quiz_id]);
    $edit_question = $stmt->fetch();
    if (!$edit_question) {
        $error = 'Question not found.';
        $edit_question = null;
    }
}

// Handle add/edit submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $quiz_id_post = (int)$_POST['quiz_id'];
    $question_text = trim($_POST['question_text'] ?? '');
    $media_type = $_POST['media_type'] ?? 'none';
    $media_path = trim($_POST['media_path'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct_answer = $_POST['correct_answer'] ?? '';
    $marks = (int)($_POST['marks'] ?? 1);
    
    if (empty($question_text)) {
        $error = 'Question text is required.';
    } elseif (empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
        $error = 'All four options are required.';
    } elseif (!in_array($correct_answer, ['a','b','c','d'])) {
        $error = 'Please select a correct answer.';
    } else {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO questions 
                    (quiz_id, question_text, media_type, media_path, option_a, option_b, option_c, option_d, correct_answer, marks) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $quiz_id_post, $question_text, $media_type, $media_path,
                    $option_a, $option_b, $option_c, $option_d, $correct_answer, $marks
                ]);
                $success = 'Question added successfully.';
            } elseif ($_POST['action'] === 'edit' && isset($_POST['question_id'])) {
                $question_id = (int)$_POST['question_id'];
                $stmt = $pdo->prepare("
                    UPDATE questions SET 
                        question_text = ?, media_type = ?, media_path = ?,
                        option_a = ?, option_b = ?, option_c = ?, option_d = ?,
                        correct_answer = ?, marks = ?
                    WHERE id = ? AND quiz_id = ?
                ");
                $stmt->execute([
                    $question_text, $media_type, $media_path,
                    $option_a, $option_b, $option_c, $option_d, $correct_answer, $marks,
                    $question_id, $quiz_id_post
                ]);
                $success = 'Question updated successfully.';
                // Clear edit mode
                $edit_question = null;
                header("Location: manage_questions.php?quiz_id=$quiz_id_post&msg=updated");
                exit;
            }
            
            // After add, redirect to clear form and avoid resubmission
            if ($_POST['action'] === 'add') {
                header("Location: manage_questions.php?quiz_id=$quiz_id_post&msg=added");
                exit;
            }
        } catch (PDOException $e) {
            error_log('Save question error: ' . $e->getMessage());
            $error = 'Failed to save question. Please check your input.';
        }
    }
}

// Fetch questions for selected quiz
$questions = [];
if ($quiz_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
}

// Handle success message from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $success = 'Question added successfully.';
    if ($_GET['msg'] === 'updated') $success = 'Question updated successfully.';
    if ($_GET['msg'] === 'deleted') $success = 'Question deleted successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - Admin Panel</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
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
        .quiz-selector {
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .quiz-selector label {
            font-weight: 500;
            color: #0f172a;
        }
        select {
            padding: 8px 14px;
            border-radius: 40px;
            border: 1px solid #cbd5e1;
            background: white;
            font-size: 14px;
        }
        .btn-sm {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 40px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-sm:hover {
            background: #2563eb;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid #cbd5e1;
            color: #334155;
        }
        .btn-outline:hover {
            background: #f1f5f9;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
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
        .question-list {
            margin-top: 30px;
        }
        .question-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.2s;
        }
        .question-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .question-text {
            font-weight: 600;
            color: #0f172a;
            font-size: 18px;
        }
        .question-actions a {
            margin-left: 12px;
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }
        .question-actions a.delete {
            color: #ef4444;
        }
        .options {
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 12px;
            margin-top: 12px;
            font-size: 14px;
        }
        .correct {
            background: #dcfce7;
            color: #166534;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 12px;
            display: inline-block;
        }
        .form-section {
            background: #f8fafc;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .full-width {
            grid-column: span 2;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 14px;
            color: #1e293b;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
        }
        textarea {
            resize: vertical;
        }
        button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 40px;
            font-weight: 500;
            cursor: pointer;
            font-size: 14px;
        }
        .options-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #e2e8f0;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #3b82f6;
            text-decoration: none;
        }
        @media (max-width: 700px) {
            .form-grid, .options-group {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Manage Questions</h1>
    <div class="sub">Add, edit, or remove quiz questions. Supports media attachments.</div>

    <div class="quiz-selector">
        <label for="quiz_select">Select Quiz:</label>
        <select id="quiz_select" onchange="window.location.href='manage_questions.php?quiz_id='+this.value">
            <option value="">-- Choose a quiz --</option>
            <?php foreach ($quizzes as $quiz): ?>
                <option value="<?php echo $quiz['id']; ?>" <?php echo ($quiz_id == $quiz['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($quiz['title']) . ' (' . ucfirst($quiz['status']) . ')'; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($quiz_id > 0): ?>
            <a href="create_quiz.php" class="btn-sm btn-outline" style="background:white;">+ New Quiz</a>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($quiz_id > 0): ?>
        <!-- Add/Edit Question Form -->
        <div class="form-section">
            <h3 style="margin-bottom: 20px; font-weight:600;">
                <?php echo $edit_question ? 'Edit Question' : 'Add New Question'; ?>
            </h3>
            <form method="post">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                <input type="hidden" name="action" value="<?php echo $edit_question ? 'edit' : 'add'; ?>">
                <?php if ($edit_question): ?>
                    <input type="hidden" name="question_id" value="<?php echo $edit_question['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="full-width">
                        <label>Question Text *</label>
                        <textarea name="question_text" rows="3" required><?php echo htmlspecialchars($edit_question['question_text'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label>Media Type</label>
                        <select name="media_type" id="media_type">
                            <option value="none" <?php echo ($edit_question['media_type'] ?? 'none') == 'none' ? 'selected' : ''; ?>>None</option>
                            <option value="image" <?php echo ($edit_question['media_type'] ?? '') == 'image' ? 'selected' : ''; ?>>Image</option>
                            <option value="audio" <?php echo ($edit_question['media_type'] ?? '') == 'audio' ? 'selected' : ''; ?>>Audio</option>
                            <option value="video" <?php echo ($edit_question['media_type'] ?? '') == 'video' ? 'selected' : ''; ?>>Video</option>
                        </select>
                    </div>
                    <div>
                        <label>Media Path (URL or file path)</label>
                        <input type="text" name="media_path" value="<?php echo htmlspecialchars($edit_question['media_path'] ?? ''); ?>" placeholder="/uploads/image.jpg">
                    </div>
                    
                    <div class="options-group full-width">
                        <div>
                            <label>Option A *</label>
                            <input type="text" name="option_a" required value="<?php echo htmlspecialchars($edit_question['option_a'] ?? ''); ?>">
                        </div>
                        <div>
                            <label>Option B *</label>
                            <input type="text" name="option_b" required value="<?php echo htmlspecialchars($edit_question['option_b'] ?? ''); ?>">
                        </div>
                        <div>
                            <label>Option C *</label>
                            <input type="text" name="option_c" required value="<?php echo htmlspecialchars($edit_question['option_c'] ?? ''); ?>">
                        </div>
                        <div>
                            <label>Option D *</label>
                            <input type="text" name="option_d" required value="<?php echo htmlspecialchars($edit_question['option_d'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div>
                        <label>Correct Answer *</label>
                        <select name="correct_answer" required>
                            <option value="">-- Select --</option>
                            <option value="a" <?php echo ($edit_question['correct_answer'] ?? '') == 'a' ? 'selected' : ''; ?>>Option A</option>
                            <option value="b" <?php echo ($edit_question['correct_answer'] ?? '') == 'b' ? 'selected' : ''; ?>>Option B</option>
                            <option value="c" <?php echo ($edit_question['correct_answer'] ?? '') == 'c' ? 'selected' : ''; ?>>Option C</option>
                            <option value="d" <?php echo ($edit_question['correct_answer'] ?? '') == 'd' ? 'selected' : ''; ?>>Option D</option>
                        </select>
                    </div>
                    <div>
                        <label>Marks</label>
                        <input type="number" name="marks" min="1" value="<?php echo htmlspecialchars($edit_question['marks'] ?? '1'); ?>">
                    </div>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 12px;">
                    <button type="submit"><?php echo $edit_question ? 'Update Question' : 'Add Question'; ?></button>
                    <?php if ($edit_question): ?>
                        <a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn-sm btn-outline" style="line-height: 2.2;">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- List Existing Questions -->
        <div class="question-list">
            <h3 style="margin-bottom: 16px; font-weight:600;">Existing Questions (<?php echo count($questions); ?>)</h3>
            <?php if (empty($questions)): ?>
                <p style="color: #64748b;">No questions added yet. Use the form above to create your first question.</p>
            <?php else: ?>
                <?php foreach ($questions as $q): ?>
                    <div class="question-card">
                        <div class="question-header">
                            <div class="question-text"><?php echo htmlspecialchars($q['question_text']); ?></div>
                            <div class="question-actions">
                                <a href="?quiz_id=<?php echo $quiz_id; ?>&edit=<?php echo $q['id']; ?>">Edit</a>
                                <a href="?quiz_id=<?php echo $quiz_id; ?>&delete=<?php echo $q['id']; ?>" class="delete" onclick="return confirm('Delete this question? This action cannot be undone.');">Delete</a>
                            </div>
                        </div>
                        <div class="options">
                            <div>A: <?php echo htmlspecialchars($q['option_a']); ?></div>
                            <div>B: <?php echo htmlspecialchars($q['option_b']); ?></div>
                            <div>C: <?php echo htmlspecialchars($q['option_c']); ?></div>
                            <div>D: <?php echo htmlspecialchars($q['option_d']); ?></div>
                            <div style="margin-top: 10px;"><span class="correct">Correct: <?php echo strtoupper($q['correct_answer']); ?></span> | Marks: <?php echo $q['marks']; ?></div>
                            <?php if ($q['media_type'] != 'none' && !empty($q['media_path'])): ?>
                                <div style="margin-top: 12px;">
                                    <?php if ($q['media_type'] === 'image'): ?>
                                        <img 
                                            src="<?php echo htmlspecialchars($q['media_path']); ?>" 
                                            alt="Question media"
                                            style="max-width: 320px; width: 100%; border-radius: 12px; border: 1px solid #cbd5e1;"
                                        >
                                    <?php elseif ($q['media_type'] === 'audio'): ?>
                                        <audio controls style="width: 100%;">
                                            <source src="<?php echo htmlspecialchars($q['media_path']); ?>">
                                            Your browser does not support audio playback.
                                        </audio>
                                    <?php elseif ($q['media_type'] === 'video'): ?>
                                        <video controls style="max-width: 420px; width: 100%; border-radius: 12px;">
                                            <source src="<?php echo htmlspecialchars($q['media_path']); ?>">
                                            Your browser does not support video playback.
                                        </video>
                                    <?php endif; ?>

                                    <div style="margin-top: 6px; font-size: 12px; color: #64748b;">
                                        <?php echo htmlspecialchars($q['media_path']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="alert" style="background:#eef2ff; color:#1e40af;">Please select a quiz from the dropdown to manage its questions.</div>
    <?php endif; ?>
    
    <hr>
    <a href="dashboard.php" class="back-link">Back to Dashboard</a>
</div>
</body>
</html>