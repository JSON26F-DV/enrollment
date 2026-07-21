<?php
$student_id = $student['id'] ?? 0;
$terms = ['Prelim', 'Midterm', 'Prefinal', 'Finals'];

$stmt = $pdo->prepare("SELECT total_tuition FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$total_tuition = (float)($stmt->fetch()['total_tuition'] ?? 0);

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) AS total_paid FROM payments WHERE student_id = ?");
$stmt->execute([$student_id]);
$total_paid = (float)$stmt->fetch()['total_paid'];
$outstanding = max(0, $total_tuition - $total_paid);

$term_totals = [];
foreach ($terms as $t) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) AS paid FROM payments WHERE student_id = ? AND term = ?");
    $stmt->execute([$student_id, $t]);
    $term_totals[$t] = (float)$stmt->fetch()['paid'];
}

$stmt = $pdo->prepare("SELECT id, term, amount_paid, payment_date FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
$stmt->execute([$student_id]);
$payments = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid session token.';
        $type = 'error';
    } else {
        $term = $_POST['term'] ?? '';
        $amount = str_replace(',', '', $_POST['amount'] ?? '');
        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);

        if (!in_array($term, $terms)) {
            $msg = 'Invalid term selected.';
            $type = 'error';
        } elseif ($amount === false || $amount <= 0) {
            $msg = 'Amount must be greater than zero.';
            $type = 'error';
        } elseif ($amount > $outstanding) {
            $msg = 'Amount exceeds your outstanding balance of ' . number_format($outstanding, 2) . '.';
            $type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO payments (student_id, term, amount_paid, payment_date) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$student_id, $term, $amount]);
                $msg = 'Payment of ' . number_format($amount, 2) . ' for ' . $term . ' recorded successfully.';
                $type = 'success';
            } catch (PDOException $e) {
                $msg = 'Payment failed. Please try again.';
                $type = 'error';
            }
        }
    }
    header('Location: ' . url('/src/view/student/dashboard.php?tab=payments&msg=' . urlencode($msg) . '&type=' . urlencode($type)));
    exit;
}
?>
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-black tracking-tighter uppercase">Payments</h2>
        <p class="text-xs text-black/40">Manage your tuition payments</p>
    </div>

    <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[160px] border border-black/10 rounded-2xl p-4 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Total Tuition</p>
            <p class="text-2xl font-black mt-1">₱<?= number_format($total_tuition, 2) ?></p>
        </div>
        <div class="flex-1 min-w-[160px] border border-black/10 rounded-2xl p-4 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Total Paid</p>
            <p class="text-2xl font-black mt-1 text-green-600">₱<?= number_format($total_paid, 2) ?></p>
        </div>
        <div class="flex-1 min-w-[160px] border border-black/10 rounded-2xl p-4 bg-white">
            <p class="text-xs font-medium text-black/40 uppercase tracking-wider">Outstanding</p>
            <p class="text-2xl font-black mt-1 <?= $outstanding > 0 ? 'text-red-500' : 'text-green-600' ?>">₱<?= number_format($outstanding, 2) ?></p>
        </div>
    </div>

    <?php if ($outstanding > 0): ?>
    <div class="border border-black/10 rounded-2xl p-6 bg-white">
        <h3 class="text-sm font-black tracking-tighter uppercase mb-4">Make a Payment</h3>
        <form method="POST" action="" onsubmit="return validatePayment()">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="make_payment" value="1">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="text-xs font-bold text-black/40 uppercase tracking-wider block mb-1">Term</label>
                    <select name="term" id="payTerm" required
                        class="w-full border border-black/10 rounded-xl px-4 py-2.5 text-sm font-medium focus:outline-none focus:border-google-blue bg-white">
                        <option value="">Select term</option>
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-black/40 uppercase tracking-wider block mb-1">Amount (₱)</label>
                    <input type="number" name="amount" id="payAmount" step="0.01" min="0.01" max="<?= $outstanding ?>" required
                        class="w-full border border-black/10 rounded-xl px-4 py-2.5 text-sm font-medium focus:outline-none focus:border-google-blue"
                        placeholder="0.00">
                </div>
                <div class="flex items-end">
                    <button type="submit"
                        class="w-full px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-xl transition-colors">
                        Pay Now
                    </button>
                </div>
            </div>
            <p id="payError" class="text-xs font-medium text-red-500 hidden"></p>
        </form>
    </div>
    <?php endif; ?>

    <div class="border border-black/10 rounded-2xl p-6 bg-white">
        <h3 class="text-sm font-black tracking-tighter uppercase mb-4">Term Breakdown</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-bold text-black/40 uppercase border-b border-black/10">
                        <th class="pb-3">Term</th>
                        <th class="pb-3">Amount Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($terms as $t): ?>
                    <tr class="border-b border-black/5 text-sm font-medium">
                        <td class="py-3"><?= $t ?></td>
                        <td class="py-3">₱<?= number_format($term_totals[$t], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="border border-black/10 rounded-2xl p-6 bg-white">
        <h3 class="text-sm font-black tracking-tighter uppercase mb-4">Payment History</h3>
        <?php if (empty($payments)): ?>
            <p class="text-sm font-medium text-black/40">No payments recorded yet.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-bold text-black/40 uppercase border-b border-black/10">
                        <th class="pb-3">Date</th>
                        <th class="pb-3">Term</th>
                        <th class="pb-3">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr class="border-b border-black/5 text-sm font-medium">
                        <td class="py-3 text-black/60"><?= date('M d, Y h:i A', strtotime($p['payment_date'])) ?></td>
                        <td class="py-3"><?= htmlspecialchars($p['term']) ?></td>
                        <td class="py-3">₱<?= number_format($p['amount_paid'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function validatePayment() {
    const amount = parseFloat(document.getElementById('payAmount').value);
    const max = <?= $outstanding ?>;
    const err = document.getElementById('payError');
    if (!amount || amount <= 0) {
        err.textContent = 'Amount must be greater than zero.';
        err.classList.remove('hidden');
        return false;
    }
    if (amount > max) {
        err.textContent = 'Amount exceeds outstanding balance of ₱' + max.toFixed(2) + '.';
        err.classList.remove('hidden');
        return false;
    }
    err.classList.add('hidden');
    return true;
}
</script>
