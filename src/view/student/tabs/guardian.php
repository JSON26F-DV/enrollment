<h2 class="text-lg font-black tracking-tighter mb-6">Guardian Information</h2>
<div class="border border-black/10 rounded-2xl p-6 bg-white">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
        <div>
            <p class="text-xs text-black/40 mb-1">Father's Name</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['father_name'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Mother's Name</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['mother_name'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Guardian's Name</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['guardian_name'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Guardian's Contact</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['guardian_contact'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Relationship</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['guardian_relationship'] ?? 'N/A') ?></p>
        </div>
    </div>
</div>
