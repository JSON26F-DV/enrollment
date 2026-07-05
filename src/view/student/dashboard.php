<?php
require_once __DIR__ . '/../../../header.php';
require_student();

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get student enrollment info
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

// Get applicant's college and course info
$stmt = $pdo->prepare("SELECT * FROM applicants WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$applicant = $stmt->fetch();

// Get course name
$course_name = 'Not enrolled';
if ($applicant && $applicant['preferred_course']) {
    $stmt = $pdo->prepare("SELECT name FROM courses WHERE code = ?");
    $stmt->execute([$applicant['preferred_course']]);
    $course = $stmt->fetch();
    if ($course) $course_name = $course['name'];
}

// Get all required documents
$required_docs = [
    'psa_birth_certificate' => 'PSA Birth Certificate',
    'form_138' => 'Form 138 / Report Card',
    'good_moral' => 'Good Moral Certificate',
    'certificate_of_graduation' => 'Certificate of Graduation',
    'id_photo_2x2' => 'ID Photo (2x2)',
    'valid_id' => 'Valid ID',
];

// Get uploaded documents
$uploaded_docs = [];
if ($applicant) {
    $stmt = $pdo->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ?");
    $stmt->execute([$applicant['id']]);
    $docs = $stmt->fetchAll();
    foreach ($docs as $doc) {
        $uploaded_docs[$doc['document_type']] = $doc;
    }
}

// Get SHS track info
$shs_track = $student['shs_track'] ?? ($applicant['shs_track'] ?? 'N/A');
$shs_strand = $student['shs_strand'] ?? ($applicant['shs_strand'] ?? 'N/A');

$name = $user ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : 'Student';
?>
<div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12">
    <h1 class="text-4xl font-black tracking-tighter italic uppercase mb-2">Hello, <?= $name ?></h1>
    <p class="text-sm font-medium text-black/60 mb-8">Welcome to your NCST Enrollment dashboard.</p>

    <!-- Enrollment Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- College Info -->
        <div class="border border-black/10 rounded-[32px] p-6 bg-white">
            <h3 class="text-sm font-black tracking-tighter uppercase mb-4">College Information</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs font-medium text-black/40">Course</p>
                    <p class="text-sm font-bold"><?= htmlspecialchars($course_name) ?></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-black/40">Status</p>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold <?= $applicant ? ($applicant['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($applicant['status'] === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800')) : 'bg-gray-100 text-gray-800' ?>">
                        <?= $applicant ? ucfirst($applicant['status']) : 'Not Applied' ?>
                    </span>
                </div>
                <div>
                    <p class="text-xs font-medium text-black/40">Academic Year</p>
                    <p class="text-sm font-medium"><?= htmlspecialchars($applicant['academic_year'] ?? 'N/A') ?> - <?= htmlspecialchars($applicant['semester'] ?? 'N/A') ?></p>
                </div>
            </div>
        </div>

        <!-- SHS Info -->
        <div class="border border-black/10 rounded-[32px] p-6 bg-white">
            <h3 class="text-sm font-black tracking-tighter uppercase mb-4">Senior High School Background</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs font-medium text-black/40">Track</p>
                    <p class="text-sm font-bold"><?= htmlspecialchars($shs_track ?: 'N/A') ?></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-black/40">Strand</p>
                    <p class="text-sm font-medium"><?= htmlspecialchars($shs_strand ?: 'N/A') ?></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-black/40">School</p>
                    <p class="text-sm font-medium"><?= htmlspecialchars($student['highschool_name'] ?? ($applicant['highschool_name'] ?? 'N/A')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Accountability Table -->
    <div class="border border-black/10 rounded-[32px] p-6 bg-white">
        <h3 class="text-sm font-black tracking-tighter uppercase mb-4">Document Accountability</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-black/10">
                        <th class="text-left py-3 px-2 text-xs font-medium text-black/40 uppercase">Document</th>
                        <th class="text-left py-3 px-2 text-xs font-medium text-black/40 uppercase">Status</th>
                        <th class="text-left py-3 px-2 text-xs font-medium text-black/40 uppercase">File Name</th>
                        <th class="text-left py-3 px-2 text-xs font-medium text-black/40 uppercase">Date Submitted</th>
                        <th class="text-left py-3 px-2 text-xs font-medium text-black/40 uppercase">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($required_docs as $key => $label): ?>
                        <?php 
                        $doc = $uploaded_docs[$key] ?? null;
                        $status = $doc ? ($doc['status'] ?? 'submitted') : 'not_submitted';
                        ?>
                        <tr class="border-b border-black/5 hover:bg-black/5">
                            <td class="py-3 px-2 font-medium"><?= htmlspecialchars($label) ?></td>
                            <td class="py-3 px-2">
                                <?php if ($status === 'approved'): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">Approved</span>
                                <?php elseif ($status === 'submitted' || $status === 'pending'): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Pending Review</span>
                                <?php elseif ($status === 'rejected'): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">Rejected</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800">Not Submitted</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-2 text-black/60">
                                <?= $doc ? htmlspecialchars($doc['file_name']) : '-' ?>
                            </td>
                            <td class="py-3 px-2 text-black/60">
                                <?= $doc ? date('M d, Y', strtotime($doc['created_at'])) : '-' ?>
                            </td>
                            <td class="py-3 px-2 text-black/60 text-xs">
                                <?= $doc && !empty($doc['notes']) ? htmlspecialchars($doc['notes']) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!$applicant): ?>
            <div class="mt-6 p-4 bg-amber-50 rounded-xl border border-amber-200">
                <p class="text-sm text-amber-800">
                    <strong>Note:</strong> You haven't submitted an enrollment application yet. 
                    <a href="<?= url('/src/view/auth/register/register.php') ?>" class="underline font-bold">Apply now</a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 flex items-center gap-4">
        <?php if ($applicant && $applicant['status'] === 'approved'): ?>
            <span class="px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-800">✓ Enrollment Approved</span>
        <?php elseif ($applicant && $applicant['status'] === 'pending'): ?>
            <span class="px-4 py-2 rounded-full text-sm font-bold bg-amber-100 text-amber-800">⏳ Awaiting Review</span>
        <?php endif; ?>
        <a href="<?= url('/src/view/auth/logout.php') ?>" class="px-4 py-2 text-xs font-bold text-white bg-red-500 hover:bg-red-600 rounded-full transition-colors">Logout</a>
    </div>
</div>
<?php require_once __DIR__ . '/../../../footer.php'; ?>
