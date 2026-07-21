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
        <?php
        // Get current section info
        $currentSection = null;
        $allSections = [];
        
        // Check if sections table exists first
        $tableExists = false;
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'sections'");
            $tableExists = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $tableExists = false;
        }
        
        if ($tableExists) {
            $stmt = $pdo->prepare("SELECT * FROM sections ORDER BY section_name");
            $stmt->execute();
            $allSections = $stmt->fetchAll();
            
            if (!empty($student['section_id'])) {
                $stmt = $pdo->prepare("SELECT * FROM sections WHERE id = ?");
                $stmt->execute([$student['section_id']]);
                $currentSection = $stmt->fetch();
            }
        }
        ?>
        <div>
            <p class="text-xs text-black/40 mb-1">Assigned Section</p>
            <p class="font-semibold <?= $currentSection ? 'text-blue-700' : 'text-gray-500' ?>">
                <?= $currentSection ? htmlspecialchars($currentSection['section_name']) : 'Not assigned' ?>
            </p>
            <?php if ($currentSection): ?>
                <p class="text-xs text-black/50 mt-1">
                    Schedule: <?= htmlspecialchars(date('g:i A', strtotime($currentSection['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($currentSection['end_time']))) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Section Selection Form -->
<?php if (!empty($allSections)): ?>
<div class="mt-6 border border-black/10 rounded-2xl p-6 bg-white">
    <h3 class="text-base font-bold text-gray-900 mb-4">Update Section Assignment</h3>
    <form method="POST" action="" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="update_section" value="1">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Section</label>
                <select name="section_id" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <option value="">-- Select a Section --</option>
                    <?php foreach ($allSections as $sec): ?>
                        <option value="<?= (int)$sec['id'] ?>" <?= ($student['section_id'] == $sec['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sec['section_name']) ?> (<?= htmlspecialchars(date('g:i A', strtotime($sec['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($sec['end_time']))) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" onclick="return confirm('Are you sure you want to change your section assignment?')"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                    Update Section
                </button>
            </div>
        </div>
        
        <!-- Section Schedule Reference -->
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Section Schedule Reference</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                <?php
                $timeGroups = [
                    'Morning (M1-M4)' => ['M1', 'M2', 'M3', 'M4'],
                    'Afternoon (A1-A4)' => ['A1', 'A2', 'A3', 'A4'],
                    'Evening (E1-E4)' => ['E1', 'E2', 'E3', 'E4'],
                ];
                foreach ($timeGroups as $groupName => $codes):
                    $groupSections = array_filter($allSections, function($s) use ($codes) {
                        return in_array($s['section_name'], $codes);
                    });
                    if (!empty($groupSections)):
                        $first = reset($groupSections);
                ?>
                    <div class="bg-white p-2 rounded border border-gray-200">
                        <p class="font-semibold text-gray-700"><?= htmlspecialchars($groupName) ?></p>
                        <p class="text-gray-500">
                            <?= htmlspecialchars(date('g:i A', strtotime($first['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($first['end_time']))) ?>
                        </p>
                    </div>
                <?php 
                    endif;
                endforeach;
                ?>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>
