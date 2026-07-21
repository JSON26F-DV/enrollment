<?php
require_once __DIR__ . '/sidebar.php';

$stmt = $pdo->query("SELECT COALESCE(SUM(amount_paid), 0) AS total FROM payments");
$total_revenue = $stmt ? (float)$stmt->fetch()['total'] : 0;

$stmt = $pdo->query("SELECT COALESCE(SUM(total_tuition), 0) AS total FROM students");
$total_tuition = $stmt ? (float)$stmt->fetch()['total'] : 0;

$total_outstanding = max(0, $total_tuition - $total_revenue);

$terms = ['Prelim', 'Midterm', 'Prefinal', 'Finals'];
$term_earnings = [];
foreach ($terms as $t) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) AS total FROM payments WHERE term = ?");
    $stmt->execute([$t]);
    $term_earnings[$t] = (float)$stmt->fetch()['total'];
}

$stmt = $pdo->query("
    SELECT p.id, p.term, p.amount_paid, p.payment_date,
           u.first_name, u.last_name, u.email,
           s.id AS student_id
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN users u ON s.user_id = u.id
    ORDER BY p.payment_date DESC
    LIMIT 100
");
$transactions = $stmt ? $stmt->fetchAll() : [];
?>
<div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-black tracking-tighter  uppercase">Financials</h1>
    </div>

    <div class="flex flex-wrap gap-4 mb-8">
        <div class="flex-1 min-w-[200px] border border-black/10 rounded-[32px] p-6 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Total Revenue Collected</p>
            <p class="text-4xl font-black mt-2 text-green-600">₱<?= number_format($total_revenue, 2) ?></p>
        </div>
        <div class="flex-1 min-w-[200px] border border-black/10 rounded-[32px] p-6 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Total Outstanding</p>
            <p class="text-4xl font-black mt-2 text-red-500">₱<?= number_format($total_outstanding, 2) ?></p>
        </div>
    </div>

    <div class="border border-black/10 rounded-[32px] p-6 bg-white mb-8">
        <h2 class="text-lg font-black tracking-tighter  uppercase mb-4">Earnings by Term</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-bold text-black/40 uppercase border-b border-black/10">
                        <th class="pb-3">Term</th>
                        <th class="pb-3">Total Collected</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($terms as $t): ?>
                    <tr class="border-b border-black/5 text-sm font-medium">
                        <td class="py-3"><?= $t ?></td>
                        <td class="py-3">₱<?= number_format($term_earnings[$t], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="border border-black/10 rounded-[32px] p-6 bg-white">
        <h2 class="text-lg font-black tracking-tighter  uppercase mb-4">Transaction Log</h2>
        <?php if (empty($transactions)): ?>
            <p class="text-sm font-medium text-black/40">No payments recorded yet.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-bold text-black/40 uppercase border-b border-black/10">
                        <th class="pb-3">Student Name</th>
                        <th class="pb-3">Student ID</th>
                        <th class="pb-3">Term</th>
                        <th class="pb-3">Amount</th>
                        <th class="pb-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                    <tr class="border-b border-black/5 text-sm font-medium">
                        <td class="py-3"><?= htmlspecialchars($tx['first_name'] . ' ' . $tx['last_name']) ?></td>
                        <td class="py-3 text-black/40"><?= $tx['student_id'] ?></td>
                        <td class="py-3"><?= htmlspecialchars($tx['term']) ?></td>
                        <td class="py-3">₱<?= number_format($tx['amount_paid'], 2) ?></td>
                        <td class="py-3 text-black/60"><?= date('M d, Y h:i A', strtotime($tx['payment_date'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

</main>
</div>
</body>
</html>
