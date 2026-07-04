<?php
$page_title = 'Students';
require_once __DIR__ . '/sidebar.php';

$filter = $_GET['filter'] ?? '';

$where = "WHERE u.role = 'student'";
if ($filter === 'active') $where .= " AND u.status = 'active'";
elseif ($filter === 'inactive') $where .= " AND u.status = 'inactive'";

try {
    $stmt = $pdo->query("
        SELECT u.*, s.preferred_course, s.academic_year, s.enrollment_status 
        FROM users u 
        LEFT JOIN students s ON s.user_id = u.id 
        $where 
        ORDER BY u.created_at DESC
    ");
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    $students = [];
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Students</h1>
    <p class="text-gray-500 mt-1">View all enrolled students</p>
</div>

<div class="flex gap-2 mb-6">
    <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium <?= !$filter ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">All</a>
    <a href="?filter=active" class="px-4 py-2 rounded-lg text-sm font-medium <?= $filter === 'active' ? 'bg-green-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">Active</a>
    <a href="?filter=inactive" class="px-4 py-2 rounded-lg text-sm font-medium <?= $filter === 'inactive' ? 'bg-red-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">Inactive</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($students)): ?>
        <div class="p-8 text-center text-gray-500">
            <p>No students found.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($students as $s): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($s['email']) ?></div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                <?= htmlspecialchars($s['contact_number']) ?>
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-medium"><?= htmlspecialchars($s['preferred_course'] ?? '-') ?></span>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($s['academic_year'] ?? '') ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $s['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= ucfirst($s['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($s['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>