<?php
/**
 * User Registration for Real-Time Quiz System
 */

session_start();

require_once '../config/database.php';
require_once '../includes/helpers.php';

if (isset($_SESSION['user_id'])) {
    redirect('available_quizzes.php');
}

$pdo = Database::getInstance()->getConnection();

$errors = [];

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$dob = $_POST['dob'] ?? '';
$gender = $_POST['gender'] ?? '';
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[a-zA-Z\s\-]{2,50}$/', $firstName)) {
        $errors[] = 'First name must be 2 to 50 letters.';
    }

    if (!preg_match('/^[a-zA-Z\s\-]{2,50}$/', $lastName)) {
        $errors[] = 'Last name must be 2 to 50 letters.';
    }

    if (empty($dob) || strtotime($dob) > time()) {
        $errors[] = 'Valid date of birth is required.';
    }

    $age = calculateAge($dob);

    if ($age < 5 || $age > 120) {
        $errors[] = 'Age must be between 5 and 120.';
    }

    if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $errors[] = 'Please select a valid gender.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required.';
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Phone number must be exactly 10 digits.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'Email already registered. Please login.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare("
                INSERT INTO users
                (
                    first_name,
                    last_name,
                    dob,
                    gender,
                    email,
                    phone,
                    password,
                    is_active,
                    created_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");

            $insert->execute([
                $firstName,
                $lastName,
                $dob,
                $gender,
                $email,
                $phone,
                $hashedPassword
            ]);

            $userId = (int) $pdo->lastInsertId();

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            $_SESSION['user_email'] = $email;

            redirect('available_quizzes.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Quiz System</title>

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

.container{
    max-width:520px;
    width:100%;
    background:#fff;
    border-radius:24px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    padding:32px 28px;
}

h2{
    font-size:28px;
    font-weight:600;
    color:#0f172a;
    margin-bottom:6px;
}

.sub{
    color:#475569;
    margin-bottom:26px;
    font-size:14px;
    border-left:3px solid #3b82f6;
    padding-left:12px;
}

label{
    display:block;
    margin-top:14px;
    margin-bottom:6px;
    font-size:14px;
    font-weight:500;
    color:#1e293b;
}

input,
select{
    width:100%;
    padding:10px 14px;
    border:1px solid #cbd5e1;
    border-radius:12px;
    font-size:15px;
    font-family:inherit;
}

input:focus,
select:focus{
    outline:none;
    border-color:#3b82f6;
    box-shadow:0 0 0 3px rgba(59,130,246,.2);
}

input[readonly]{
    background:#f1f5f9;
}

button{
    width:100%;
    margin-top:24px;
    padding:12px;
    border:none;
    border-radius:40px;
    background:#3b82f6;
    color:#fff;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
}

button:hover{
    background:#2563eb;
}

.error{
    background:#fee2e2;
    border-left:4px solid #ef4444;
    padding:12px 16px;
    border-radius:12px;
    margin-bottom:20px;
}

.error ul{
    padding-left:20px;
    color:#b91c1c;
}

.login-link{
    text-align:center;
    margin-top:20px;
    font-size:14px;
    color:#475569;
}

.login-link a{
    color:#3b82f6;
    text-decoration:none;
    font-weight:500;
}

hr{
    margin:20px 0 10px;
    border:none;
    border-top:1px solid #e2e8f0;
}
</style>

<script>
function calculateAge() {
    const dob = document.getElementById('dob').value;
    if (!dob) {
        document.getElementById('age').value = '';
        return;
    }

    const birthDate = new Date(dob);
    const today = new Date();

    let age = today.getFullYear() - birthDate.getFullYear();

    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }

    document.getElementById('age').value = age;
}

window.addEventListener('DOMContentLoaded', calculateAge);
</script>
</head>
<body>

<div class="container">
    <h2>Create Account</h2>
    <div class="sub">Register to participate in quizzes</div>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">

        <label>First Name</label>
        <input type="text" name="first_name" required value="<?php echo htmlspecialchars($firstName); ?>">

        <label>Last Name</label>
        <input type="text" name="last_name" required value="<?php echo htmlspecialchars($lastName); ?>">

        <label>Date of Birth</label>
        <input type="date" name="dob" id="dob" required onchange="calculateAge()" value="<?php echo htmlspecialchars($dob); ?>">

        <label>Age</label>
        <input type="number" id="age" readonly>

        <label>Gender</label>
        <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male" <?php echo ($gender === 'Male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo ($gender === 'Female') ? 'selected' : ''; ?>>Female</option>
            <option value="Other" <?php echo ($gender === 'Other') ? 'selected' : ''; ?>>Other</option>
        </select>

        <label>Email Address</label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">

        <label>Phone Number</label>
        <input type="tel" name="phone" maxlength="10" required value="<?php echo htmlspecialchars($phone); ?>">

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required>

        <button type="submit">Register & Continue</button>
    </form>

    <hr>

    <div class="login-link">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

</body>
</html>