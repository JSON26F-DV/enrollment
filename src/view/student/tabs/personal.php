<h2 class="text-lg font-black tracking-tighter mb-6">Personal Information</h2>
<div class="border border-black/10 rounded-2xl p-6 bg-white">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
        <div>
            <p class="text-xs text-black/40 mb-1">Full Name</p>
            <p class="font-semibold"><?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Suffix</p>
            <p class="font-semibold"><?= htmlspecialchars($user['suffix'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Birthday</p>
            <p class="font-semibold"><?= $applicant ? date('F d, Y', strtotime($applicant['birthday'])) : 'N/A' ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Gender</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['gender'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Civil Status</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['civil_status'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Nationality</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['nationality'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Religion</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['religion'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p class="text-xs text-black/40 mb-1">Birth Place</p>
            <p class="font-semibold"><?= htmlspecialchars($applicant['birth_place'] ?? 'N/A') ?></p>
        </div>
    </div>
</div>
