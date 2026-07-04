<?php
require_once __DIR__ . '/../../../../header.php';
require_admin();

$total_users = 0;
$stmt = $pdo->query("SELECT COUNT(*) as c FROM users WHERE deleted_at IS NULL");
if ($stmt)
    $total_users = $stmt->fetch()['c'];

$total_admins = 0;
$stmt = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role = 'admin' AND deleted_at IS NULL");
if ($stmt)
    $total_admins = $stmt->fetch()['c'];

$total_staff = 0;
$stmt = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role = 'staff' AND deleted_at IS NULL");
if ($stmt)
    $total_staff = $stmt->fetch()['c'];

$total_students = 0;
$stmt = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role = 'student' AND deleted_at IS NULL");
if ($stmt)
    $total_students = $stmt->fetch()['c'];

$total_deleted = 0;
$stmt = $pdo->query("SELECT COUNT(*) as c FROM users WHERE deleted_at IS NOT NULL");
if ($stmt)
    $total_deleted = $stmt->fetch()['c'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    if ($delete_id > 0) {
        $stmt = $pdo->prepare("UPDATE users SET is_deleted = TRUE, deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$delete_id]);
    }
    header("Location: " . url('/src/view/admin/landing/dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['undo_id'])) {
    $undo_id = (int) $_POST['undo_id'];
    if ($undo_id > 0) {
        $stmt = $pdo->prepare("UPDATE users SET is_deleted = FALSE, deleted_at = NULL WHERE id = ?");
        $stmt->execute([$undo_id]);
    }
    header("Location: " . url('/src/view/admin/landing/dashboard.php'));
    exit;
}

$filter_role = isset($_GET['filter']) ? $_GET['filter'] : '';
$where_extra = '';
if ($filter_role === 'admin') {
    $where_extra = "AND c.role = 'admin'";
} elseif ($filter_role === 'staff') {
    $where_extra = "AND c.role = 'staff'";
} elseif ($filter_role === 'guest') {
    $where_extra = "AND (u.created_by IS NULL OR c.role NOT IN ('admin', 'staff'))";
}

$sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.status, u.created_at, u.created_by,
               c.first_name AS creator_first, c.last_name AS creator_last, c.role AS creator_role
        FROM users u
        LEFT JOIN users c ON u.created_by = c.id
        WHERE u.deleted_at IS NULL $where_extra
        ORDER BY u.created_at DESC";
$stmt = $pdo->query($sql);
$users = $stmt ? $stmt->fetchAll() : [];
?>
<div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-black tracking-tighter italic uppercase">Dashboard</h1>
        <div class="flex items-center gap-2">
            <a href="<?= url('/src/view/admin/landing/accountmanage.php') ?>"
                class="px-4 py-2 text-xs font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors">Register
                Account</a>
            <a href="<?= url('/src/view/auth/logout.php') ?>"
                class="px-4 py-2 text-xs font-bold text-white bg-red-500 hover:bg-red-600 rounded-full transition-colors">Logout</a>
        </div>
    </div>

    <div class="flex flex-wrap justify-center gap-2 mb-8">
        <div class="border border-black/10 rounded-[32px] flex-1 p-6 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Total Users</p>
            <p class="text-4xl font-black mt-2"><?= $total_users ?></p>
        </div>
        <div class="border border-black/10 rounded-[32px] flex-1 p-6 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Admins</p>
            <p class="text-4xl font-black mt-2"><?= $total_admins ?></p>
        </div>
        <div class="border border-black/10 rounded-[32px] flex-1 p-6 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Staff</p>
            <p class="text-4xl font-black mt-2"><?= $total_staff ?></p>
        </div>
        <div class="border border-black/10 rounded-[32px] flex-1 p-6 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Students</p>
            <p class="text-4xl font-black mt-2"><?= $total_students ?></p>
        </div>
        <div class="border border-black/10 rounded-[32px] flex-1 p-6 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Deleted</p>
            <p class="text-4xl font-black mt-2 text-red-500"><?= $total_deleted ?></p>
        </div>
    </div>

    <div class="border border-black/10 rounded-[32px] p-6 bg-white">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-black tracking-tighter italic uppercase">All Users</h2>
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-black/40">Created by:</span>
                <select onchange="window.location.href=this.value"
                    class="text-xs font-bold border border-black/10 rounded-lg px-3 py-1.5 bg-white text-black/60 focus:outline-none focus:border-google-blue">
                    <option value="<?= url('/src/view/admin/landing/dashboard.php') ?>" <?= $filter_role === '' ? 'selected' : '' ?>>All</option>
                    <option value="<?= url('/src/view/admin/landing/dashboard.php?filter=admin') ?>"
                        <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="<?= url('/src/view/admin/landing/dashboard.php?filter=staff') ?>"
                        <?= $filter_role === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="<?= url('/src/view/admin/landing/dashboard.php?filter=guest') ?>"
                        <?= $filter_role === 'guest' ? 'selected' : '' ?>>Guest</option>
                </select>
            </div>
        </div>
        <?php if (empty($users)): ?>
            <p class="text-sm font-medium text-black/40">No users found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs font-bold text-black/40 uppercase border-b border-black/10">
                            <th class="pb-3">ID</th>
                            <th class="pb-3">Name</th>
                            <th class="pb-3">Email</th>
                            <th class="pb-3">Role</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Created By</th>
                            <th class="pb-3">Joined</th>
                            <th class="pb-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr class="border-b border-black/5 text-sm font-medium">
                                <td class="py-3 text-black/40"><?= $u['id'] ?></td>
                                <td class="py-3"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                <td class="py-3 text-black/60"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="py-3"><span
                                        class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $u['role'] === 'admin' ? 'bg-google-blue/10 text-google-blue' : ($u['role'] === 'staff' ? 'bg-purple-100 text-purple-600' : 'bg-pixs-mint/10 text-pixs-mint') ?>"><?= $u['role'] ?></span>
                                </td>
                                <td class="py-3"><span
                                        class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $u['status'] === 'active' ? 'bg-green-100 text-green-600' : ($u['status'] === 'pending' ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600') ?>"><?= $u['status'] ?></span>
                                </td>
                                <td class="py-3 text-black/60">
                                    <?php if ($u['created_by']): ?>
                                        <?= htmlspecialchars($u['creator_first'] . ' ' . $u['creator_last']) ?>
                                        <span class="text-black/40 text-[10px]">(<?= $u['creator_role'] ?>)</span>
                                    <?php else: ?>
                                        <span class="text-black/30 italic">Self Register</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-black/40"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="<?= url('/src/view/admin/landing/accountmanage.php?id=' . $u['id']) ?>"
                                            class="p-2 text-google-blue hover:bg-google-blue/10 rounded-full transition-colors"
                                            title="Edit">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <button type="button"
                                            class="p-2 text-red-500 hover:bg-red-50 rounded-full transition-colors delete-btn"
                                            title="Delete" data-id="<?= $u['id'] ?>"
                                            data-name="<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>"
                                            data-email="<?= htmlspecialchars($u['email']) ?>" data-role="<?= $u['role'] ?>"
                                            data-status="<?= $u['status'] ?>"
                                            data-created="<?= date('M d, Y', strtotime($u['created_at'])) ?>"
                                            data-creator="<?= $u['created_by'] ? htmlspecialchars(($u['creator_first'] ?? '') . ' ' . ($u['creator_last'] ?? '')) : 'Self Register' ?>">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_deleted > 0):
        $sqlDel = "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.status, u.created_at, u.deleted_at,
                          c.first_name AS creator_first, c.last_name AS creator_last, c.role AS creator_role
                   FROM users u
                   LEFT JOIN users c ON u.created_by = c.id
                   WHERE u.deleted_at IS NOT NULL
                   ORDER BY u.deleted_at DESC";
        $stmtDel = $pdo->query($sqlDel);
        $deleted_users = $stmtDel ? $stmtDel->fetchAll() : [];
        ?>
        <div class="border border-black/10 rounded-[32px] p-6 bg-white mt-8">
            <h2 class="text-lg font-black tracking-tighter italic uppercase mb-4 text-red-500">Deleted Users</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs font-bold text-black/40 uppercase border-b border-black/10">
                            <th class="pb-3">ID</th>
                            <th class="pb-3">Name</th>
                            <th class="pb-3">Email</th>
                            <th class="pb-3">Role</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Created By</th>
                            <th class="pb-3">Joined</th>
                            <th class="pb-3">Deleted At</th>
                            <th class="pb-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deleted_users as $u): ?>
                            <tr class="border-b border-black/5 text-sm font-medium text-black/50">
                                <td class="py-3 text-black/40"><?= $u['id'] ?></td>
                                <td class="py-3"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                <td class="py-3 text-black/60"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="py-3"><span
                                        class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $u['role'] === 'admin' ? 'bg-google-blue/10 text-google-blue' : ($u['role'] === 'staff' ? 'bg-purple-100 text-purple-600' : 'bg-pixs-mint/10 text-pixs-mint') ?>"><?= $u['role'] ?></span>
                                </td>
                                <td class="py-3"><span
                                        class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-100 text-red-600"><?= $u['status'] ?></span>
                                </td>
                                <td class="py-3 text-black/60">
                                    <?php if ($u['created_by']): ?>
                                        <?= htmlspecialchars($u['creator_first'] . ' ' . $u['creator_last']) ?>
                                        <span class="text-black/40 text-[10px]">(<?= $u['creator_role'] ?>)</span>
                                    <?php else: ?>
                                        <span class="text-black/30 italic">Self Register</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-black/40"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td class="py-3 text-red-500 font-bold"><?= date('M d, Y h:i A', strtotime($u['deleted_at'])) ?>
                                </td>
                                <td class="py-3">
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="undo_id" value="<?= $u['id'] ?>">
                                        <button type="submit"
                                            class="p-2 text-green-500 hover:bg-green-50 rounded-full transition-colors"
                                            title="Restore">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<div id="deleteOverlay"
    class="fixed inset-0 z-40 bg-black/50 opacity-0 pointer-events-none transition-opacity duration-200"></div>

<div id="deleteModal"
    class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200">
    <div class="w-[90%] max-w-lg rounded-xl border border-gray-200 bg-white p-6 shadow-xl">
        <h2 class="text-xl font-bold">Delete Account</h2>
        <p class="mt-1 text-sm text-gray-500">This will soft-delete the account and set a <code>deleted_at</code>
            timestamp.</p>

        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm space-y-1.5">
            <div class="flex justify-between">
                <span class="text-gray-500">Name</span>
                <span class="font-medium" id="delName">—</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Email</span>
                <span class="font-medium" id="delEmail">—</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Role</span>
                <span class="font-medium" id="delRole">—</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Status</span>
                <span class="font-medium" id="delStatus">—</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Joined</span>
                <span class="font-medium" id="delJoined">—</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Created By</span>
                <span class="font-medium" id="delCreator">—</span>
            </div>
            <div class="border-t border-gray-200 pt-1.5 mt-1.5 flex justify-between">
                <span class="text-gray-500">Deleted At</span>
                <span class="font-medium text-red-600" id="delDeletedAt">—</span>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="delete_id" id="deleteIdInput" value="">
            <div class="mt-5 flex gap-3 pt-2! mt-2">
                <button type="submit"
                    class=" flex-1 rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white">Delete</button>
                <button type="button" id="deleteCancelBtn"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('deleteModal');
        const overlay = document.getElementById('deleteOverlay');
        const cancelBtn = document.getElementById('deleteCancelBtn');
        const idInput = document.getElementById('deleteIdInput');

        const delName = document.getElementById('delName');
        const delEmail = document.getElementById('delEmail');
        const delRole = document.getElementById('delRole');
        const delStatus = document.getElementById('delStatus');
        const delJoined = document.getElementById('delJoined');
        const delCreator = document.getElementById('delCreator');
        const delDeletedAt = document.getElementById('delDeletedAt');

        function formatTimestamp() {
            const now = new Date();
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const d = String(now.getDate()).padStart(2, '0');
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            return months[now.getMonth()] + ' ' + d + ', ' + now.getFullYear() + ' ' + h + ':' + m + ':' + s;
        }

        function showModal() {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.classList.add('opacity-100');
            overlay.classList.remove('opacity-0', 'pointer-events-none');
            overlay.classList.add('opacity-100');
        }

        function closeModal() {
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.classList.remove('opacity-100');
            overlay.classList.add('opacity-0', 'pointer-events-none');
            overlay.classList.remove('opacity-100');
        }

        document.querySelectorAll('.delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                idInput.value = this.getAttribute('data-id');
                delName.textContent = this.getAttribute('data-name');
                delEmail.textContent = this.getAttribute('data-email');
                delRole.textContent = this.getAttribute('data-role');
                delStatus.textContent = this.getAttribute('data-status');
                delJoined.textContent = this.getAttribute('data-created');
                delCreator.textContent = this.getAttribute('data-creator');
                delDeletedAt.textContent = formatTimestamp() + ' (now)';
                showModal();
            });
        });

        cancelBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);
    });
</script>

<?php require_once __DIR__ . '/../../../../footer.php'; ?>