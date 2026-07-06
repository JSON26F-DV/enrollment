<?php
$page_title = 'Accounts';
require_once __DIR__ . '/sidebar.php';

$errors = [];
$success = '';
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $old = $_POST;
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $birthday = trim($_POST['birthday'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $role = trim($_POST['role'] ?? 'staff');
        $password = $_POST['password'] ?? '';

        if (empty($first_name)) $errors[] = 'First name is required.';
        if (empty($last_name)) $errors[] = 'Last name is required.';
        if (empty($email)) $errors[] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
        if (empty($birthday)) $errors[] = 'Birthday is required.';
        if (empty($gender)) $errors[] = 'Gender is required.';
        if (empty($contact_number)) $errors[] = 'Contact number is required.';
        if (empty($password)) $errors[] = 'Password is required.';
        elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) $errors[] = 'Email already exists.';
        } catch (PDOException $e) {}

        if (empty($errors)) {
            try {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (first_name, last_name, birthday, gender, contact_number, email, password, role, status, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
                ");
                $stmt->execute([$first_name, $last_name, $birthday, $gender, $contact_number, $email, $hashed, $role, $_SESSION['user_id']]);
                $success = 'Account created successfully!';
                $old = [];
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $delete_id = (int)($_POST['delete_id'] ?? 0);
    if ($delete_id > 0 && $delete_id != $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            $success = 'Account deleted successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Cannot delete account.';
        }
    }
}

$filter_role = $_GET['filter'] ?? '';
$where = "WHERE role NOT IN ('shs', 'college')";
if ($filter_role === 'admin') $where .= " AND role = 'admin'";
elseif ($filter_role === 'staff') $where .= " AND role = 'staff'";
elseif ($filter_role === 'registrar') $where .= " AND role = 'registrar'";

try {
    $stmt = $pdo->query("SELECT * FROM users $where ORDER BY created_at DESC");
    $accounts = $stmt->fetchAll();
} catch (Exception $e) {
    $accounts = [];
}
?>

<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Accounts</h1>
        <p class="text-gray-500 mt-1">Manage admin and staff accounts</p>
    </div>
    <a href="<?= url('/src/view/admin/manageaccount.php') ?>" 
       class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Create Account
    </a>
</div>

<div class="flex gap-2 mb-6">
    <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium <?= !$filter_role ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">All</a>
    <a href="?filter=admin" class="px-4 py-2 rounded-lg text-sm font-medium <?= $filter_role === 'admin' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">Admin</a>
    <a href="?filter=staff" class="px-4 py-2 rounded-lg text-sm font-medium <?= $filter_role === 'staff' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">Staff</a>
    <a href="?filter=registrar" class="px-4 py-2 rounded-lg text-sm font-medium <?= $filter_role === 'registrar' ? 'bg-amber-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">Registrar</a>
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

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($accounts)): ?>
        <div class="p-8 text-center text-gray-500">
            <p>No accounts found.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($accounts as $acc): ?>
                        <?php if ($acc['id'] == $_SESSION['user_id']) continue; ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 font-medium text-gray-900"><?= htmlspecialchars($acc['first_name'] . ' ' . $acc['last_name']) ?></td>
                            <td class="px-4 py-4 text-gray-500"><?= htmlspecialchars($acc['email']) ?></td>
                            <td class="px-4 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $acc['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                                    <?= ucfirst($acc['role']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $acc['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= ucfirst($acc['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500"><?= date('M d, Y', strtotime($acc['created_at'])) ?></td>
                            <td class="px-4 py-4 text-right space-x-3">
                                <a href="<?= url('/src/view/admin/manageaccount.php?id=' . $acc['id']) ?>"
                                   class="text-blue-600 hover:underline text-sm">Edit</a>
                                <button onclick="deleteAccount(<?= $acc['id'] ?>, '<?= htmlspecialchars(addslashes($acc['first_name'] . ' ' . $acc['last_name'])) ?>')" 
                                        class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl max-w-sm w-full mx-4 p-6">
        <h3 class="text-lg font-bold mb-2">Delete Account</h3>
        <p class="text-gray-600 mb-6">Are you sure you want to delete <span id="deleteName" class="font-medium"></span>?</p>
        <form method="POST" id="deleteForm" class="flex gap-3">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="delete_id" id="deleteIdInput">
            <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden');document.getElementById('deleteModal').classList.remove('flex');" class="flex-1 px-4 py-2 border rounded-lg font-medium hover:bg-gray-50">Cancel</button>
            <button type="submit" name="delete_account" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700">Delete</button>
        </form>
    </div>
</div>

<script>
function deleteAccount(id, name) {
    document.getElementById('deleteIdInput').value = id;
    document.getElementById('deleteName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}
</script>

</main>
</div>
</body>
</html>