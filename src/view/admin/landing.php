<?php
require_once __DIR__ . '/../../../header.php';
require_admin();
?>
<div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12">
    <h1 class="text-4xl font-black tracking-tighter italic uppercase mb-4">Admin Landing</h1>
    <p class="text-sm font-medium text-black/60 mb-8">Welcome to the NCST Violation Management System admin panel.</p>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="border border-black/10 rounded-[32px] p-6 bg-white">
            <h3 class="text-sm font-black tracking-tighter uppercase mb-2">Quick Links</h3>
            <ul class="space-y-2">
                <li><a href="landing/dashboard.php"
                        class="text-xs font-medium text-google-blue hover:underline">Dashboard</a></li>
                <li><a href="<?= url('/src/view/auth/logout.php') ?>"
                        class="text-xs font-medium text-red-500 hover:underline">Logout</a></li>
            </ul>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../../footer.php'; ?>