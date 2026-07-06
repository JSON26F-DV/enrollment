<h2 class="text-lg font-black tracking-tighter mb-6">Educational Background</h2>
<div class="border border-black/10 rounded-2xl p-6 bg-white">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
        <div>
            <p class="text-xs text-black/40 mb-1">Education Type</p>
            <p class="font-semibold"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($applicant['education_type'] ?? 'N/A'))) ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">High School Name</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['highschool_name'] ?? 'N/A') ?></p>
        </div>
        <div class="md:col-span-2">
            <p class="text-xs text-black/40 mb-1">School Address</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['highschool_address'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">SHS Track</p>
            <p class="font-semibold"><?= htmlspecialchars($shs_track ?: 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">SHS Strand</p>
            <p class="font-semibold"><?= htmlspecialchars($shs_strand ?: 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Year Graduated</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['year_graduated'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">LRN</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['lrn'] ?? 'N/A') ?></p>
        </div>
    </div>
</div>
<?php if ($is_transferee): ?>
<h3 class="text-sm font-bold mt-6 mb-3">Transferee Information</h3>
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
<?php endif; ?>
