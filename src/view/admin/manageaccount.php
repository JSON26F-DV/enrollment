<?php
$page_title = 'Manage Account';
require_once __DIR__ . '/sidebar.php';

$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = $edit_id > 0;

$errors = [];
$success = '';
$old = [];

$user = [];

if ($is_edit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role NOT IN ('shs','college')");
        $stmt->execute([$edit_id]);
        $user = $stmt->fetch();
        if (!$user) {
            header("Location: " . url('/src/view/admin/accounts.php'));
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $old = $_POST;

        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $suffix = trim($_POST['suffix'] ?? '');
        $birthday = trim($_POST['birthday'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $civil_status = trim($_POST['civil_status'] ?? 'Single');
        $nationality = trim($_POST['nationality'] ?? 'Filipino');
        $religion = trim($_POST['religion'] ?? '');
        $birth_place = trim($_POST['birth_place'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $home_address = trim($_POST['home_address'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = trim($_POST['role'] ?? 'staff');
        $status = trim($_POST['status'] ?? 'active');

        if (empty($first_name)) $errors[] = 'First name is required.';
        if (empty($last_name)) $errors[] = 'Last name is required.';
        if (empty($email)) $errors[] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
        if (empty($birthday)) $errors[] = 'Birthday is required.';
        if (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other'])) $errors[] = 'Gender is required.';
        if (empty($contact_number)) $errors[] = 'Contact number is required.';
        if (!in_array($role, ['admin', 'staff', 'registrar'])) $errors[] = 'Invalid role.';
        if (!$is_edit && empty($password)) $errors[] = 'Password is required.';
        elseif (!empty($password) && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $edit_id ?: 0]);
        if ($stmt->fetch()) $errors[] = 'An account with this email already exists.';

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                if ($is_edit) {
                    if (!empty($password)) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, suffix=?, birthday=?, gender=?, civil_status=?, nationality=?, religion=?, birth_place=?, email=?, contact_number=?, home_address=?, province=?, city=?, barangay=?, zip_code=?, status=?, role=?, password=? WHERE id=?");
                        $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $birthday, $gender, $civil_status, $nationality, $religion, $birth_place, $email, $contact_number, $home_address, $province, $city, $barangay, $zip_code, $status, $role, $hashed, $edit_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, suffix=?, birthday=?, gender=?, civil_status=?, nationality=?, religion=?, birth_place=?, email=?, contact_number=?, home_address=?, province=?, city=?, barangay=?, zip_code=?, status=?, role=? WHERE id=?");
                        $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $birthday, $gender, $civil_status, $nationality, $religion, $birth_place, $email, $contact_number, $home_address, $province, $city, $barangay, $zip_code, $status, $role, $edit_id]);
                    }
                    $success = 'Account updated successfully!';
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, suffix, birthday, gender, civil_status, nationality, religion, birth_place, email, contact_number, home_address, province, city, barangay, zip_code, password, role, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $birthday, $gender, $civil_status, $nationality, $religion, $birth_place, $email, $contact_number, $home_address, $province, $city, $barangay, $zip_code, $hashed, $role, $status, $_SESSION['user_id']]);
                    $edit_id = (int) $pdo->lastInsertId();
                    $success = 'Account created successfully!';
                }

                $pdo->commit();

                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$edit_id]);
                $user = $stmt->fetch();

                $old = [];
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soft_delete'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } elseif (!$is_edit) {
        $errors[] = 'Account not found.';
    } else {
        $admin_password = $_POST['admin_password'] ?? '';
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($admin_password, $admin['password'])) {
            $errors[] = 'Invalid admin password.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET deleted_at = CURDATE(), status = 'inactive' WHERE id = ? AND role NOT IN ('shs','college')");
            $stmt->execute([$edit_id]);
            $success = 'Account has been soft-deleted.';
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$edit_id]);
            $user = $stmt->fetch();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_account'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } elseif (!$is_edit) {
        $errors[] = 'Account not found.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET deleted_at = NULL, status = 'active' WHERE id = ? AND role NOT IN ('shs','college')");
        $stmt->execute([$edit_id]);
        $success = 'Account restored successfully.';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$edit_id]);
        $user = $stmt->fetch();
    }
}
?>
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900"><?= $is_edit ? 'Edit Account' : 'New Account' ?></h1>
        <p class="text-gray-500 mt-1"><?= $is_edit ? 'Update admin, staff, or registrar account' : 'Create a new admin, staff, or registrar account' ?></p>
    </div>
    <a href="<?= url('/src/view/admin/accounts.php') ?>" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 border rounded-lg hover:bg-gray-50 transition-colors">Back to Accounts</a>
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
<?php endif; ?>

