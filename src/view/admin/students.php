<?php
$page_title = 'Students';
require_once __DIR__ . '/sidebar.php';

$errors = [];
$success = '';

// Handle document status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doc_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $doc_id = (int) ($_POST['doc_id'] ?? 0);
        $status = $_POST['doc_status'] ?? '';
        $notes = trim($_POST['doc_notes'] ?? '');
        if ($doc_id && in_array($status, ['pending', 'submitted', 'approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE applicant_documents SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $doc_id]);
            $success = 'Document status updated.';
        }
    }
}

// Handle student status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $user_id = (int) ($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($user_id && in_array($status, ['active', 'inactive'])) {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$status, $user_id]);
            $success = "Student status updated to {$status}.";
        }
    }
}

$filter = $_GET['filter'] ?? '';

$where = "WHERE u.role = 'student'";
if ($filter === 'active')
    $where .= " AND u.status = 'active' AND u.deleted_at IS NULL";
elseif ($filter === 'inactive')
    $where .= " AND u.status = 'inactive' AND u.deleted_at IS NULL";
elseif ($filter === 'deleted')
    $where .= " AND u.deleted_at IS NOT NULL";

try {
    $stmt = $pdo->query("
        SELECT u.*, s.preferred_course, s.academic_year, s.enrollment_status,
               a.id as applicant_id
        FROM users u
        LEFT JOIN students s ON s.user_id = u.id
        LEFT JOIN applicants a ON a.user_id = u.id
        $where
        ORDER BY u.created_at DESC
    ");
    $students = $stmt->fetchAll();

    // Get document counts per applicant
    $applicant_ids = array_filter(array_column($students, 'applicant_id'));
    $doc_counts = [];
    $all_docs = [];
    if (!empty($applicant_ids)) {
        $placeholders = implode(',', array_fill(0, count($applicant_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT applicant_id,
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as verified
            FROM applicant_documents
            WHERE applicant_id IN ($placeholders)
            GROUP BY applicant_id
        ");
        $stmt->execute(array_values($applicant_ids));
        foreach ($stmt->fetchAll() as $row) {
            $doc_counts[$row['applicant_id']] = $row;
        }

        // Fetch all documents for modal display
        $stmt = $pdo->prepare("
            SELECT ad.*, a.first_name, a.last_name
            FROM applicant_documents ad
            JOIN applicants a ON a.id = ad.applicant_id
            WHERE ad.applicant_id IN ($placeholders)
            ORDER BY ad.applicant_id, ad.created_at DESC
        ");
        $stmt->execute(array_values($applicant_ids));
        foreach ($stmt->fetchAll() as $row) {
            $row['file_path'] = BASE_URL . $row['file_path'];
            $all_docs[$row['applicant_id']][] = $row;
        }
    }
} catch (Exception $e) {
    $students = [];
    $doc_counts = [];
    $all_docs = [];
}
?>
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Students</h1>
    <p class="text-gray-500 mt-1">View, manage students and verify their documents</p>
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

<div class="flex gap-2 mb-6">
    <a href="?"
        class="px-4 py-2 rounded-lg text-sm font-medium <?= !$filter ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">All</a>
    <a href="?filter=active"
        class="px-4 py-2 rounded-lg text-sm font-medium <?= $filter === 'active' ? 'bg-green-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">Active</a>
    <a href="?filter=inactive"
        class="px-4 py-2 rounded-lg text-sm font-medium <?= $filter === 'inactive' ? 'bg-red-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">Inactive</a>
    <a href="?filter=deleted"
        class="px-4 py-2 rounded-lg text-sm font-medium <?= $filter === 'deleted' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">Deleted</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($students)): ?>
        <div class="p-8 text-center text-gray-500">
            <p>No students found.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documents
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($students as $s):
                        $dc = $doc_counts[$s['applicant_id']] ?? null;
                        $total_docs = $dc ? (int) $dc['total'] : 0;
                        $verified_docs = $dc ? (int) $dc['verified'] : 0;
                        $deleted_at = $s['deleted_at'] ?? null;
                        $delete_badge = '';
                        if ($deleted_at) {
                            $del_date = new DateTime($deleted_at);
                            $now = new DateTime();
                            $diff = $now->diff($del_date);
                            $days_left = 30 - $diff->days;
                            if ($days_left > 0) {
                                $delete_badge = '<span class="ml-1 p-2 rounded-full text-[10px] font-bold bg-red-200 text-red-800">Delete in ' . $days_left . 'd</span>';
                            } else {
                                $delete_badge = '<span class="ml-1 px-1.5! py-0.5! rounded! text-[10px] font-bold bg-red-200 text-red-800">Pending permanent delete</span>';
                            }
                        }
                        ?>
                        <tr class="hover:bg-gray-50 <?= $deleted_at ? 'opacity-60' : '' ?>">
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900">
                                    <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>         <?= $delete_badge ?>
                                </div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($s['email']) ?></div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                <?= htmlspecialchars($s['contact_number']) ?>
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-medium"><?= htmlspecialchars($s['preferred_course'] ?? '-') ?></span>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($s['academic_year'] ?? '') ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <?php if ($total_docs > 0): ?>
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-medium <?= $verified_docs === $total_docs ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                                        <?= $verified_docs ?>/<?= $total_docs ?> verified
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">No documents</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-medium <?= $s['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= ucfirst($s['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($s['created_at'])) ?>
                            </td>
                            <td class="px-4 py-4 text-right space-x-3">
                                <a href="<?= url('/src/view/admin/managestudent.php?id=' . $s['id']) ?>"
                                    class="text-blue-600 hover:underline text-sm">Edit</a>
                                <button onclick="viewStudent(<?= $s['id'] ?>, <?= $s['applicant_id'] ?: 'null' ?>)"
                                    class="text-blue-600 hover:underline text-sm">View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<!-- Student Detail Modal -->
<div id="studentModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto m-4">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-bold" id="modalTitle">Student Details</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-6" id="modalContent"></div>
    </div>
</div>

<script>
    const students = <?= json_encode(array_column($students, null, 'id')) ?>;
    const allDocs = <?= json_encode($all_docs) ?>;

    function viewStudent(id, applicantId) {
        const s = students[id];
        if (!s) return;

        let docsHtml = '<p class="text-sm text-gray-500">No applicant record found.</p>';

        if (applicantId && allDocs[applicantId]) {
            const docs = allDocs[applicantId];
            const statusMap = {
                'pending': { cls: 'bg-gray-100 text-gray-800', label: 'Pending' },
                'submitted': { cls: 'bg-blue-100 text-blue-800', label: 'Submitted' },
                'approved': { cls: 'bg-green-100 text-green-800', label: 'Approved' },
                'rejected': { cls: 'bg-red-100 text-red-800', label: 'Rejected' },
            };
            docsHtml = `
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 px-2 text-xs font-medium text-gray-500 uppercase">Document</th>
                                <th class="text-left py-2 px-2 text-xs font-medium text-gray-500 uppercase">File</th>
                                <th class="text-left py-2 px-2 text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="text-right py-2 px-2 text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${docs.map(d => {
                const st = statusMap[d.status] || statusMap['pending'];
                return `
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 px-2 capitalize">${d.document_type.replace(/_/g, ' ')}</td>
                                    <td class="py-2 px-2">
                                        <a href="${d.file_path}" target="_blank" class="text-blue-600 hover:underline">View File</a>
                                    </td>
                                    <td class="py-2 px-2">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium ${st.cls}">${st.label}</span>
                                        ${d.notes ? `<div class="text-[10px] text-gray-500 mt-0.5">${d.notes}</div>` : ''}
                                    </td>
                                    <td class="py-2 px-2 text-right">
                                        <button onclick="event.stopPropagation(); editStudentDoc('${d.id}', '${d.status || 'pending'}', '${(d.notes || '').replace(/'/g, "\\'")}', '${d.file_path}', '${(d.file_name || '').replace(/'/g, "\\'")}')" class="px-3 py-1 rounded-lg text-xs font-medium bg-blue-600 text-white hover:bg-blue-700">
                                            Edit
                                        </button>
                                    </td>
                                </tr>`;
            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else if (applicantId) {
            docsHtml = '<p class="text-sm text-gray-500">No documents uploaded.</p>';
        }

        document.getElementById('modalContent').innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="font-bold text-gray-900 mb-3">Personal Information</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex"><dt class="w-32 text-gray-500">Name:</dt><dd class="font-medium">${s.first_name} ${s.last_name}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Email:</dt><dd>${s.email}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Contact:</dt><dd>${s.contact_number || '-'}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Gender:</dt><dd>${s.gender || '-'}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Status:</dt><dd><span class="px-2 py-0.5 rounded-full text-xs font-medium ${s.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">${s.status}</span></dd></div>
                </dl>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 mb-3">Academic Info</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex"><dt class="w-32 text-gray-500">Course:</dt><dd class="font-medium">${s.preferred_course || '-'}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Year:</dt><dd>${s.academic_year || '-'}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Enrollment:</dt><dd>${s.enrollment_status || '-'}</dd></div>
                    <div class="flex"><dt class="w-32 text-gray-500">Joined:</dt><dd>${new Date(s.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</dd></div>
                </dl>
                <div class="mt-4">
                    <form method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="user_id" value="${s.id}">
                        ${s.status === 'active'
                ? `<button type="submit" name="status" value="inactive" class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Deactivate</button>`
                : `<button type="submit" name="status" value="active" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">Activate</button>`
            }
                    </form>
                </div>
            </div>
        </div>

        <div class="border-t pt-6">
            <h3 class="font-bold text-gray-900 mb-4">Documents</h3>
            ${docsHtml}
        </div>
        `;

        document.getElementById('modalTitle').textContent = `${s.first_name} ${s.last_name}`;
        document.getElementById('studentModal').classList.remove('hidden');
        document.getElementById('studentModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('studentModal').classList.add('hidden');
        document.getElementById('studentModal').classList.remove('flex');
    }

    document.getElementById('studentModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });

    // Document status edit functions
    function editStudentDoc(id, status, notes, filePath, fileName) {
        // Auto-hide the Student Detail Modal since the Edit Document Status modal will show
        closeModal();

        document.getElementById('stdDocId').value = id;
        document.getElementById('stdDocStatus').value = status;
        document.getElementById('stdDocNotes').value = notes;

        // Show file preview
        const preview = document.getElementById('stdDocPreview');
        if (filePath) {
            const isImg = filePath.match(/\.(jpg|jpeg|png|gif|webp)$/i);
            if (isImg) {
                preview.innerHTML = `<a href="${filePath}" target="_blank"><img src="${filePath}" alt="${fileName || 'Document'}" class="max-h-48 mx-auto rounded-lg border"></a>`;
            } else {
                preview.innerHTML = `<a href="${filePath}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg text-sm hover:bg-blue-100"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> View File: ${fileName || 'Document'}</a>`;
            }
        } else {
            preview.innerHTML = '';
        }

        document.getElementById('stdDocModal').classList.remove('hidden');
        document.getElementById('stdDocModal').classList.add('flex');
    }

    function setStdDocNote(note) {
        document.getElementById('stdDocNotes').value = note;
    }

    function closeStdDocModal() {
        document.getElementById('stdDocModal').classList.add('hidden');
        document.getElementById('stdDocModal').classList.remove('flex');
    }
</script>

<!-- Edit Document Modal -->
<div id="stdDocModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[70] p-5!"
    onclick="if(event.target===this)closeStdDocModal()">
    <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto m-4">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
            <h2 class="text-lg font-bold">Edit Document Status</h2>
            <button type="button" onclick="closeStdDocModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div id="stdDocPreview" class="p-4 bg-gray-50 flex items-center justify-center min-h-[80px]"></div>
        <form method="POST" class="px-6 pb-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="update_doc_status" value="1">
            <input type="hidden" name="doc_id" id="stdDocId">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="doc_status" id="stdDocStatus"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="pending">Pending</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quick Notes</label>
                <div class="flex flex-wrap gap-1 mb-2">
                    <button type="button" onclick="setStdDocNote('For OSA review and verification of documents.')"
                        class="text-[11px] px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Need
                        verification — refer to OSA</button>
                    <button type="button"
                        onclick="setStdDocNote('Document is unclear. Please resubmit a clearer copy.')"
                        class="text-[11px] px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Need resubmit
                        — unclear copy</button>
                    <button type="button"
                        onclick="setStdDocNote('Document is incomplete. Please resubmit the complete version.')"
                        class="text-[11px] px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Need resubmit
                        — incomplete</button>
                    <button type="button" onclick="setStdDocNote('Document is currently under review.')"
                        class="text-[11px] px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Under
                        review</button>
                    <button type="button" onclick="setStdDocNote('Document has been verified and approved.')"
                        class="text-[11px] px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Approved — all
                        clear</button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="doc_notes" id="stdDocNotes" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    placeholder="Add notes about this document..."></textarea>
            </div>

            <div class="flex gap-2 justify-end pb-5!">
                <button type="button" onclick="closeStdDocModal()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Update Status</button>
            </div>
            <div class="flex gap-2 justify-end pb-5!"></div>
        </form>
    </div>
</div>

</main>
</div>
</body>

</html>