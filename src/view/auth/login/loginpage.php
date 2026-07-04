<?php
require_once __DIR__ . '/../../../../src/config/bootstrap.php';

if (check_logged_in()) {
    if (check_admin()) {
        header("Location: " . url('/src/view/admin/landing/dashboard.php'));
    } elseif (check_staff()) {
        header("Location: " . url('/src/view/staff/dashboard.php'));
    } else {
        header("Location: " . url('/src/view/student/dashboard.php'));
    }
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid session token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                if ($user['role'] === 'admin') {
                    header("Location: " . url('/src/view/admin/landing/dashboard.php'));
                } elseif ($user['role'] === 'staff') {
                    header("Location: " . url('/src/view/staff/dashboard.php'));
                } else {
                    header("Location: " . url('/src/view/student/dashboard.php'));
                }
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — NCST Violation Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>

<body>

    <nav class="absolute top-0 left-0 w-full p-4 md:p-8 z-10">
        <a href="<?= url('/src/view/guest/landing/landingpage.php') ?>"
            class="inline-flex items-center gap-2 text-sm font-bold text-black/60 hover:text-black transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back
        </a>
    </nav>

    <div class="min-h-screen flex items-center justify-center p-4 md:p-8">
        <div
            class="w-full border border-black/10 max-w-[1040px] bg-black/5 rounded-[32px] md:rounded-[40px] overflow-hidden relative flex flex-col md:flex-row min-h-[500px]">

            <div class="hidden md:flex md:w-5/12 bg-white p-8 flex-col">
                <div class="flex-1 flex flex-col justify-center">
                    <div class="mb-6">
                    </div>
                    <h1 class="text-5xl font-black tracking-tighter italic uppercase leading-tight mb-4">
                        Sign in to<br>NCST
                    </h1>
                    <p class="text-lg text-black/60 font-medium">Use your NCST Account</p>
                </div>
                <div class="mt-auto pt-8">
                    <p class="text-sm font-medium text-black/40">
                        Don't have an account?
                        <a href="<?= url('/src/view/auth/register/register.php') ?>"
                            class="text-google-blue font-bold hover:underline">Create account</a>
                    </p>
                </div>
            </div>

            <div class="w-full md:w-7/12 p-6 md:p-8 flex flex-col justify-center">
                <?php if ($error): ?>
                    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                        <p class="text-sm font-bold text-red-600"><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div
                        class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                        <label class="text-xs font-medium text-black/50">Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required
                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                            placeholder="you@ncst.edu.ph">
                    </div>

                    <div
                        class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                        <label class="text-xs font-medium text-black/50">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="loginPassword" required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 pr-8 placeholder:text-black/30"
                                placeholder="Enter your password">
                            <button type="button" onclick="togglePassword('loginPassword', this)"
                                class="absolute right-0 top-1/2 -translate-y-1/2 text-black/40 hover:text-black/60">
                                <svg class="w-5 h-5 eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="w-5 h-5 eye-off-icon hidden" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-1">
                        <a href="#" class="text-sm font-bold text-google-blue hover:underline">Forgot password?</a>
                    </div>

                    <button type="submit"
                        class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95 disabled:opacity-50">
                        Login
                    </button>
                </form>

                <div class="relative border-t border-black/10 mt-6 pt-6">
                    <div class="flex items-center justify-center gap-2 mb-4">
                        <button type="button"
                            class="w-full flex items-center justify-center gap-2 bg-black/5 hover:bg-black/10 border border-black/10 rounded-full py-2.5 text-xs font-bold text-black/70 transition-colors">
                            <svg class="w-5 h-5" viewBox="0 0 48 48">
                                <path fill="#FFC107"
                                    d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z" />
                                <path fill="#FF3D00"
                                    d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z" />
                                <path fill="#4CAF50"
                                    d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0124 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z" />
                                <path fill="#1976D2"
                                    d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 01-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z" />
                            </svg>
                            Sign in with Google
                        </button>
                    </div>
                </div>

                <div class="md:hidden mt-8 pt-6 border-t border-black/10 text-center">
                    <p class="text-sm font-medium text-black/40">
                        Don't have an account?
                        <a href="<?= url('/src/view/auth/register/register.php') ?>"
                            class="text-google-blue font-bold hover:underline">Create account</a>
                    </p>
                </div>
            </div>

        </div>
    </div>

    <script src="<?= url('/assets/js/main.js') ?>"></script>
</body>

</html>