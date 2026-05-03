<?php

function sanitize($input)
{
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function calculateAge($dob)
{
    if (empty($dob)) {
        return 0;
    }

    $birthDate = new DateTime($dob);
    $today = new DateTime();

    return $today->diff($birthDate)->y;
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

function post($key, $default = '')
{
    return trim($_POST[$key] ?? $default);
}

function get($key, $default = '')
{
    return trim($_GET[$key] ?? $default);
}
?>