<h2 class="text-lg font-black tracking-tighter mb-6">Transferee Information</h2>
<div class="border border-black/10 rounded-2xl p-6 bg-white">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-sm">
        <div>
            <p class="text-xs text-black/40 mb-1">Previous College</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['previous_college'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Previous Course</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['previous_course'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Last Year Level</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['last_year_level'] ?? 'N/A') ?></p>
        </div>
    </div>
</div>
