<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path === '/' || $path === '') {
    require __DIR__ . '/index.php';
    return true;
}

if (is_file($file)) {
    return false;
}

$_GET['error'] = '404';
require __DIR__ . '/src/view/guest/errors/errorpage.php';
return true;
