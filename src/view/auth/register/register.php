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

$errors = [];
$success = '';
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $old = $_POST;
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $birthday = trim($_POST['birthday'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($first_name))
            $errors[] = 'First name is required.';
        if (empty($last_name))
            $errors[] = 'Last name is required.';
        if (empty($email))
            $errors[] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = 'Invalid email format.';
        if (empty($birthday))
            $errors[] = 'Birthday is required.';
        if (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other']))
            $errors[] = 'Gender is required.';
        if (empty($contact_number))
            $errors[] = 'Contact number is required.';
        if (empty($password))
            $errors[] = 'Password is required.';
        elseif (strlen($password) < 8)
            $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm_password)
            $errors[] = 'Passwords do not match.';

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch())
            $errors[] = 'An account with this email already exists.';

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, birthday, gender, contact_number, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'student', 'pending')");
            $stmt->execute([$first_name, $last_name, $birthday, $gender, $contact_number, $email, $hashed]);
            $success = 'Registration successful! You can now log in.';
            $old = [];
        }
    }
}

$step_labels = ['Enter your name', 'Basic information', 'Contact number', 'Set your password'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — NCST Violation Management System</title>
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
            class="w-full border border-black/10 max-w-[1040px] bg-black/5 rounded-[32px] md:rounded-[40px] overflow-hidden relative flex flex-col md:flex-row min-h-[560px]">

            <div class="hidden md:flex md:w-5/12 bg-white p-8 flex-col">
                <div class="flex-1 flex flex-col justify-center">
                    <div class="mb-6">
                        <img src="<?= url('/public/images/ncst.png') ?>" alt="NCST"
                            class="w-12 h-12 rounded-2xl object-cover shadow shadow-pixs-mint/20">
                    </div>
                    <h1 class="text-5xl font-black tracking-tighter italic uppercase leading-tight mb-4">
                        Create a<br>NCST Account
                    </h1>
                    <p id="stepLabel" class="text-lg text-black/60 font-medium"><?= $step_labels[0] ?></p>
                </div>
                <div class="mt-auto pt-8">
                    <p class="text-sm font-medium text-black/40">
                        Already have an account?
                        <a href="<?= url('/src/view/auth/login/loginpage.php') ?>"
                            class="text-google-blue font-bold hover:underline">Sign in</a>
                    </p>
                </div>
            </div>

            <div class="w-full md:w-7/12 p-6 md:p-8 flex flex-col justify-center">

                <?php if (!empty($errors)): ?>
                    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                        <?php foreach ($errors as $e): ?>
                            <p class="text-sm font-bold text-red-600"><?= htmlspecialchars($e) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-xl">
                        <p class="text-sm font-bold text-green-600"><?= htmlspecialchars($success) ?></p>
                    </div>
                    <div class="text-center">
                        <a href="<?= url('/src/view/auth/login/loginpage.php') ?>"
                            class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95 inline-block">Proceed
                            to Login</a>
                    </div>
                <?php else: ?>

                    <form id="registerForm" method="POST" action="" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="current_step" id="currentStep" value="1">

                        <!-- Step 1: Name -->
                        <div class="step" data-step="1">
                            <div class="space-y-4">
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">First Name</label>
                                    <input type="text" name="first_name"
                                        value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="Juan">
                                </div>
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Last Name</label>
                                    <input type="text" name="last_name"
                                        value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="Dela Cruz">
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Basic Info -->
                        <div class="step hidden" data-step="2">
                            <div class="space-y-4">
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Email Address</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                                        required
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="you@ncst.edu.ph">
                                </div>
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Birthdate</label>
                                    <input type="date" name="birthday"
                                        value="<?= htmlspecialchars($old['birthday'] ?? '') ?>" required
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                </div>
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Gender</label>
                                    <select name="gender" required
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                        <option value="" disabled <?= empty($old['gender']) ? 'selected' : '' ?>>Select
                                            gender</option>
                                        <option value="Male" <?= ($old['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male
                                        </option>
                                        <option value="Female" <?= ($old['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>
                                            Female</option>
                                        <option value="Other" <?= ($old['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Contact Number -->
                        <div class="step hidden" data-step="3">
                            <div class="space-y-4">
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Mobile Number</label>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-sm font-bold text-black/50 whitespace-nowrap">+63</span>
                                        <input type="tel" name="contact_number"
                                            value="<?= htmlspecialchars($old['contact_number'] ?? '') ?>" required
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none py-1 placeholder:text-black/30"
                                            placeholder="912 345 6789" pattern="[0-9\s\-]{7,15}">
                                    </div>
                                </div>
                                <p class="text-xs font-medium text-black/40">Enter your mobile number starting with the
                                    network prefix (e.g., 9123456789).</p>
                            </div>
                        </div>

                        <!-- Step 4: Password -->
                        <div class="step hidden" data-step="4">
                            <div class="space-y-4">
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Password</label>
                                    <div class="relative">
                                        <input type="password" name="password" id="regPassword" required
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 pr-8 placeholder:text-black/30"
                                            placeholder="Minimum 8 characters">
                                        <button type="button" onclick="togglePassword('regPassword', this)"
                                            class="absolute right-0 top-1/2 -translate-y-1/2 text-black/40 hover:text-black/60">
                                            <svg class="w-5 h-5 eye-icon" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
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
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Confirm Password</label>
                                    <div class="relative">
                                        <input type="password" name="confirm_password" id="regConfirmPassword" required
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 pr-8 placeholder:text-black/30"
                                            placeholder="Re-enter your password">
                                        <button type="button" onclick="togglePassword('regConfirmPassword', this)"
                                            class="absolute right-0 top-1/2 -translate-y-1/2 text-black/40 hover:text-black/60">
                                            <svg class="w-5 h-5 eye-icon" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
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
                                <div class="flex items-center gap-2 text-xs font-medium text-black/40">
                                    <svg class="w-4 h-4" id="reqLength" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 15v2m0 0v2m0-2h2m-2 0H10" />
                                    </svg>
                                    <span id="reqLengthText">At least 8 characters</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step Indicators -->
                        <div class="flex gap-1.5 mt-6 mb-4">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div class="h-1 flex-1 rounded-full step-dot <?= $i === 1 ? 'bg-google-blue' : 'bg-black/20' ?>"
                                    data-step="<?= $i ?>"></div>
                            <?php endfor; ?>
                        </div>

                        <!-- Navigation -->
                        <div class="flex items-center justify-between">
                            <button type="button" id="backBtn"
                                class="px-6 py-2 text-sm font-bold text-google-blue hover:bg-google-blue/10 rounded-full transition-colors active:scale-95 hidden">Back</button>
                            <div></div>
                            <button type="button" id="nextBtn"
                                class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95">Next</button>
                            <button type="submit" name="register" id="submitBtn"
                                class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95 hidden">Create
                                Account</button>
                        </div>

                        <!-- Google Sign Up (step 1 only) -->
                        <div id="googleSignUp" class="relative border-t border-black/10 mt-6 pt-6">
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
                                    Sign up with Google
                                </button>
                            </div>
                        </div>

                    </form>

                    <div class="md:hidden mt-8 pt-6 border-t border-black/10 text-center">
                        <p class="text-sm font-medium text-black/40">
                            Already have an account?
                            <a href="<?= url('/src/view/auth/login/loginpage.php') ?>"
                                class="text-google-blue font-bold hover:underline">Sign in</a>
                        </p>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="<?= url('/assets/js/main.js') ?>"></script>
</body>

</html>