<?php
require_once __DIR__ . '/../../../header.php';
require_admin();
?>
<nav class="bg-white border-b border-black/10 px-4 md:px-8 py-4">
    <div class="max-w-[1040px] mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="<?= url('/public/images/ncst.png') ?>" alt="NCST" class="w-8 h-8 rounded-lg object-cover">
            <span class="text-sm font-black tracking-tighter italic uppercase">Admin Panel</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-xs font-medium text-black/60"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
            <a href="<?= url('/src/view/auth/logout.php') ?>" class="text-xs font-bold text-google-blue hover:underline">Logout</a>
        </div>
    </div>
</nav>