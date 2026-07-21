<?php
ob_start();
$page_title = 'Applicants';
require_once __DIR__ . '/sidebar.php';

$errors = [];
$success = '';
$ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

function approve_applicant($pdo, $applicant_id, $reviewer_id)
{
    $stmt = $pdo->prepare("SELECT * FROM applicants WHERE id = ? AND status = 'pending'");
    $stmt->execute([$applicant_id]);
    $applicant = $stmt->fetch();

    if (!$applicant)
        return 'Applicant not found or already processed.';

    $hashed_password = password_hash('ncst123', PASSWORD_DEFAULT);
    $role = ($applicant['education_type'] === 'senior_high') ? 'shs' : 'college';

    $stmt = $pdo->prepare("INSERT INTO users (
        first_name, middle_name, last_name, suffix, birthday, gender,
        civil_status, nationality, religion, birth_place,
        email, contact_number, home_address, province, city, barangay, zip_code,
        password, role, status, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $applicant['first_name'],
        $applicant['middle_name'],
        $applicant['last_name'],
        $applicant['suffix'],
        $applicant['birthday'],
        $applicant['gender'],
        $applicant['civil_status'],
        $applicant['nationality'],
        $applicant['religion'],
        $applicant['birth_place'],
        $applicant['email'],
        $applicant['contact_number'],
        $applicant['home_address'],
        $applicant['province'],
        $applicant['city'],
        $applicant['barangay'],
        $applicant['zip_code'],
        $hashed_password,
        $role,
        'active',
        $reviewer_id
    ]);
    $user_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO students (
        user_id, father_name, mother_name, guardian_name, guardian_contact, guardian_relationship,
        education_type, highschool_name, highschool_address, shs_track, shs_strand, year_graduated, lrn,
        previous_college, previous_course, last_year_level,
        preferred_course, second_course, semester, academic_year
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $applicant['father_name'],
        $applicant['mother_name'],
        $applicant['guardian_name'],
        $applicant['guardian_contact'],
        $applicant['guardian_relationship'],
        $applicant['education_type'],
        $applicant['highschool_name'],
        $applicant['highschool_address'],
        $applicant['shs_track'],
        $applicant['shs_strand'],
        $applicant['year_graduated'],
        $applicant['lrn'],
        $applicant['previous_college'],
        $applicant['previous_course'],
        $applicant['last_year_level'],
        $applicant['preferred_course'],
        $applicant['second_course'],
        $applicant['semester'],
        $applicant['academic_year']
    ]);

    // Move documents
    $stmt = $pdo->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $docs = $stmt->fetchAll();

    $student_upload_path = get_document_path('student_document') ?: '/assets/uploads/documents/students/';
    $student_upload_dir = PROJECT_ROOT . $student_upload_path;
    if (!is_dir($student_upload_dir))
        mkdir($student_upload_dir, 0755, true);

    foreach ($docs as $doc) {
        $old_file = PROJECT_ROOT . ltrim($doc['file_path'], '/');
        if (file_exists($old_file)) {
            $new_file_path = $student_upload_path . basename($doc['file_path']);
            $new_file_full = PROJECT_ROOT . ltrim($new_file_path, '/');
            if (copy($old_file, $new_file_full)) {
                $stmt_upd = $pdo->prepare("UPDATE applicant_documents SET file_path = ? WHERE id = ?");
                $stmt_upd->execute([$new_file_path, $doc['id']]);
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE applicants SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), user_id = ? WHERE id = ?");
    $stmt->execute([$reviewer_id, $user_id, $applicant_id]);

    send_approval_email($applicant['email'], $applicant['first_name'], 'ncst123');

    return "Application approved! Student account created with default password: ncst123";
}

// Handle document status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doc_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid session token.';
        if ($ajax) {
            echo json_encode(['ok' => false, 'msg' => $msg]);
            exit;
        }
        $errors[] = $msg;
    } else {
        $doc_id = (int) ($_POST['doc_id'] ?? 0);
        $status = $_POST['doc_status'] ?? '';
        $notes = trim($_POST['doc_notes'] ?? '');

        if ($doc_id && in_array($status, ['pending', 'submitted', 'approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE applicant_documents SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $doc_id]);
            $success = 'Document status updated.';

            // Get applicant_id for this doc
            $stmt = $pdo->prepare("SELECT applicant_id FROM applicant_documents WHERE id = ?");
            $stmt->execute([$doc_id]);
            $doc_row = $stmt->fetch();
            $applicant_id = $doc_row ? $doc_row['applicant_id'] : 0;

            // Auto-approve if all docs are approved
            if ($applicant_id && $status === 'approved') {
                $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved FROM applicant_documents WHERE applicant_id = ?");
                $stmt->execute([$applicant_id]);
                $cnt = $stmt->fetch();
                if ($cnt && $cnt['total'] > 0 && $cnt['total'] == $cnt['approved']) {
                    $approve_msg = approve_applicant($pdo, $applicant_id, $_SESSION['user_id']);
                    $success .= ' ' . $approve_msg;
                }
            }

            if ($ajax) {
                ob_clean();
                echo json_encode(['ok' => true, 'msg' => $success]);
                exit;
            }
        } else {
            $msg = 'Invalid request.';
            if ($ajax) {
                ob_clean();
                echo json_encode(['ok' => false, 'msg' => $msg]);
                exit;
            }
            $errors[] = $msg;
        }
    }
}

// Handle appointment save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_appointment'])) {
    $applicant_id = (int) ($_POST['applicant_id'] ?? 0);
    $appointment_label = trim($_POST['appointment_label'] ?? '');
    $appointment = trim($_POST['appointment'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        ob_clean();
        echo json_encode(['ok' => false, 'msg' => 'Invalid session token.']);
        exit;
    }

    if (!$applicant_id || empty($appointment)) {
        ob_clean();
        echo json_encode(['ok' => false, 'msg' => 'Please select a date and time.']);
        exit;
    }

    try {
        $appointment_dt = date('Y-m-d H:i:s', strtotime($appointment));
        $stmt = $pdo->prepare("UPDATE applicants SET appointment_label = ?, appointment = ? WHERE id = ?");
        $stmt->execute([$appointment_label, $appointment_dt, $applicant_id]);
        ob_clean();
        echo json_encode(['ok' => true, 'msg' => 'Appointment set successfully.']);
        exit;
    } catch (PDOException $e) {
        ob_clean();
        echo json_encode(['ok' => false, 'msg' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $applicant_id = (int) ($_POST['applicant_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');

        if ($applicant_id && in_array($action, ['approve', 'reject', 'revision'])) {
            try {
                $pdo->beginTransaction();

                if ($action === 'approve') {
                    $success = approve_applicant($pdo, $applicant_id, $_SESSION['user_id']);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE applicants SET status = ?, rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$action === 'revision' ? 'revision' : 'rejected', $rejection_reason, $_SESSION['user_id'], $applicant_id]);
                    $success = "Application marked as " . ($action === 'revision' ? 'needs revision' : 'rejected') . ".";
                }

                $pdo->commit();

                // Send email notification
                if ($action === 'reject') {
                    $stmt2 = $pdo->query("SELECT * FROM applicants WHERE id = $applicant_id");
                    $rejected = $stmt2->fetch();
                    if ($rejected) {
                        send_rejection_email($rejected['email'], $rejected['first_name'], $rejection_reason);
                    }
                } elseif ($action === 'revision') {
                    $stmt2 = $pdo->query("SELECT * FROM applicants WHERE id = $applicant_id");
                    $rev = $stmt2->fetch();
                    if ($rev) {
                        send_revision_email($rev['email'], $rev['first_name'], $rejection_reason);
                    }
                }

            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get document types for labels
$doc_type_labels = [
    'psa_birth_certificate' => 'PSA Birth Certificate',
    'form_138' => 'Form 138 / Report Card',
    'good_moral' => 'Good Moral Certificate',
    'certificate_of_graduation' => 'Certificate of Graduation',
    'id_photo_2x2' => 'ID Photo (2x2)',
    'valid_id' => 'Valid ID',
    'tor' => 'Transcript of Records',
    'honorable_dismissal' => 'Honorable Dismissal',
    'other' => 'Other',
];

$doc_quick_notes = [
    'Need verification — refer to OSA' => 'For OSA review and verification of documents.',
    'Need resubmit — unclear copy' => 'Document is unclear. Please resubmit a clearer copy.',
    'Need resubmit — incomplete' => 'Document is incomplete. Please resubmit the complete version.',
    'Under review' => 'Document is currently under review.',
    'Approved — all clear' => 'Document has been verified and approved.',
];

// Get filter
$status_filter = $_GET['status'] ?? 'pending';

$query = "SELECT a.*, u.first_name as reviewed_by_name 
          FROM applicants a 
          LEFT JOIN users u ON a.reviewed_by = u.id 
          WHERE 1=1";

if ($status_filter !== 'all') {
    $query .= " AND a.status = '" . addslashes($status_filter) . "'";
}
$query .= " ORDER BY a.created_at DESC";

try {
    $applicants = $pdo->query($query)->fetchAll();
} catch (Exception $e) {
    $applicants = [];
}

// Get documents per applicant
$applicant_docs = [];
if (!empty($applicants)) {
    $ids = array_column($applicants, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    try {
        $stmt = $pdo->prepare("SELECT * FROM applicant_documents WHERE applicant_id IN ($placeholders) ORDER BY created_at DESC");
        $stmt->execute(array_values($ids));
        foreach ($stmt->fetchAll() as $doc) {
            $applicant_docs[$doc['applicant_id']][] = $doc;
        }
    } catch (Exception $e) {
    }
}

// Get counts
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'revision' => 0, 'all' => count($applicants)];
foreach ($applicants as $a) {
    if (isset($counts[$a['status']])) {
        $counts[$a['status']]++;
    }
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Applicants</h1>
    <p class="text-gray-500 mt-1">Review and manage student enrollment applications</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
        <?php foreach ($errors as $e): ?>
            <p class="text-sm font-bold text-red-600"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 rounded-xl">
        <p class="text-sm font-bold text-green-600"><?= htmlspecialchars($success) ?></p>
    </div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="flex flex-wrap gap-2 mb-6 bg-white p-2 rounded-xl shadow-sm">
    <a href="?status=pending"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'pending' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
        Pending <?php if ($counts['pending'] > 0): ?><span
                class="ml-1 bg-amber-400 text-amber-900 px-2 py-0.5 rounded-full text-xs"><?= $counts['pending'] ?></span><?php endif; ?>
    </a>
    <a href="?status=approved"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'approved' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
        Approved
    </a>
    <a href="?status=rejected"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'rejected' ? 'bg-red-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
        Rejected
    </a>
    <a href="?status=revision"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'revision' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
        Needs Revision
    </a>
    <a href="?status=all"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'all' ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
        All (<?= $counts['all'] ?>)
    </a>
</div>

<!-- Applications List -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($applicants)): ?>
        <div class="p-8 text-center text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p>No applications found.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Education
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($applicants as $app): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900">
                                    <?= htmlspecialchars($app['first_name'] . ' ' . ($app['middle_name'] ?? '') . ' ' . $app['last_name']) ?>
                                </div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($app['email']) ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium"><?= htmlspecialchars($app['preferred_course']) ?></div>
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars(($app['academic_year'] ?? '') . ' - ' . ($app['semester'] ?? '')) ?>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="capitalize"><?= htmlspecialchars($app['education_type'] ?? 'freshman') ?></span>
                            </td>
                            <td class="px-4 py-4">
                                <?php
                                $status_class = match ($app['status']) {
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'revision' => 'bg-indigo-100 text-indigo-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                                    <?= ucfirst($app['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($app['created_at'])) ?>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <button onclick="viewApplication(<?= $app['id'] ?>)"
                                    class="text-blue-600 hover:underline text-sm">View Details</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Confirm Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[99999]">
    <div class="bg-white rounded-2xl max-w-md w-full m-4 p-6 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2" id="confirmTitle">Confirm Action</h3>
        <p class="text-sm text-gray-500 mb-6" id="confirmMessage">Are you sure?</p>
        <div class="flex gap-3 justify-center">
            <button onclick="closeConfirmModal()"
                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">Cancel</button>
            <button id="confirmOkBtn"
                class="px-6 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">Confirm</button>
        </div>
    </div>
</div>

<!-- Application Detail Modal -->
<div id="applicationModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto m-4">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-bold" id="modalTitle">Application Details</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-6" id="modalContent"></div>
    </div>
</div>

<!-- Document Viewer Modal -->
<div id="docViewerModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000]">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto m-4">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
            <h2 class="text-lg font-bold" id="docViewerTitle">Document</h2>
            <button onclick="closeDocViewer()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div id="docViewerContent" class="p-4 flex items-center justify-center min-h-[200px] bg-gray-50"></div>
    </div>
</div>

<script>
    const applicants = <?= json_encode(array_column($applicants, null, 'id')) ?>;
    const applicantDocs = <?= json_encode($applicant_docs) ?>;
    const docTypeLabels = <?= json_encode($doc_type_labels) ?>;
    const quickNotes = <?= json_encode($doc_quick_notes) ?>;
    const csrfToken = '<?= $_SESSION['csrf_token'] ?>';

    let pendingApproveId = null;

    function statusBadge(status) {
        const map = {
            'pending': { cls: 'bg-gray-100 text-gray-800', label: 'Pending' },
            'submitted': { cls: 'bg-blue-100 text-blue-800', label: 'Submitted' },
            'approved': { cls: 'bg-green-100 text-green-800', label: 'Approved' },
            'rejected': { cls: 'bg-red-100 text-red-800', label: 'Rejected' },
        };
        const s = map[status] || map['pending'];
        return `<span class="px-2 py-0.5 rounded-full text-xs font-medium ${s.cls}">${s.label}</span>`;
    }

    function viewApplication(id) {
        const app = applicants[id];
        if (!app) return;

        const docs = applicantDocs[id] || [];

        // Documents section
        let docsHtml = '<p class="text-sm text-gray-500">No documents uploaded.</p>';
        if (docs.length > 0) {
            docsHtml = docs.map(d => `
                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">${docTypeLabels[d.document_type] || d.document_type.replace(/_/g, ' ')}</p>
                            <a href="javascript:void(0)" onclick="openDocViewer('<?= url('/') ?>${d.file_path}', '${d.file_name}')" class="text-xs text-blue-600 hover:underline">${d.file_name}</a>
                            <div class="mt-1"><span id="docBadge_${d.id}">${statusBadge(d.status || 'pending')}</span></div>
                            ${d.notes ? `<p class="text-xs text-gray-500 mt-1 italic" id="docNote_${d.id}">${d.notes}</p>` : `<p class="text-xs text-gray-500 mt-1 italic" id="docNote_${d.id}"></p>`}
                        </div>
                    </div>
                    <form class="mt-2 space-y-2 js-doc-status-form">
                        <input type="hidden" name="csrf_token" value="${csrfToken}">
                        <input type="hidden" name="update_doc_status" value="1">
                        <input type="hidden" name="doc_id" value="${d.id}">
                        <div class="flex gap-2 items-start">
                            <select name="doc_status" class="text-xs px-2 py-1 border rounded">
                                <option value="pending" ${(d.status || 'pending') === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="submitted" ${d.status === 'submitted' ? 'selected' : ''}>Submitted</option>
                                <option value="approved" ${d.status === 'approved' ? 'selected' : ''}>Approved</option>
                                <option value="rejected" ${d.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                            </select>
                            <button type="submit" class="text-xs px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
                        </div>
                        <div class="flex gap-1 flex-wrap">
                            ${Object.keys(quickNotes).map(note => `
                                <button type="button" onclick="document.getElementById('notes_${d.id}').value='${quickNotes[note].replace(/'/g, "\\'")}'" class="text-[10px] px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">${note}</button>
                            `).join('')}
                        </div>
                        <textarea name="doc_notes" id="notes_${d.id}" rows="2" class="w-full text-xs px-2 py-1 border rounded" placeholder="Add notes...">${d.notes || ''}</textarea>
                    </form>
                </div>
            `).join('');
        }

        // Overall action form
        let actionForm = '';
        if (app.status === 'pending') {
            actionForm = `
            <div class="border-t pt-4">
                <h3 class="font-bold text-gray-900 mb-3">Take Action</h3>
                
                <div class="flex flex-wrap gap-3">
                    <button type="button" onclick="showApproveConfirm(${id})" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700">
                        ✓ Approve Application
                    </button>
                    <button type="button" onclick="showRejectForm(${id})" class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700">
                        ✗ Reject
                    </button>
                    <button type="button" onclick="showRevisionForm(${id})" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                        ⟳ Request Revision
                    </button>
                </div>
                
                <div id="rejectForm${id}" class="hidden mt-4 p-4 bg-red-50 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason</label>
                    <textarea name="rejection_reason" rows="3" class="w-full px-3 py-2 border rounded-lg" placeholder="Enter reason..."></textarea>
                    <button type="button" onclick="submitReject(${id})" class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Confirm Rejection</button>
                </div>
                
                <div id="revisionForm${id}" class="hidden mt-4 p-4 bg-indigo-50 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Required Revisions</label>
                    <textarea name="revision_reason" rows="3" class="w-full px-3 py-2 border rounded-lg" placeholder="Enter what needs to be revised..."></textarea>
                    <button type="button" onclick="submitRevision(${id})" class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Request Revision</button>
                </div>
            </div>
        `;
        }

        function val(v, fallback) { return v || fallback || '-'; }

        document.getElementById('modalContent').innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-bold text-gray-900 mb-3">Personal Information</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex"><dt class="w-32 text-gray-500">Name:</dt><dd class="font-medium">${val(app.first_name)} ${val(app.middle_name)} ${val(app.last_name)} ${val(app.suffix)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Birthday:</dt><dd>${val(app.birthday)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Gender:</dt><dd>${val(app.gender)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Civil Status:</dt><dd>${val(app.civil_status)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Nationality:</dt><dd>${val(app.nationality, 'Filipino')}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Religion:</dt><dd>${val(app.religion)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Birth Place:</dt><dd>${val(app.birth_place)}</dd></div>
                </dl>
            </div>
            
            <div>
                <h3 class="font-bold text-gray-900 mb-3">Contact Information</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex"><dt class="w-32 text-gray-500">Email:</dt><dd>${app.email}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Phone:</dt><dd>${val(app.contact_number)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Address:</dt><dd>${val(app.home_address)}, ${val(app.barangay)}, ${val(app.city)}, ${val(app.province)} ${val(app.zip_code)}</dd></div>
                </dl>
            </div>
            
            <div>
                <h3 class="font-bold text-gray-900 mb-3">Parent / Guardian</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex"><dt class="w-32 text-gray-500">Father:</dt><dd>${val(app.father_name)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Mother:</dt><dd>${val(app.mother_name)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Guardian:</dt><dd>${val(app.guardian_name)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Guardian Contact:</dt><dd>${val(app.guardian_contact)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Relationship:</dt><dd>${val(app.guardian_relationship)}</dd></div>
                </dl>
            </div>
            
            <div>
                <h3 class="font-bold text-gray-900 mb-3">Education Background</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex"><dt class="w-32 text-gray-500">Education Type:</dt><dd class="capitalize">${val(app.education_type, 'freshman')}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">High School:</dt><dd>${val(app.highschool_name)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">School Address:</dt><dd>${val(app.highschool_address)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">SHS Track:</dt><dd>${val(app.shs_track)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">SHS Strand:</dt><dd>${val(app.shs_strand)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Year Graduated:</dt><dd>${val(app.year_graduated)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">LRN:</dt><dd>${val(app.lrn)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Previous College:</dt><dd>${val(app.previous_college)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Previous Course:</dt><dd>${val(app.previous_course)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Last Year Level:</dt><dd>${val(app.last_year_level)}</dd></div>
                </dl>
            </div>

            <div>
                <h3 class="font-bold text-gray-900 mb-3">Course Selection</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex"><dt class="w-32 text-gray-500">First Choice:</dt><dd class="font-medium">${val(app.preferred_course)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Second Choice:</dt><dd>${val(app.second_course)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Academic Year:</dt><dd class="font-medium">${val(app.academic_year)}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Semester:</dt><dd class="font-medium">${val(app.semester)}</dd></div>
                </dl>
            </div>
        </div>

        <div class="border-t pt-4 mt-4">
            <h3 class="font-bold text-gray-900 mb-3">Documents</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                ${docsHtml}
            </div>
        </div>
        
        <div class="border-t pt-6 mt-6 px-1 pb-4" id="appointmentSection">
            <h3 class="font-bold text-gray-900 mb-4">Appointment</h3>
            <div id="appointmentDisplay_${id}">
                ${app.appointment ? `
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl mb-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <div>
                                <p class="font-medium text-blue-800">${val(app.appointment_label)}</p>
                                <p class="text-sm text-blue-600 mt-0.5">${new Date(app.appointment).toLocaleString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
                                <button type="button" onclick="showAppointmentForm(${id})" class="mt-2 text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">Edit Appointment</button>
                            </div>
                        </div>
                    </div>
                ` : `
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl mb-4 text-center">
                        <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm text-gray-500">No appointment set for this applicant.</p>
                    </div>
                `}
            </div>
            <div id="appointmentForm_${id}" class="hidden p-4 bg-gray-50 border border-gray-200 rounded-xl">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="relative border border-black/10 rounded-xl px-4 py-3 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-white">
                        <label class="text-xs font-medium text-black/50">Appointment Date & Time *</label>
                        <input type="datetime-local" name="appointment" id="appointmentDate_${id}" value="${app.appointment ? app.appointment.substring(0, 16) : ''}"
                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1.5 py-1">
                    </div>
                    <div class="relative border border-black/10 rounded-xl px-4 py-3 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-white">
                        <label class="text-xs font-medium text-black/50">Appointment Label</label>
                        <input type="text" name="appointment_label" id="appointmentLabel_${id}" value="${val(app.appointment_label, 'Enrollment Interview')}"
                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1.5 py-1 placeholder:text-black/30" placeholder="e.g. Enrollment Interview">
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="button" onclick="saveAppointment(${id})" class="px-5 py-2 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95 px-4">Save Appointment</button>
                    <button type="button" onclick="hideAppointmentForm(${id})" class="px-5 py-2 text-sm font-bold text-black/60 hover:text-black border border-black/10 rounded-full transition-colors active:scale-95 px-4">Cancel</button>
                </div>
            </div>
            ${!app.appointment ? `<button type="button" onclick="showAppointmentForm(${id})" class="mt-2 px-5 py-2 text-sm font-bold text-google-blue hover:bg-google-blue/10 border border-google-blue/30 rounded-full transition-colors active:scale-95">Set Appointment</button>` : ''}
        </div>

        ${actionForm}
    `;

        document.getElementById('modalTitle').textContent = `${app.first_name} ${app.last_name}'s Application`;
        document.getElementById('applicationModal').classList.remove('hidden');
        document.getElementById('applicationModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('applicationModal').classList.add('hidden');
        document.getElementById('applicationModal').classList.remove('flex');
    }

    function showRejectForm(id) {
        document.getElementById('rejectForm' + id).classList.toggle('hidden');
        document.getElementById('revisionForm' + id).classList.add('hidden');
    }

    function showRevisionForm(id) {
        document.getElementById('revisionForm' + id).classList.toggle('hidden');
        document.getElementById('rejectForm' + id).classList.add('hidden');
    }

    document.getElementById('applicationModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });

    // --- Confirm Modal ---
    function showApproveConfirm(id) {
        pendingApproveId = id;
        document.getElementById('confirmTitle').textContent = 'Approve Application';
        document.getElementById('confirmMessage').textContent = 'A student account will be created with default password: ncst123. An email notification will be sent. Proceed?';
        document.getElementById('confirmOkBtn').className = 'px-6 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700';
        document.getElementById('confirmOkBtn').textContent = 'Confirm Approve';
        document.getElementById('confirmOkBtn').onclick = function () { doApprove(); };
        document.getElementById('applicationModal').classList.add('hidden');
        document.getElementById('applicationModal').classList.remove('flex');
        document.getElementById('confirmModal').classList.remove('hidden');
        document.getElementById('confirmModal').classList.add('flex');
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.add('hidden');
        document.getElementById('confirmModal').classList.remove('flex');
        document.getElementById('applicationModal').classList.remove('hidden');
        document.getElementById('applicationModal').classList.add('flex');
        pendingApproveId = null;
    }

    document.getElementById('confirmModal').addEventListener('click', function (e) {
        if (e.target === this) closeConfirmModal();
    });

    function doApprove() {
        if (!pendingApproveId) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="csrf_token" value="' + csrfToken + '">' +
            '<input type="hidden" name="update_status" value="1">' +
            '<input type="hidden" name="applicant_id" value="' + pendingApproveId + '">' +
            '<input type="hidden" name="action" value="approve">';
        document.body.appendChild(form);
        form.submit();
    }

    function submitReject(id) {
        const reason = document.querySelector('#rejectForm' + id + ' textarea').value;
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="csrf_token" value="' + csrfToken + '">' +
            '<input type="hidden" name="update_status" value="1">' +
            '<input type="hidden" name="applicant_id" value="' + id + '">' +
            '<input type="hidden" name="action" value="reject">' +
            '<input type="hidden" name="rejection_reason" value="' + reason.replace(/"/g, '&quot;') + '">';
        document.body.appendChild(form);
        form.submit();
    }

    function submitRevision(id) {
        const reason = document.querySelector('#revisionForm' + id + ' textarea').value;
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="csrf_token" value="' + csrfToken + '">' +
            '<input type="hidden" name="update_status" value="1">' +
            '<input type="hidden" name="applicant_id" value="' + id + '">' +
            '<input type="hidden" name="action" value="revision">' +
            '<input type="hidden" name="rejection_reason" value="' + reason.replace(/"/g, '&quot;') + '">';
        document.body.appendChild(form);
        form.submit();
    }

    // --- AJAX doc status update ---
    document.getElementById('applicationModal').addEventListener('submit', function (e) {
        const form = e.target.closest('.js-doc-status-form');
        if (!form) return;
        e.preventDefault();

        const formData = new FormData(form);

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                const docId = formData.get('doc_id');
                const status = formData.get('doc_status');
                const notes = formData.get('doc_notes');

                // Update badge
                const badge = document.getElementById('docBadge_' + docId);
                if (badge) badge.innerHTML = statusBadge(status);

                // Update notes
                const noteEl = document.getElementById('docNote_' + docId);
                if (noteEl) noteEl.textContent = notes || '';

                // Reload page if auto-approved
                if (data.msg && data.msg.includes('approved')) {
                    location.reload();
                }
            })
            .catch(() => {
                location.reload();
            });
    });

    // --- Appointment Form ---
    function showAppointmentForm(id) {
        document.getElementById('appointmentForm_' + id).classList.remove('hidden');
        document.getElementById('appointmentForm_' + id).classList.add('flex', 'flex-col');
        document.getElementById('appointmentDisplay_' + id).classList.add('hidden');
        // Hide the "Set Appointment" button
        const btn = document.querySelector('#appointmentSection > button');
        if (btn) btn.classList.add('hidden');
    }

    function hideAppointmentForm(id) {
        document.getElementById('appointmentForm_' + id).classList.add('hidden');
        document.getElementById('appointmentForm_' + id).classList.remove('flex', 'flex-col');
        document.getElementById('appointmentDisplay_' + id).classList.remove('hidden');
        const btn = document.querySelector('#appointmentSection > button');
        if (btn) btn.classList.remove('hidden');
    }

    function saveAppointment(id) {
        const dt = document.getElementById('appointmentDate_' + id).value;
        if (!dt) { alert('Please select a date and time.'); return; }
        const label = document.getElementById('appointmentLabel_' + id).value;
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('save_appointment', '1');
        formData.append('applicant_id', id);
        formData.append('appointment_label', label);
        formData.append('appointment', dt);

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                const dateStr = new Date(dt).toLocaleString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                document.getElementById('appointmentDisplay_' + id).innerHTML = `
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl mb-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <div>
                                <p class="font-medium text-blue-800">${label}</p>
                                <p class="text-sm text-blue-600 mt-0.5">${dateStr}</p>
                                <button type="button" onclick="showAppointmentForm(${id})" class="mt-2 text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">Edit Appointment</button>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('appointmentDisplay_' + id).classList.remove('hidden');
                document.getElementById('appointmentForm_' + id).classList.add('hidden');
                document.getElementById('appointmentForm_' + id).classList.remove('flex', 'flex-col');
                applicants[id].appointment = dt;
                applicants[id].appointment_label = label;
            } else {
                alert(data.msg || 'Failed to save appointment.');
            }
        })
        .catch(err => { alert('Error: ' + (err.message || 'Request failed.')); });
    }

    // --- Doc Viewer ---
    function openDocViewer(filePath, fileName) {
        closeModal();
        const content = document.getElementById('docViewerContent');
        document.getElementById('docViewerTitle').textContent = fileName || 'Document';

        const isImg = filePath.match(/\.(jpg|jpeg|png|gif|webp)$/i);
        if (isImg) {
            content.innerHTML = '<img src="' + filePath + '" alt="' + (fileName || 'Document') + '" class="max-h-[70vh] w-auto rounded-lg">';
        } else {
            content.innerHTML = '<div class="text-center p-8"><a href="' + filePath + '" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> View File: ' + (fileName || 'Document') + '</a><p class="text-sm text-gray-500 mt-4">Preview not available for this file type.</p></div>';
        }

        document.getElementById('docViewerModal').classList.remove('hidden');
        document.getElementById('docViewerModal').classList.add('flex');
    }

    function closeDocViewer() {
        document.getElementById('docViewerModal').classList.add('hidden');
        document.getElementById('docViewerModal').classList.remove('flex');
    }

    document.getElementById('docViewerModal').addEventListener('click', function (e) {
        if (e.target === this) closeDocViewer();
    });
</script>

</main>
</div>
</body>

</html>