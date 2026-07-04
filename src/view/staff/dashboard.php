<?php
require_once __DIR__ . '/../../../header.php';
require_staff();

$total_students = 0;
$stmt = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role = 'student' AND deleted_at IS NULL");
if ($stmt)
    $total_students = $stmt->fetch()['c'];

$total_pending = 0;
$stmt = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role = 'student' AND status = 'pending' AND deleted_at IS NULL");
if ($stmt)
    $total_pending = $stmt->fetch()['c'];

$total_active = 0;
$stmt = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role = 'student' AND status = 'active' AND deleted_at IS NULL");
if ($stmt)
    $total_active = $stmt->fetch()['c'];
?>
<div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-black tracking-tighter italic uppercase">Staff Dashboard</h1>
        <div class="flex items-center gap-2">
            <a href="<?= url('/src/view/staff/accountmanage.php') ?>"
                class="px-4 py-2 text-xs font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors">Register
                Student</a>
            <a href="<?= url('/src/view/auth/logout.php') ?>"
                class="px-4 py-2 text-xs font-bold text-white bg-red-500 hover:bg-red-600 rounded-full transition-colors">Logout</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="border border-black/10 rounded-[32px] p-6 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Total Students</p>
            <p class="text-4xl font-black mt-2"><?= $total_students ?></p>
        </div>
        <div class="border border-black/10 rounded-[32px] p-6 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Pending</p>
            <p class="text-4xl font-black mt-2"><?= $total_pending ?></p>
        </div>
        <div class="border border-black/10 rounded-[32px] p-6 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Active</p>
            <p class="text-4xl font-black mt-2"><?= $total_active ?></p>
        </div>
    </div>

    <div class="border border-black/10 rounded-[32px] p-6 bg-white">
        <h2 class="text-lg font-black tracking-tighter italic uppercase mb-4">Students</h2>
        <?php
        $stmt = $pdo->query("SELECT id, first_name, last_name, email, status, created_at FROM users WHERE role = 'student' AND deleted_at IS NULL ORDER BY created_at DESC");
        $students = $stmt ? $stmt->fetchAll() : [];
        ?>
        <?php if (empty($students)): ?>
            <p class="text-sm font-medium text-black/40">No students found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs font-bold text-black/40 uppercase border-b border-black/10">
                            <th class="pb-3">ID</th>
                            <th class="pb-3">Name</th>
                            <th class="pb-3">Email</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr class="border-b border-black/5 text-sm font-medium">
                                <td class="py-3 text-black/40"><?= $s['id'] ?></td>
                                <td class="py-3"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                <td class="py-3 text-black/60"><?= htmlspecialchars($s['email']) ?></td>
                                <td class="py-3"><span
                                        class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $s['status'] === 'active' ? 'bg-green-100 text-green-600' : ($s['status'] === 'pending' ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600') ?>"><?= $s['status'] ?></span>
                                </td>
                                <td class="py-3 text-black/40"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../../footer.php'; ?>