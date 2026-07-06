<h1 class="text-3xl font-black tracking-tighter mb-1">Hello, <?= $name ?></h1>
<p class="text-sm text-black/60 mb-6">Welcome to your NCST Enrollment dashboard.</p>

<div class="flex flex-wrap items-center gap-3 mb-8">
    <?php if ($applicant && $applicant['status'] === 'approved'): ?>
        <span class="px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-800">Enrollment Approved</span>
    <?php elseif ($applicant && $applicant['status'] === 'pending'): ?>
        <span class="px-4 py-2 rounded-full text-sm font-bold bg-amber-100 text-amber-800">Awaiting Review</span>
    <?php elseif ($applicant && $applicant['status'] === 'rejected'): ?>
        <span class="px-4 py-2 rounded-full text-sm font-bold bg-red-100 text-red-800">Application Rejected</span>
    <?php else: ?>
        <span class="px-4 py-2 rounded-full text-sm font-bold bg-gray-100 text-gray-800">Not Applied</span>
    <?php endif; ?>
</div>

<!-- Status Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-2 m-5">
    <div class="border border-black/10 rounded-2xl p-5 bg-white">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-google-blue/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-google-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="text-sm font-bold">Enrollment Information</h3>
        </div>
        <div class="space-y-2.5 text-sm">
            <div class="flex justify-between">
                <span class="text-black/50">Course</span>
                <span class="font-semibold"><?= htmlspecialchars($course_name) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-black/50">Status</span>
                <span class="font-semibold"><?= $applicant ? ucfirst($applicant['status']) : 'Not Applied' ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-black/50">Academic Year</span>
                <span class="font-semibold"><?= htmlspecialchars($applicant['academic_year'] ?? 'N/A') ?></span>
            </div>
        </div>
    </div>
    <div class="border border-black/10 rounded-2xl p-5 bg-white">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-google-blue/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-google-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
            </div>
            <h3 class="text-sm font-bold">Senior High School Background</h3>
        </div>
        <div class="space-y-2.5 text-sm">
            <div class="flex justify-between">
                <span class="text-black/50">Education Type</span>
                <span
                    class="font-semibold"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($applicant['education_type'] ?? 'N/A'))) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-black/50">Track</span>
                <span class="font-semibold"><?= htmlspecialchars($shs_track ?: 'N/A') ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-black/50">Strand</span>
                <span class="font-semibold"><?= htmlspecialchars($shs_strand ?: 'N/A') ?></span>
            </div>
        </div>
    </div>
</div>

<?php if ($applicant): ?>
    <div class="border border-black/10 rounded-2xl p-5 bg-white mt-5 ">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold">Document Accountability</h3>
                <p class="text-xs text-black/40">Track your submitted documents</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-black/10">
                        <th class="text-left py-2.5 px-2 text-xs font-medium text-black/40 uppercase">Document</th>
                        <th class="text-left py-2.5 px-2 text-xs font-medium text-black/40 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($required_docs, 0, 4) as $key => $label): ?>
                        <?php
                        $doc = $uploaded_docs[$key] ?? null;
                        $status = $doc ? ($doc['status'] ?? 'submitted') : 'not_submitted';
                        ?>
                        <tr class="border-b border-black/5">
                            <td class="py-2.5 px-2 font-medium"><?= htmlspecialchars($label) ?></td>
                            <td class="py-2.5 px-2">
                                <?php if ($status === 'approved'): ?>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800">Approved</span>
                                <?php elseif ($status === 'submitted' || $status === 'pending'): ?>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Pending</span>
                                <?php elseif ($status === 'rejected'): ?>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">Rejected</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800">Not
                                        Submitted</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button onclick="switchTab('documents')" class="mt-3 text-xs font-bold text-google-blue hover:underline">
            View all documents →
        </button>
    </div>
<?php endif; ?>

<?php if (!$applicant): ?>
    <div class="p-5 bg-amber-50 rounded-2xl border border-amber-200">
        <p class="text-sm text-amber-800">
            <strong>Note:</strong> You haven't submitted an enrollment application yet.
            <a href="<?= url('/src/view/auth/register/register.php') ?>" class="underline font-bold">Apply now</a>
        </p>
    </div>
<?php endif; ?>