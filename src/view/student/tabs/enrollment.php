<h2 class="text-lg font-black tracking-tighter mb-6">Enrollment Information</h2>
<div class="border border-black/10 rounded-2xl p-6 bg-white">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
        <div>
            <p class="text-xs text-black/40 mb-1">Preferred Course</p>
            <p class="font-semibold"><?= htmlspecialchars($course_name) ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Second Choice</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['second_course'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Academic Year</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['academic_year'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Semester</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['semester'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Application Status</p>
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold <?= $applicant ? ($applicant['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($applicant['status'] === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800')) : 'bg-gray-100 text-gray-800' ?>">
                <?= $applicant ? ucfirst($applicant['status']) : 'Not Applied' ?>
            </span>
        </div>
    </div>
</div>
