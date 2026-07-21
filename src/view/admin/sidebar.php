<?php
require_once __DIR__ . '/../../../header.php';
require_admin();

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Panel' ?> - NCST</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>

<body class="bg-gray-50 min-h-screen">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
            <!-- Logo -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <img src="<?= url('/public/images/ncst.png') ?>" alt="NCST"
                        class="w-10 h-10 rounded-lg object-cover">
                    <div>
                        <h1 class="font-black text-sm tracking-tight">NCST</h1>
                        <p class="text-xs text-gray-500">Admin Panel</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1">
                <a href="<?= url('/src/view/admin/') ?>"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= ($current_page === 'index' || $current_page === 'landing') ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>

                <a href="<?= url('/src/view/admin/applicants.php') ?>"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= $current_page === 'applicants' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-sm font-medium">Applicants</span>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM applicants WHERE status = 'pending'");
                        $pending_count = $stmt->fetchColumn();
                        if ($pending_count > 0): ?>
                            <span
                                class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $pending_count ?></span>
                        <?php endif;
                    } catch (Exception $e) {
                    }
                    ?>
                </a>

                <a href="<?= url('/src/view/admin/accounts.php') ?>"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= $current_page === 'accounts' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span class="text-sm font-medium">Accounts</span>
                </a>

                <a href="<?= url('/src/view/admin/students.php') ?>"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= $current_page === 'students' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    </svg>
                    <span class="text-sm font-medium">Students</span>
                </a>

                <a href="<?= url('/src/view/admin/financials.php') ?>"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= $current_page === 'financials' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium">Financials</span>
                </a>

                <div class="pt-4 mt-4 border-t border-gray-200">
                    <p class="px-4 text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">Settings</p>
                </div>

                <a href="<?= url('/src/view/admin/documentpaths.php') ?>"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= $current_page === 'documentpaths' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    <span class="text-sm font-medium">Document Paths</span>
                </a>
            </nav>

            <!-- User Info & Logout -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center gap-3 mb-3">
                    <div
                        class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-sm font-bold text-gray-700">
                        <?= strtoupper(substr($_SESSION['email'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            <?= htmlspecialchars($_SESSION['email'] ?? 'Admin') ?>
                        </p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                </div>
                <a href="<?= url('/src/view/auth/logout.php') ?>"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden">
            <div class="p-8">