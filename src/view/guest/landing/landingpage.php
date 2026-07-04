<?php require_once __DIR__ . '/../../../../header.php'; ?>
<div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12">
    <div class="flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="flex-1">
            <h1 class="text-5xl md:text-7xl font-black tracking-tighter uppercase leading-tight">
                Welcome to<br>NCST
            </h1>
            <p class="text-lg font-medium text-black/60 mt-4 max-w-md">
                NCST Violation Management System — tracking academic integrity and campus discipline.
            </p>
            <div class="flex items-center gap-4 mt-8">
                <?php if (check_logged_in()): ?>
                    <a href="<?= url('/src/view/auth/logout.php') ?>"
                        class="px-6 py-2.5 text-sm font-bold text-white bg-red-500 hover:bg-red-600 rounded-full transition-colors shadow-sm">Logout</a>
                    <?php if (check_admin()): ?>
                        <a href="<?= url('/src/view/admin/landing/dashboard.php') ?>"
                            class="px-6 py-2 text-sm font-bold text-google-blue hover:bg-google-blue/10 rounded-full transition-colors">Dashboard</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= url('/src/view/auth/login/loginpage.php') ?>"
                        class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95">Sign
                        In</a>
                    <a href="<?= url('/src/view/auth/register/register.php') ?>"
                        class="px-6 py-2 text-sm font-bold text-google-blue hover:bg-google-blue/10 rounded-full transition-colors">Create
                        Account</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex-shrink-0">
            <img src="<?= url('/public/images/ncst.png') ?>" alt="NCST"
                class="w-48 h-48 md:w-64 md:h-64 rounded-[40px] object-cover shadow-2xl shadow-pixs-mint/20">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-16">
        <div class="border border-black/10 rounded-[32px] p-6 bg-white">
            <h3 class="text-sm font-black tracking-tighter uppercase mb-2">For Students</h3>
            <p class="text-xs font-medium text-black/60">View your records, track violations, and stay informed about
                your academic standing.</p>
        </div>
        <div class="border border-black/10 rounded-[32px] p-6 bg-white">
            <h3 class="text-sm font-black tracking-tighter uppercase mb-2">For Administrators</h3>
            <p class="text-xs font-medium text-black/60">Manage violations, generate reports, and oversee the
                disciplinary system efficiently.</p>
        </div>
        <div class="border border-black/10 rounded-[32px] p-6 bg-white">
            <h3 class="text-sm font-black tracking-tighter uppercase mb-2">Secure &amp; Reliable</h3>
            <p class="text-xs font-medium text-black/60">Enterprise-grade security with encrypted data and role-based
                access control.</p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../../../footer.php'; ?>