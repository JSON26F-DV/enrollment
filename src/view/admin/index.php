<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/sidebar.php';

// Get counts
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM applicants");
    $total_applicants = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM applicants WHERE status = 'pending'");
    $pending_applicants = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $total_students = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'staff')");
    $total_staff = $stmt->fetchColumn();

    // Get latest 10 accounts
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
    $latest_accounts = $stmt->fetchAll();
} catch (Exception $e) {
    $total_applicants = $pending_applicants = $total_students = $total_staff = 0;
    $latest_accounts = [];
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-500 mt-1">Welcome back! Here's an overview of your system.</p>
</div>

<!-- Stats Cards - One Line Flex -->
<div class="flex gap-4 mb-8">
    <div class="flex-1 bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Applicants</p>
        <p class="text-2xl font-bold text-gray-900 mt-1"><?= $total_applicants ?></p>
    </div>
    <div class="flex-1 bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pending Review</p>
        <p class="text-2xl font-bold text-amber-600 mt-1"><?= $pending_applicants ?></p>
    </div>
    <div class="flex-1 bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Students</p>
        <p class="text-2xl font-bold text-green-600 mt-1"><?= $total_students ?></p>
    </div>
    <div class="flex-1 bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Staff & Admin</p>
        <p class="text-2xl font-bold text-purple-600 mt-1"><?= $total_staff ?></p>
    </div>
</div>

<!-- Latest Accounts -->
<div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-900">Latest Accounts</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                    <th class="pb-3 text-left">Name</th>
                    <th class="pb-3 text-left">Email</th>
                    <th class="pb-3 text-left">Role</th>
                    <th class="pb-3 text-left">Status</th>
                    <th class="pb-3 text-left">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($latest_accounts)): ?>
                    <tr>
                        <td colspan="5" class="py-4 text-center text-sm text-gray-500">No accounts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($latest_accounts as $acc): ?>
                        <tr class="text-sm">
                            <td class="py-3 text-gray-900"><?= htmlspecialchars($acc['first_name'] . ' ' . $acc['last_name']) ?>
                            </td>
                            <td class="py-3 text-gray-500"><?= htmlspecialchars($acc['email']) ?></td>
                            <td class="py-3">
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-medium <?= $acc['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>">
                                    <?= ucfirst($acc['role']) ?>
                                </span>
                            </td>
                            <td class="py-3">
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-medium <?= $acc['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                    <?= ucfirst($acc['status']) ?>
                                </span>
                            </td>
                            <td class="py-3 text-gray-500"><?= date('M d, Y', strtotime($acc['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</div>
</body>

</html>