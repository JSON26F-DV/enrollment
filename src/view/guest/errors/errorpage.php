<?php
require_once __DIR__ . '/../../../../header.php';

$error_code = $_GET['error'] ?? '404';
$messages = [
    '403' => ['Access Denied', 'You do not have permission to view this page.'],
    '404' => ['Page Not Found', 'The page you are looking for does not exist.'],
    '500' => ['Server Error', 'An internal server error occurred. Please try again later.'],
];
$msg = $messages[$error_code] ?? $messages['404'];
?>
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <h1 class="text-8xl font-black tracking-tighter italic text-black/10"><?= $error_code ?></h1>
        <h2 class="text-2xl font-black tracking-tighter italic uppercase mt-4"><?= $msg[0] ?></h2>
        <p class="text-sm font-medium text-black/60 mt-2"><?= $msg[1] ?></p>
        <div class="mt-8 flex items-center justify-center gap-4">
            <a href="<?= url('/src/view/guest/landing/landingpage.php') ?>"
                class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95">Go
                Home</a>
            <a href="<?= url('/src/view/auth/login/loginpage.php') ?>"
                class="px-6 py-2 text-sm font-bold text-google-blue hover:bg-google-blue/10 rounded-full transition-colors">Login</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../../../footer.php'; ?>