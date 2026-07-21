<?php
require_once __DIR__ . '/../../../header.php';
require_student();

$user_id = $_SESSION['user_id'];

// Handle section update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_section'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid session token.';
        $type = 'error';
    } else {
        $section_id = !empty($_POST['section_id']) ? (int) $_POST['section_id'] : null;
        
        try {
            // Validate section exists if provided
            if ($section_id !== null) {
                $stmt = $pdo->prepare("SELECT id FROM sections WHERE id = ?");
                $stmt->execute([$section_id]);
                if (!$stmt->fetch()) {
                    $msg = 'Invalid section selected.';
                    $type = 'error';
                    header('Location: ' . url('/src/view/student/dashboard.php?tab=enrollment&msg=' . urlencode($msg) . '&type=' . urlencode($type)));
                    exit;
                }
            }
            
            // Check if student record exists
            $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $studentRecord = $stmt->fetch();
            
            if ($studentRecord) {
                $stmt = $pdo->prepare("UPDATE students SET section_id = ? WHERE user_id = ?");
                $stmt->execute([$section_id, $user_id]);
            } else {
                // Create student record with section if it doesn't exist
                $stmt = $pdo->prepare("INSERT INTO students (user_id, section_id, enrollment_status) VALUES (?, ?, 'pending')");
                $stmt->execute([$user_id, $section_id]);
            }
            
            $msg = 'Section updated successfully!';
            $type = 'success';
        } catch (PDOException $e) {
            $msg = 'Failed to update section.';
            $type = 'error';
        }
    }
    header('Location: ' . url('/src/view/student/dashboard.php?tab=enrollment&msg=' . urlencode($msg) . '&type=' . urlencode($type)));
    exit;
}

// Handle contact info update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid session token.';
        $type = 'error';
    } else {
        $email = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $home_address = trim($_POST['home_address'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');

        if (empty($email)) {
            $msg = 'Email is required.';
            $type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Invalid email format.';
            $type = 'error';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$email, $user_id]);

                $stmt = $pdo->prepare("SELECT * FROM applicants WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$user_id]);
                $app = $stmt->fetch();

                if ($app) {
                    $stmt = $pdo->prepare("UPDATE applicants SET contact_number = ?, home_address = ?, province = ?, city = ?, barangay = ?, zip_code = ? WHERE id = ?");
                    $stmt->execute([$contact_number, $home_address, $province, $city, $barangay, $zip_code, $app['id']]);
                }

                $pdo->commit();
                $msg = 'Contact information updated successfully!';
                $type = 'success';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $msg = 'Failed to update contact information.';
                $type = 'error';
            }
        }
    }
    header('Location: ' . url('/src/view/student/dashboard.php?tab=contact&msg=' . urlencode($msg) . '&type=' . urlencode($type)));
    exit;
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid session token.';
        $type = 'error';
    } else {
        $document_type = trim($_POST['document_type'] ?? '');
        $applicant_id = trim($_POST['applicant_id'] ?? '');
        
        if (empty($document_type) || empty($applicant_id)) {
            $msg = 'Please select a document type.';
            $type = 'error';
        } elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            $msg = 'Please select a file to upload.';
            $type = 'error';
        } else {
            $file = $_FILES['document_file'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $max_size = 5 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $msg = 'Invalid file type. Only JPG, PNG, GIF, and PDF are allowed.';
                $type = 'error';
            } elseif ($file['size'] > $max_size) {
                $msg = 'File size exceeds 5MB limit.';
                $type = 'error';
            } else {
                $upload_path = get_document_path('applicant_document') ?? '/assets/uploads/documents/applicants/';
                $upload_dir = PROJECT_ROOT . $upload_path;
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = $applicant_id . '_' . $document_type . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $stmt = $pdo->prepare("SELECT id FROM applicant_documents WHERE applicant_id = ? AND document_type = ?");
                    $stmt->execute([$applicant_id, $document_type]);
                    $existing = $stmt->fetch();
                    
                    $relative_path = $upload_path . $new_filename;
                    
                    if ($existing) {
                        $stmt = $pdo->prepare("UPDATE applicant_documents SET file_name = ?, file_path = ?, file_size = ?, mime_type = ?, status = 'pending', created_at = NOW() WHERE id = ?");
                        $stmt->execute([$file['name'], $relative_path, $file['size'], $file['type'], $existing['id']]);
                        $msg = 'Document updated successfully!';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO applicant_documents (applicant_id, document_type, file_name, file_path, file_size, mime_type, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                        $stmt->execute([$applicant_id, $document_type, $file['name'], $relative_path, $file['size'], $file['type']]);
                        $msg = 'Document uploaded successfully!';
                    }
                    $type = 'success';
                } else {
                    $msg = 'Failed to upload file.';
                    $type = 'error';
                }
            }
        }
    }
    header('Location: ' . url('/src/view/student/dashboard.php?tab=documents&msg=' . urlencode($msg) . '&type=' . urlencode($type)));
    exit;
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
$shs_track = $student['shs_track'] ?? ($applicant['shs_track'] ?? '');
$shs_strand = $student['shs_strand'] ?? ($applicant['shs_strand'] ?? '');

$name = $user ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : 'Student';
$is_transferee = ($applicant['education_type'] ?? '') === 'transferee';

// Determine active tab and message from GET
$active_tab = $_GET['tab'] ?? 'overview';
$modal_msg = $_GET['msg'] ?? '';
$modal_type = $_GET['type'] ?? '';

