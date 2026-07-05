<?php
require_once __DIR__ . '/../../../header.php';
require_student();

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid session token.';
        $message_type = 'error';
    } else {
        $document_type = trim($_POST['document_type'] ?? '');
        $applicant_id = trim($_POST['applicant_id'] ?? '');
        
        if (empty($document_type) || empty($applicant_id)) {
            $message = 'Please select a document type.';
            $message_type = 'error';
        } elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            $message = 'Please select a file to upload.';
            $message_type = 'error';
        } else {
            $file = $_FILES['document_file'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $message = 'Invalid file type. Only JPG, PNG, GIF, and PDF are allowed.';
                $message_type = 'error';
            } elseif ($file['size'] > $max_size) {
                $message = 'File size exceeds 5MB limit.';
                $message_type = 'error';
            } else {
                // Get upload path from database
                $upload_path = get_document_path('applicant_document');
                $upload_dir = dirname(__DIR__, 4) . '/public' . $upload_path;
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = $applicant_id . '_' . $document_type . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Check if document already exists
                    $stmt = $pdo->prepare("SELECT id FROM applicant_documents WHERE applicant_id = ? AND document_type = ?");
                    $stmt->execute([$applicant_id, $document_type]);
                    $existing = $stmt->fetch();
                    
                    $relative_path = $upload_path . $new_filename;
                    
                    if ($existing) {
                        // Update existing document
                        $stmt = $pdo->prepare("UPDATE applicant_documents SET file_name = ?, file_path = ?, file_size = ?, mime_type = ?, status = 'pending', created_at = NOW() WHERE id = ?");
                        $stmt->execute([$file['name'], $relative_path, $file['size'], $file['type'], $existing['id']]);
                        $message = 'Document updated successfully!';
                    } else {
                        // Insert new document
                        $stmt = $pdo->prepare("INSERT INTO applicant_documents (applicant_id, document_type, file_name, file_path, file_size, mime_type, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                        $stmt->execute([$applicant_id, $document_type, $file['name'], $relative_path, $file['size'], $file['type']]);
                        $message = 'Document uploaded successfully!';
                    }
                    $message_type = 'success';
                } else {
                    $message = 'Failed to upload file.';
                    $message_type = 'error';
                }
            }
        }
    }
}

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
    'tor' => 'Transcript of Records (TOR)',
    'honorable_dismissal' => 'Honorable Dismissal',
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

    <?php if ($message): ?>
        <div class="mb-6 px-4 py-3 rounded-xl border <?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
            <p class="text-sm font-medium"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="mb-8 flex flex-wrap items-center gap-4">
        <?php if ($applicant && $applicant['status'] === 'approved'): ?>
            <span class="px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-800">✓ Enrollment Approved</span>
        <?php elseif ($applicant && $applicant['status'] === 'pending'): ?>
            <span class="px-4 py-2 rounded-full text-sm font-bold bg-amber-100 text-amber-800">⏳ Awaiting Review</span>
        <?php endif; ?>
        <button onclick="toggleSection('full-info')" class="px-4 py-2 text-xs font-bold text-google-blue hover:bg-google-blue/10 rounded-full transition-colors border border-google-blue">
            <?= isset($_GET['show_info']) ? 'Hide Full Info' : 'View Full Info' ?>
        </button>
        <?php if ($applicant): ?>
        <button onclick="toggleSection('upload-doc')" class="px-4 py-2 text-xs font-bold text-google-blue hover:bg-google-blue/10 rounded-full transition-colors border border-google-blue">
            Upload Document
        </button>
        <?php endif; ?>
        <a href="<?= url('/src/view/auth/logout.php') ?>" class="px-4 py-2 text-xs font-bold text-white bg-red-500 hover:bg-red-600 rounded-full transition-colors">Logout</a>
    </div>

    <!-- Full Information Section (Hidden by default) -->
    <?php if (isset($_GET['show_info']) || isset($_GET['view'])): ?>
    <div id="full-info-section" class="mb-8 border border-black/10 rounded-[32px] p-6 bg-white">
        <h3 class="text-sm font-black tracking-tighter uppercase mb-6">Full Information</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Personal Information -->
            <div>
                <h4 class="text-xs font-bold text-black/40 uppercase mb-3">Personal Information</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-black/50">Full Name:</span>
                        <span class="font-medium"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Birthday:</span>
                        <span class="font-medium"><?= $applicant ? date('F d, Y', strtotime($applicant['birthday'])) : 'N/A' ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Gender:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['gender'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Civil Status:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['civil_status'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Nationality:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['nationality'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div>
                <h4 class="text-xs font-bold text-black/40 uppercase mb-3">Contact Information</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-black/50">Email:</span>
                        <span class="font-medium"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Contact:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['contact_number'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Address:</span>
                        <span class="font-medium text-right max-w-[200px]"><?= htmlspecialchars(($applicant['home_address'] ?? '') . ', ' . ($applicant['city'] ?? '')) ?></span>
                    </div>
                </div>
            </div>

            <!-- Educational Background -->
            <div>
                <h4 class="text-xs font-bold text-black/40 uppercase mb-3">Educational Background</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-black/50">Education Type:</span>
                        <span class="font-medium"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($applicant['education_type'] ?? 'N/A'))) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">High School:</span>
                        <span class="font-medium text-right max-w-[200px]"><?= htmlspecialchars($applicant['highschool_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">SHS Track:</span>
                        <span class="font-medium"><?= htmlspecialchars($shs_track ?: 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">SHS Strand:</span>
                        <span class="font-medium"><?= htmlspecialchars($shs_strand ?: 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Year Graduated:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['year_graduated'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">LRN:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['lrn'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>

            <!-- Enrollment Information -->
            <div>
                <h4 class="text-xs font-bold text-black/40 uppercase mb-3">Enrollment Information</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-black/50">Course:</span>
                        <span class="font-medium"><?= htmlspecialchars($course_name) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Second Choice:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['second_course'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Academic Year:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['academic_year'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Semester:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['semester'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Status:</span>
                        <span class="font-medium"><?= htmlspecialchars(ucfirst($applicant['status'] ?? 'N/A')) ?></span>
                    </div>
                </div>
            </div>

            <!-- Guardian Information -->
            <div>
                <h4 class="text-xs font-bold text-black/40 uppercase mb-3">Guardian Information</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-black/50">Father:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['father_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Mother:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['mother_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Guardian:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['guardian_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Guardian Contact:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['guardian_contact'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>

            <?php if ($applicant['education_type'] === 'transferee'): ?>
            <!-- Transferee Information -->
            <div>
                <h4 class="text-xs font-bold text-black/40 uppercase mb-3">Transferee Information</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-black/50">Previous College:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['previous_college'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Previous Course:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['previous_course'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-black/50">Last Year Level:</span>
                        <span class="font-medium"><?= htmlspecialchars($applicant['last_year_level'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upload Document Section (Hidden by default) -->
    <?php if ($applicant): ?>
    <div id="upload-doc-section" class="hidden mb-8 border border-black/10 rounded-[32px] p-6 bg-white">
        <h3 class="text-sm font-black tracking-tighter uppercase mb-4">Upload Document</h3>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="applicant_id" value="<?= $applicant['id'] ?>">
            <input type="hidden" name="upload_document" value="1">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                    <label class="text-xs font-medium text-black/50">Document Type</label>
                    <select name="document_type" required class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                        <option value="">Select Document</option>
                        <?php foreach ($required_docs as $key => $label): ?>
                            <?php 
                            $has_doc = isset($uploaded_docs[$key]);
                            $status_class = $has_doc ? ($uploaded_docs[$key]['status'] === 'approved' ? 'text-green-600' : 'text-amber-600') : '';
                            ?>
                            <option value="<?= $key ?>" class="<?= $status_class ?>">
                                <?= $label ?><?= $has_doc ? ' (Replace existing)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                    <label class="text-xs font-medium text-black/50">Select File (Max 5MB)</label>
                    <input type="file" name="document_file" required accept=".jpg,.jpeg,.png,.gif,.pdf"
                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-google-blue file:text-white hover:file:bg-google-blue-hover">
                </div>
            </div>
            
            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors">
                    Upload Document
                </button>
                <button type="button" onclick="toggleSection('upload-doc')" class="px-6 py-2 text-sm font-bold text-black/60 hover:text-black hover:bg-black/5 rounded-full transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Enrollment Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- College Info -->
        <div class="border border-black/10 rounded-[32px] p-6 bg-white">
            <h3 class="text-sm font-black tracking-tighter uppercase mb-4">Enrollment Information</h3>
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
                    <p class="text-xs font-medium text-black/40">Education Type</p>
                    <p class="text-sm font-bold"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($applicant['education_type'] ?? 'N/A'))) ?></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-black/40">Track</p>
                    <p class="text-sm font-medium"><?= htmlspecialchars($shs_track ?: 'N/A') ?></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-black/40">Strand</p>
                    <p class="text-sm font-medium"><?= htmlspecialchars($shs_strand ?: 'N/A') ?></p>
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
</div>

<script>
function toggleSection(section) {
    const fullInfoSection = document.getElementById('full-info-section');
    const uploadDocSection = document.getElementById('upload-doc-section');
    
    if (section === 'full-info') {
        if (fullInfoSection) {
            fullInfoSection.classList.toggle('hidden');
        }
    } else if (section === 'upload-doc') {
        if (uploadDocSection) {
            uploadDocSection.classList.toggle('hidden');
        }
    }
}
</script>

<?php require_once __DIR__ . '/../../../footer.php'; ?>
