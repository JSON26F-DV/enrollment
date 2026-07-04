<?php require_once __DIR__ . '/../../../header.php'; ?>
<div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12">
    <h1 class="text-5xl font-black tracking-tighter italic uppercase mb-6">About Us</h1>
    <div class="max-w-2xl">
        <p class="text-sm font-medium text-black/60 leading-relaxed mb-4">
            The NCST Violation Management System is a platform designed to streamline the tracking, reporting, and
            management of academic and disciplinary violations at the National College of Science and Technology.
        </p>
        <p class="text-sm font-medium text-black/60 leading-relaxed mb-4">
            Our system provides a centralized database for recording violations, managing user accounts, and generating
            reports to support informed decision-making by administrators and faculty.
        </p>
        <div class="border border-black/10 rounded-[32px] p-6 bg-white mt-8">
            <h2 class="text-lg font-black tracking-tighter italic uppercase mb-3">Our Mission</h2>
            <p class="text-sm font-medium text-black/60 leading-relaxed">To promote academic integrity and foster a
                safe, accountable campus environment through efficient digital management of disciplinary records.</p>
        </div>
        <div class="flex items-center gap-4 mt-8">
            <a href="<?= url('/src/view/auth/login/loginpage.php') ?>"
                class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95">Sign
                In</a>
            <a href="<?= url('/src/view/auth/register/register.php') ?>"
                class="px-6 py-2 text-sm font-bold text-google-blue hover:bg-google-blue/10 rounded-full transition-colors">Create
                Account</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../../footer.php'; ?>