$sidebar_items = [
    'overview' => ['label' => 'Overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    'personal' => ['label' => 'Personal Info', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    'contact' => ['label' => 'Contact Info', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
    'education' => ['label' => 'Education', 'icon' => 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z'],
    'enrollment' => ['label' => 'Enrollment', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    'guardian' => ['label' => 'Guardian', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
    'documents' => ['label' => 'Documents', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    'payments' => ['label' => 'Payments', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
];
if ($is_transferee) {
    $sidebar_items['transferee'] = ['label' => 'Transferee', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'];
}
?>
<div class="min-h-screen bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-black/10 min-h-screen flex flex-col shrink-0">
            <div class="p-6 border-b border-black/10">
                <h2 class="text-sm font-black tracking-tighter uppercase">Dashboard</h2>
                <p class="text-xs text-black/40 mt-1 truncate"><?= $name ?></p>
            </div>
            <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
                <?php foreach ($sidebar_items as $key => $item): ?>
                    <button onclick="switchTab('<?= $key ?>')"
                        class="sidebar-btn w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 text-black/60 hover:bg-google-blue/5 hover:text-google-blue <?= $key === $active_tab ? 'active bg-google-blue/10 text-google-blue' : '' ?>"
                        data-tab="<?= $key ?>">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>" />
                        </svg>
                        <?= $item['label'] ?>
                    </button>
                <?php endforeach; ?>
            </nav>
            <div class="p-3 border-t border-black/10">
                <a href="<?= url('/src/view/auth/logout.php') ?>"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-xl text-red-500 hover:bg-red-50 transition-all duration-200">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 md:p-8 max-w-[900px]">
            <div id="tab-overview" class="tab-content <?= $active_tab !== 'overview' ? 'hidden' : '' ?>"><?php require __DIR__ . '/tabs/overview.php' ?></div>
            <div id="tab-personal" class="tab-content <?= $active_tab !== 'personal' ? 'hidden' : '' ?>"><?php require __DIR__ . '/tabs/personal.php' ?></div>
            <div id="tab-contact" class="tab-content <?= $active_tab !== 'contact' ? 'hidden' : '' ?>"><?php require __DIR__ . '/tabs/contact.php' ?></div>
            <div id="tab-education" class="tab-content <?= $active_tab !== 'education' ? 'hidden' : '' ?>"><?php require __DIR__ . '/tabs/education.php' ?></div>
            <div id="tab-enrollment" class="tab-content <?= $active_tab !== 'enrollment' ? 'hidden' : '' ?>"><?php require __DIR__ . '/tabs/enrollment.php' ?></div>
            <div id="tab-guardian" class="tab-content <?= $active_tab !== 'guardian' ? 'hidden' : '' ?>"><?php require __DIR__ . '/tabs/guardian.php' ?></div>
            <div id="tab-documents" class="tab-content <?= $active_tab !== 'documents' ? 'hidden' : '' ?>"><?php require __DIR__ . '/tabs/documents.php' ?></div>
            <div id="tab-payments" class="tab-content <?= $active_tab !== 'payments' ? 'hidden' : '' ?>"><?php require __DIR__ . '/tabs/payments.php' ?></div>
            <?php if ($is_transferee): ?>
            <div id="tab-transferee" class="tab-content <?= $active_tab !== 'transferee' ? 'hidden' : '' ?>"><?php require __DIR__ . '/tabs/transferee.php' ?></div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal -->
<div id="msgModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal()"></div>
    <div class="relative bg-white rounded-2xl p-6 max-w-sm w-full mx-4 shadow-xl">
        <div class="text-center">
            <div id="modalIcon" class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path id="modalIconPath" stroke-linecap="round" stroke-linejoin="round" d="" />
                </svg>
            </div>
            <p id="modalText" class="text-sm font-medium text-gray-900"></p>
        </div>
        <button onclick="closeModal()"
            class="mt-5 w-full px-4 py-2 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors">
            OK
        </button>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    const target = document.getElementById('tab-' + tab);
    if (target) target.classList.remove('hidden');
    document.querySelectorAll('.sidebar-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-google-blue/10', 'text-google-blue');
        if (btn.dataset.tab === tab) {
            btn.classList.add('active', 'bg-google-blue/10', 'text-google-blue');
        }
    });
    history.replaceState(null, '', '?tab=' + tab);
}

function closeModal() {
    document.getElementById('msgModal').classList.add('hidden');
    const url = new URL(window.location);
    url.searchParams.delete('msg');
    url.searchParams.delete('type');
    history.replaceState(null, '', url);
}

document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash.replace('#', '');
    const tab = hash || '<?= $active_tab ?>';
    if (document.getElementById('tab-' + tab)) {
        switchTab(tab);
    }

    // Show modal if message exists
    const msg = '<?= addslashes($modal_msg) ?>';
    const type = '<?= addslashes($modal_type) ?>';
    if (msg) {
        const modal = document.getElementById('msgModal');
        const text = document.getElementById('modalText');
        const icon = document.getElementById('modalIcon');
        const path = document.getElementById('modalIconPath');

        text.textContent = msg;
        if (type === 'success') {
            icon.className = 'w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center bg-green-100';
            path.setAttribute('d', 'M5 13l4 4L19 7');
        } else {
            icon.className = 'w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center bg-red-100';
            path.setAttribute('d', 'M6 18L18 6M6 6l12 12');
        }
        modal.classList.remove('hidden');
    }
});
</script>

<?php require_once __DIR__ . '/../../../footer.php'; ?>
