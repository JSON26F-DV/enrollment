<?php
require_once __DIR__ . '/../../../header.php';
require_student();

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$name = $user ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : 'Student';
?>
<div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12">
<h1 class="text-4xl font-black tracking-tighter italic uppercase mb-2">Hello student <?= $name ?></h1>
<p class="text-sm font-medium text-black/60 mb-8">Welcome to your NCST Violation Management dashboard.</p>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
<div class="border border-black/10 rounded-[32px] p-6 bg-white">
<h3 class="text-sm font-black tracking-tighter uppercase mb-2">My Profile</h3>
<p class="text-xs font-medium text-black/60">View and manage your personal information.</p>
</div>
<div class="border border-black/10 rounded-[32px] p-6 bg-white">
<h3 class="text-sm font-black tracking-tighter uppercase mb-2">My Records</h3>
<p class="text-xs font-medium text-black/60">Check your violation records and academic standing.</p>
</div>
<div class="border border-black/10 rounded-[32px] p-6 bg-white">
<h3 class="text-sm font-black tracking-tighter uppercase mb-2">Support</h3>
<p class="text-xs font-medium text-black/60">Contact support or view frequently asked questions.</p>
</div>
</div>

<div class="mt-8">
<a href="<?= url('/src/view/auth/logout.php') ?>" class="px-4 py-2 text-xs font-bold text-white bg-red-500 hover:bg-red-600 rounded-full transition-colors">Logout</a>
</div>
</div>
<?php require_once __DIR__ . '/../../../footer.php'; ?>
