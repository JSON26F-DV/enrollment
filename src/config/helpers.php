<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/enrollment');
}
function url($path = '') {
    return BASE_URL . $path;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
