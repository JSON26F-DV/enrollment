<?php
require_once __DIR__ . '/../../../../header.php';
require_admin();

$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = $edit_id > 0;

$errors = [];
$success = '';
$old = [];

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_deleted = FALSE");
    $stmt->execute([$edit_id]);
    $user = $stmt->fetch();
    if (!$user) {
        header("Location: " . url('/src/view/admin/landing/dashboard.php'));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
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
        $status = trim($_POST['status'] ?? 'active');

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
        if (empty($status) || !in_array($status, ['pending', 'active', 'inactive']))
            $errors[] = 'Status is invalid.';

        if (!$is_edit) {
            if (empty($password))
                $errors[] = 'Password is required.';
            elseif (strlen($password) < 8)
                $errors[] = 'Password must be at least 8 characters.';
        } else {
            if (!empty($password) && strlen($password) < 8)
                $errors[] = 'Password must be at least 8 characters.';
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $edit_id ?: 0]);
        if ($stmt->fetch())
            $errors[] = 'An account with this email already exists.';

        if (empty($errors)) {
            if ($is_edit) {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, birthday = ?, gender = ?, contact_number = ?, status = ?, password = ? WHERE id = ?");
                    $stmt->execute([$first_name, $last_name, $email, $birthday, $gender, $contact_number, $status, $hashed, $edit_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, birthday = ?, gender = ?, contact_number = ?, status = ? WHERE id = ?");
                    $stmt->execute([$first_name, $last_name, $email, $birthday, $gender, $contact_number, $status, $edit_id]);
                }
                $success = 'Student updated successfully!';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, birthday, gender, contact_number, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'student', ?)");
                $stmt->execute([$first_name, $last_name, $birthday, $gender, $contact_number, $email, $hashed, $status]);
                $success = 'Student registered successfully!';
                $old = [];
            }
        }
    }
}

$page_title = $is_edit ? 'Edit Student' : 'Register Student';
?>

<div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="<?= url('/src/view/admin/landing/dashboard.php') ?>"
                class="inline-flex items-center gap-1 text-sm font-bold text-google-blue hover:underline">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </a>
            <h1 class="text-4xl font-black tracking-tighter italic uppercase"><?= $page_title ?></h1>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
            <?php foreach ($errors as $e): ?>
                <p class="text-sm font-bold text-red-600"><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 rounded-xl">
            <p class="text-sm font-bold text-green-600"><?= htmlspecialchars($success) ?></p>
        </div>
        <a href="<?= url('/src/view/admin/landing/dashboard.php') ?>"
            class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95 inline-block">Back
            to Dashboard</a>
    <?php else: ?>

        <div class="border border-black/10 rounded-[32px] p-6 md:p-8 bg-white max-w-2xl">
            <form method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div
                            class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">First Name</label>
                            <input type="text" name="first_name"
                                value="<?= htmlspecialchars($old['first_name'] ?? ($is_edit ? $user['first_name'] : '')) ?>"
                                required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                placeholder="Juan">
                        </div>
                        <div
                            class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Last Name</label>
                            <input type="text" name="last_name"
                                value="<?= htmlspecialchars($old['last_name'] ?? ($is_edit ? $user['last_name'] : '')) ?>"
                                required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                placeholder="Dela Cruz">
                        </div>
                    </div>

                    <div
                        class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                        <label class="text-xs font-medium text-black/50">Email Address</label>
                        <input type="email" name="email"
                            value="<?= htmlspecialchars($old['email'] ?? ($is_edit ? $user['email'] : '')) ?>" required
                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                            placeholder="you@ncst.edu.ph">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                            class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Birthdate</label>
                            <input type="date" name="birthday"
                                value="<?= htmlspecialchars($old['birthday'] ?? ($is_edit ? $user['birthday'] : '')) ?>"
                                required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                        </div>
                        <div
                            class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Gender</label>
                            <select name="gender" required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                <option value="" disabled <?= empty($old['gender'] ?? ($is_edit ? $user['gender'] : '')) ? 'selected' : '' ?>>Select gender</option>
                                <option value="Male" <?= (($old['gender'] ?? ($is_edit ? $user['gender'] : '')) === 'Male') ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= (($old['gender'] ?? ($is_edit ? $user['gender'] : '')) === 'Female') ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= (($old['gender'] ?? ($is_edit ? $user['gender'] : '')) === 'Other') ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div
                            class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Status</label>
                            <select name="status" required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                <option value="pending" <?= (($old['status'] ?? ($is_edit ? $user['status'] : 'active')) === 'pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="active" <?= (($old['status'] ?? ($is_edit ? $user['status'] : 'active')) === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= (($old['status'] ?? ($is_edit ? $user['status'] : 'active')) === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div
                        class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                        <label class="text-xs font-medium text-black/50">Mobile Number</label>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-sm font-bold text-black/50 whitespace-nowrap">+63</span>
                            <input type="tel" name="contact_number"
                                value="<?= htmlspecialchars($old['contact_number'] ?? ($is_edit ? $user['contact_number'] : '')) ?>"
                                required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none py-1 placeholder:text-black/30"
                                placeholder="912 345 6789" pattern="[0-9\s\-]{7,15}">
                        </div>
                    </div>

                    <div
                        class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                        <label class="text-xs font-medium text-black/50">Password
                            <?= $is_edit ? '(leave blank to keep current)' : '' ?></label>
                        <input type="password" name="password" <?= $is_edit ? '' : 'required' ?>
                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                            placeholder="<?= $is_edit ? 'Leave blank to keep current' : 'Minimum 8 characters' ?>">
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-6">
                    <button type="submit" name="save"
                        class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95"><?= $is_edit ? 'Update Student' : 'Register Student' ?></button>
                    <a href="<?= url('/src/view/admin/landing/dashboard.php') ?>"
                        class="px-6 py-2.5 text-sm font-bold text-black/60 hover:text-black border border-black/10 rounded-full transition-colors active:scale-95">Cancel</a>
                </div>
            </form>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../footer.php'; ?>