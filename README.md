# Real-Time Quiz System

A web-based quiz application built with PHP, MySQL, HTML, CSS, and JavaScript.

This project allows administrators to create quizzes, add questions, manage participants, and lets users attempt quizzes with randomized questions, timer support, and automatic progress saving.

---

## Features

### Admin

- Secure admin login
- Create quizzes
- Save quizzes as draft or publish them
- Add, edit, and delete questions
- Support for image, audio, and video in questions
- View participants
- View leaderboard
- Dashboard with quiz statistics

### User

- Secure user login
- View available published quizzes
- Start quiz attempts
- Randomized question order
- Resume in-progress attempts
- Automatic progress saving
- Final score submission

---

## Project Structure

```text
quiz_game/
├── admin/
├── assets/
│   ├── audio/
│   ├── images/
│   └── video/
├── config/
├── includes/
├── user/
└── README.md
```

---

## Requirements

Before running the project, install:

- XAMPP
- PHP
- MySQL
- A web browser

---

## Step 1: Put Project Inside htdocs

Place the real project folder inside XAMPP `htdocs`.

Example:

```text
C:\xampp\htdocs\quiz_game
```

Important:

Use the real folder.

Do not use a shortcut file.

Wrong:

```text
quiz_game - Shortcut.lnk
```

Correct:

```text
C:\xampp\htdocs\quiz_game
```

---

## Step 2: Start Apache and MySQL

Open XAMPP Control Panel.

Start:

- Apache
- MySQL

Both must be running.

---

## Step 3: Create Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Create a new database.

Example:

```text
quiz_game
```

---

## Step 4: Import Database

If your project contains an SQL file:

1. Open the `quiz_game` database
2. Click **Import**
3. Select the SQL file
4. Click **Go**

This will create all required tables.

---

## Step 5: Configure Database Connection

Open:

```text
config/database.php
```

Make sure your database credentials are correct.

Example:

```php
$host = 'localhost';
$dbname = 'quiz_game';
$username = 'root';
$password = '';
```

---

## Step 6: Open Project

Open in browser:

```text
http://localhost/quiz_game/
```

Admin panel:

```text
http://localhost/quiz_game/admin/
```

User panel:

```text
http://localhost/quiz_game/user/
```

---

# Admin Workflow

## Create Quiz

1. Login to admin panel
2. Open **Create New Quiz**
3. Fill:
   - quiz title
   - description
   - total questions
   - questions to show
   - duration
4. Click **Create Quiz**

---

## Draft and Published

### Draft

- Hidden from users
- Used while preparing quiz

### Published

- Visible in user quiz list
- Can be attempted by users

Only published quizzes appear in available quizzes.

---

## Manage Questions

1. Open **Manage Questions**
2. Select quiz
3. Add:
   - question text
   - options A, B, C, D
   - correct answer
   - marks
4. Save question

---

# Media Files

Place media inside:

```text
assets/images/
assets/audio/
assets/video/
```

Examples:

```text
assets/images/Tiger.jpg
assets/audio/Memories.mp3
assets/video/Failure.mp4
```

---

## Media Path While Adding Questions

Since `manage_questions.php` is inside the `admin` folder, use:

```text
../assets/images/Tiger.jpg
../assets/audio/Memories.mp3
../assets/video/Failure.mp4
```

Important:

Do not use:

```text
assets/images/Tiger.jpg
```

Use:

```text
../assets/images/Tiger.jpg
```

---

# User Workflow

## Start Quiz

1. User logs in
2. Opens available quizzes
3. Clicks **Start Quiz**

The system will:

- create a quiz session
- randomize question order
- store session progress

---

## Resume Quiz

If a quiz is already in progress, the system resumes the existing session automatically.

---

## Submit Quiz

When finished:

- answers are submitted
- score is calculated
- attempt is stored

---

# GitHub

Always push the real project folder.

Open terminal inside:

```text
C:\xampp\htdocs\quiz_game
```

Run:

```bash
git init
git add .
git commit -m "Initial commit"
```

Then connect your GitHub repository and push.

---

# Important Notes

## Real Folder vs Shortcut

A shortcut only opens the real folder.

If you edit files through the shortcut, the real files are modified.

But for ZIP upload and GitHub push, always use the real folder.

---

# Troubleshooting

## Media Not Showing

Check:

- file exists inside assets folder
- path is correct
- file name matches exactly
- file extension is correct

---

## Project Not Opening

Check:

- Apache running
- MySQL running
- folder inside `htdocs`
- database created
- database connection correct

---

# Beginner Tip

If something does not work, check these first:

1. Folder location
2. File path
3. Database connection

Most beginner problems come from these three things.