<form method="POST" action="" class="space-y-8">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <!-- Personal Information -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Personal Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($old['first_name'] ?? $user['first_name'] ?? '') ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($old['middle_name'] ?? $user['middle_name'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($old['last_name'] ?? $user['last_name'] ?? '') ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Suffix</label>
                <input type="text" name="suffix" value="<?= htmlspecialchars($old['suffix'] ?? $user['suffix'] ?? '') ?>" placeholder="Jr., III, etc."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
                <input type="date" name="birthday" value="<?= htmlspecialchars($old['birthday'] ?? $user['birthday'] ?? '') ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select name="gender" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <option value="">Select</option>
                    <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                        <option value="<?= $g ?>" <?= (($old['gender'] ?? $user['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Civil Status</label>
                <select name="civil_status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <?php foreach (['Single', 'Married', 'Widowed', 'Separated', 'Annulled'] as $cs): ?>
                        <option value="<?= $cs ?>" <?= (($old['civil_status'] ?? $user['civil_status'] ?? 'Single') === $cs) ? 'selected' : '' ?>><?= $cs ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nationality</label>
                <input type="text" name="nationality" value="<?= htmlspecialchars($old['nationality'] ?? $user['nationality'] ?? 'Filipino') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                <input type="text" name="religion" value="<?= htmlspecialchars($old['religion'] ?? $user['religion'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div class="md:col-span-2 lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Birth Place</label>
                <input type="text" name="birth_place" value="<?= htmlspecialchars($old['birth_place'] ?? $user['birth_place'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Contact Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $user['email'] ?? '') ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                <input type="text" name="contact_number" value="<?= htmlspecialchars($old['contact_number'] ?? $user['contact_number'] ?? '') ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Home Address</label>
                <input type="text" name="home_address" value="<?= htmlspecialchars($old['home_address'] ?? $user['home_address'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                <input type="text" name="province" value="<?= htmlspecialchars($old['province'] ?? $user['province'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City / Municipality</label>
                <input type="text" name="city" value="<?= htmlspecialchars($old['city'] ?? $user['city'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                <input type="text" name="barangay" value="<?= htmlspecialchars($old['barangay'] ?? $user['barangay'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Zip Code</label>
                <input type="text" name="zip_code" value="<?= htmlspecialchars($old['zip_code'] ?? $user['zip_code'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
        </div>
    </div>

    <!-- Account Settings -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Account</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <?php foreach (['staff', 'admin', 'registrar'] as $r): ?>
                        <option value="<?= $r ?>" <?= (($old['role'] ?? $user['role'] ?? 'staff') === $r) ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <?php foreach (['active', 'inactive', 'pending'] as $st): ?>
                        <option value="<?= $st ?>" <?= (($old['status'] ?? $user['status'] ?? 'active') === $st) ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password <?= $is_edit ? '<span class="text-gray-400 font-normal">(leave blank to keep)</span>' : '' ?></label>
                <input type="password" name="password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none"
                    placeholder="<?= $is_edit ? 'Leave blank to keep current' : 'Min. 8 characters' ?>">
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" name="save"
            class="px-6 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
            <?= $is_edit ? 'Update Account' : 'Create Account' ?>
        </button>
        <a href="<?= url('/src/view/admin/accounts.php') ?>"
            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">Cancel</a>
        <?php if ($is_edit): ?>
            <?php if (!empty($user['deleted_at'])): ?>
                <form method="POST" class="inline" onsubmit="return confirm('Restore this account?')">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="restore_account" value="1">
                    <button type="submit"
                        class="px-6 py-2.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">Restore Account</button>
                </form>
            <?php elseif ($edit_id != $_SESSION['user_id']): ?>
                <button type="button" onclick="openDeleteModal()"
                    class="px-6 py-2.5 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">Delete Account</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</form>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[80]" onclick="if(event.target===this)closeDeleteModal()">
    <div class="bg-white rounded-2xl max-w-md w-full m-4">
        <div class="border-b px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-red-700">Delete Account</h2>
            <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="soft_delete" value="1">

            <div class="p-4 bg-red-50 rounded-lg text-sm text-red-800">
                <strong>Warning:</strong> This will soft-delete the account. Enter your admin password to confirm.
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-bold text-gray-900 mb-2">Account Info</h3>
                <dl class="text-xs space-y-1">
                    <div class="flex"><dt class="w-28 text-gray-500">Name:</dt><dd class="font-medium"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></dd></div>
                    <div class="flex"><dt class="w-28 text-gray-500">Email:</dt><dd><?= htmlspecialchars($user['email'] ?? '') ?></dd></div>
                    <div class="flex"><dt class="w-28 text-gray-500">Role:</dt><dd><?= htmlspecialchars($user['role'] ?? '') ?></dd></div>
                    <div class="flex"><dt class="w-28 text-gray-500">Status:</dt><dd><?= htmlspecialchars($user['status'] ?? '') ?></dd></div>
                </dl>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Admin Password *</label>
                <input type="password" name="admin_password" id="deletePassword" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none"
                    placeholder="Enter your admin password to confirm">
            </div>

            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Confirm Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDeleteModal() {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
        document.getElementById('deletePassword').value = '';
    }

    document.getElementById('deleteModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
</script>

</main>
</div>
</body>
</html>
