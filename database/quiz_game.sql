CREATE DATABASE IF NOT EXISTS quiz_game;
USE quiz_game;

-- ======================================================
-- USERS TABLE (Unified authentication for participants)
-- ======================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- ADMINS
-- ======================================================
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- QUIZZES
-- ======================================================
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    total_questions INT NOT NULL,
    questions_to_show INT NOT NULL,
    duration_minutes INT NOT NULL,
    randomize_questions TINYINT(1) DEFAULT 1,
    status ENUM('draft','published','closed') DEFAULT 'draft',
    created_by_admin_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
);

-- ======================================================
-- QUESTIONS
-- ======================================================
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    media_type ENUM('none','image','audio','video') DEFAULT 'none',
    media_path VARCHAR(255) DEFAULT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_answer ENUM('a','b','c','d') NOT NULL,
    marks INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- ======================================================
-- REAL-TIME SESSION TRACKING
-- ======================================================
CREATE TABLE quiz_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    session_key VARCHAR(64) NOT NULL UNIQUE,
    current_question_index INT DEFAULT 0,
    randomized_questions TEXT NOT NULL,
    start_time DATETIME NOT NULL,
    last_activity DATETIME NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    status ENUM('in_progress','paused','completed','abandoned') DEFAULT 'in_progress',
    total_score INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_active (quiz_id, status, last_activity)
);

-- ======================================================
-- ATTEMPTS
-- ======================================================
CREATE TABLE attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT DEFAULT 0,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME DEFAULT NULL,
    FOREIGN KEY (session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_user_quiz (user_id, quiz_id)
);

-- ======================================================
-- ATTEMPT ANSWERS
-- ======================================================
CREATE TABLE attempt_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer ENUM('a','b','c','d') DEFAULT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    time_taken_seconds INT DEFAULT NULL,
    answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_question (session_id, question_id)
);

-- ======================================================
-- REAL-TIME LEADERBOARD CACHE
-- ======================================================
CREATE TABLE leaderboard_cache (
    quiz_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT NOT NULL,
    rank INT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (quiz_id, user_id),
    INDEX idx_rank (quiz_id, rank)
);

-- ======================================================
-- ACTIVE USERS TRACKING
-- ======================================================
CREATE TABLE active_quiz_users (
    session_id INT NOT NULL PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    last_ping DATETIME NOT NULL,
    INDEX idx_cleanup (last_ping)
);

-- ======================================================
-- DEFAULT ADMIN (password: admin123)
-- ======================================================
INSERT INTO admins (full_name, email, password)
VALUES (
    'Administrator',
    'admin@quiz.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- ======================================================
-- SAMPLE QUIZ AND QUESTIONS (For testing)
-- ======================================================
INSERT INTO quizzes (title, description, total_questions, questions_to_show, duration_minutes, status, created_by_admin_id)
VALUES ('Sample Quiz', 'This is a demo quiz for testing real-time features', 0, 3, 5, 'published', 1);

SET @quiz_id = LAST_INSERT_ID();

INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, marks)
VALUES 
(@quiz_id, 'What is the capital of France?', 'Berlin', 'Madrid', 'Paris', 'Lisbon', 'c', 1),
(@quiz_id, 'Which language runs in a web browser?', 'Java', 'C', 'Python', 'JavaScript', 'd', 1),
(@quiz_id, 'What does SQL stand for?', 'Structured Query Language', 'Simple Query Language', 'Styled Question Language', 'None of above', 'a', 1);

UPDATE quizzes SET total_questions = 3 WHERE id = @quiz_id;