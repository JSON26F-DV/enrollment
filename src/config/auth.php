<?php
function check_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function check_admin() {
    return check_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function check_student() {
    return check_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function check_staff() {
    return check_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

function require_login() {
    if (!check_logged_in()) {
        header("Location: " . url('/src/view/auth/login/loginpage.php'));
        exit;
    }
}

function require_admin() {
    require_login();
    if (!check_admin()) {
        header("Location: " . url('/src/view/guest/errors/errorpage.php?error=403'));
        exit;
    }
}

function require_student() {
    require_login();
    if (!check_student()) {
        header("Location: " . url('/src/view/guest/errors/errorpage.php?error=403'));
        exit;
    }
}

function require_staff() {
    require_login();
    if (!check_staff()) {
        header("Location: " . url('/src/view/guest/errors/errorpage.php?error=403'));
        exit;
    }
}
