<?php
require_once __DIR__ . '/../../../../src/config/bootstrap.php';
require_staff();

$errors = [];
$success = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $applicant_id = (int)($_POST['applicant_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        
        if ($applicant_id && in_array($action, ['approve', 'reject', 'revision'])) {
            try {
                $pdo->beginTransaction();
                
                if ($action === 'approve') {
                    // Get applicant data
                    $stmt = $pdo->prepare("SELECT * FROM applicants WHERE id = ? AND status = 'pending'");
                    $stmt->execute([$applicant_id]);
                    $applicant = $stmt->fetch();
                    
                    if ($applicant) {
                        // Default password is "ncst123"
                        $hashed_password = password_hash('ncst123', PASSWORD_DEFAULT);
                        
                        // Create user account
                        $stmt = $pdo->prepare("
                            INSERT INTO users (
                                first_name, middle_name, last_name, suffix, birthday, gender,
                                civil_status, nationality, religion, birth_place,
                                email, contact_number, home_address, province, city, barangay, zip_code,
                                password, role, status, created_by
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $applicant['first_name'], $applicant['middle_name'], $applicant['last_name'], 
                            $applicant['suffix'], $applicant['birthday'], $applicant['gender'],
                            $applicant['civil_status'], $applicant['nationality'], $applicant['religion'], 
                            $applicant['birth_place'], $applicant['email'], $applicant['contact_number'],
                            $applicant['home_address'], $applicant['province'], $applicant['city'], 
                            $applicant['barangay'], $applicant['zip_code'],
                            $hashed_password, 'student', 'active', $_SESSION['user_id']
                        ]);
                        
                        $user_id = $pdo->lastInsertId();
                        
                        // Create student record
                        $stmt = $pdo->prepare("
                            INSERT INTO students (
                                user_id, father_name, mother_name, guardian_name, guardian_contact, guardian_relationship,
                                education_type, highschool_name, highschool_address, shs_strand, year_graduated, lrn,
                                previous_college, previous_course, last_year_level,
                                preferred_course, second_course, semester, academic_year
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $user_id, $applicant['father_name'], $applicant['mother_name'],
                            $applicant['guardian_name'], $applicant['guardian_contact'], $applicant['guardian_relationship'],
                            $applicant['education_type'], $applicant['highschool_name'], $applicant['highschool_address'],
                            $applicant['shs_strand'], $applicant['year_graduated'], $applicant['lrn'],
                            $applicant['previous_college'], $applicant['previous_course'], $applicant['last_year_level'],
                            $applicant['preferred_course'], $applicant['second_course'], $applicant['semester'], 
                            $applicant['academic_year']
                        ]);
                        
                        // Move documents to student folder
                        $old_doc_path = __DIR__ . '/../../../../../uploads/applicants/' . $applicant_id;
                        $new_doc_path = __DIR__ . '/../../../../../uploads/students/' . $user_id;
                        
                        if (is_dir($old_doc_path)) {
                            mkdir(dirname($new_doc_path), 0755, true);
                            rename($old_doc_path, $new_doc_path);
                        }
                        
                        // Update applicant status
                        $stmt = $pdo->prepare("
                            UPDATE applicants SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), user_id = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$_SESSION['user_id'], $user_id, $applicant_id]);
                        
                        // TODO: Send email notification to student using resend.com
                        
                        $success = "Application approved! Student account created with default password: ncst123";
                    }
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE applicants SET status = ?, rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$action === 'revision' ? 'revision' : 'rejected', $rejection_reason, $_SESSION['user_id'], $applicant_id]);
                    $success = "Application marked as " . ($action === 'revision' ? 'needs revision' : 'rejected') . ".";
                }
                
                $pdo->commit();
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get filter
$status_filter = $_GET['status'] ?? 'pending';

// Get applicants
$query = "SELECT a.*, u.first_name as reviewed_by_name 
          FROM applicants a 
          LEFT JOIN users u ON a.reviewed_by = u.id 
          WHERE 1=1";

if ($status_filter !== 'all') {
    $query .= " AND a.status = '" . addslashes($status_filter) . "'";
}
$query .= " ORDER BY a.created_at DESC";

$applicants = $pdo->query($query)->fetchAll();

// Get counts for badges
$counts = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'revision' => 0,
    'all' => count($applicants)
];

foreach ($applicants as $a) {
    if (isset($counts[$a['status']])) {
        $counts[$a['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Management - NCST Registrar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <style>
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-revision { background: #e0e7ff; color: #3730a3; }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/../../../admin/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Application Management</h1>
                <p class="text-gray-600 mt-1">Review and manage student enrollment applications</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="<?= url('/src/view/staff/dashboard.php') ?>" class="text-google-blue hover:underline">← Back to Dashboard</a>
            </div>
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
            <a href="?status=pending" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'pending' ? 'bg-google-blue text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                Pending <?php if ($counts['pending'] > 0): ?><span class="ml-1 bg-yellow-400 text-yellow-900 px-2 py-0.5 rounded-full text-xs"><?= $counts['pending'] ?></span><?php endif; ?>
            </a>
            <a href="?status=approved" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'approved' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                Approved <?php if ($counts['approved'] > 0): ?><span class="ml-1 bg-green-400 text-green-900 px-2 py-0.5 rounded-full text-xs"><?= $counts['approved'] ?></span><?php endif; ?>
            </a>
            <a href="?status=rejected" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'rejected' ? 'bg-red-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                Rejected <?php if ($counts['rejected'] > 0): ?><span class="ml-1 bg-red-400 text-red-900 px-2 py-0.5 rounded-full text-xs"><?= $counts['rejected'] ?></span><?php endif; ?>
            </a>
            <a href="?status=revision" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'revision' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                Needs Revision <?php if ($counts['revision'] > 0): ?><span class="ml-1 bg-indigo-400 text-indigo-900 px-2 py-0.5 rounded-full text-xs"><?= $counts['revision'] ?></span><?php endif; ?>
            </a>
            <a href="?status=all" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'all' ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                All (<?= $counts['all'] ?>)
            </a>
        </div>

        <!-- Applications List -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <?php if (empty($applicants)): ?>
                <div class="p-8 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>No applications found.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Education</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($applicants as $app): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($app['first_name'] . ' ' . $app['middle_name'] . ' ' . $app['last_name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($app['email']) ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium"><?= htmlspecialchars($app['preferred_course']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($app['academic_year'] . ' - ' . $app['semester']) ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="capitalize"><?= htmlspecialchars($app['education_type']) ?></span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="status-badge status-<?= $app['status'] ?>">
                                            <?= ucfirst($app['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500">
                                        <?= date('M d, Y', strtotime($app['created_at'])) ?>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <button onclick="viewApplication(<?= $app['id'] ?>)" class="text-google-blue hover:underline text-sm">View Details</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
            <div class="p-6" id="modalContent">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
        const applicants = <?= json_encode(array_column($applicants, null, 'id')) ?>;
        
        function viewApplication(id) {
            const app = applicants[id];
            if (!app) return;
            
            let documentsHtml = '';
            // Documents will be loaded via AJAX in production
            
            let documentsSection = `
                <div class="mb-6">
                    <h3 class="font-bold text-gray-900 mb-3">Uploaded Documents</h3>
                    <div class="text-sm text-gray-500">Documents will be shown here when uploaded.</div>
                </div>
            `;
            
            if (app.status === 'pending') {
                documentsSection = `
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="applicant_id" value="${id}">
                        <input type="hidden" name="update_status" value="1">
                        
                        <div class="mb-6">
                            <h3 class="font-bold text-gray-900 mb-3">Uploaded Documents</h3>
                            <div class="text-sm text-gray-500">Documents will be shown here when uploaded.</div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <h3 class="font-bold text-gray-900 mb-3">Take Action</h3>
                            
                            <div class="flex flex-wrap gap-3">
                                <button type="submit" name="action" value="approve" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700">
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
                                <textarea name="rejection_reason" rows="3" class="w-full px-3 py-2 border rounded-lg" placeholder="Enter reason for rejection..."></textarea>
                                <button type="submit" name="action" value="reject" class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Confirm Rejection</button>
                            </div>
                            
                            <div id="revisionForm${id}" class="hidden mt-4 p-4 bg-indigo-50 rounded-lg">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Required Revisions</label>
                                <textarea name="rejection_reason" rows="3" class="w-full px-3 py-2 border rounded-lg" placeholder="Enter what needs to be revised..."></textarea>
                                <button type="submit" name="action" value="revision" class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Request Revision</button>
                            </div>
                        </div>
                    </form>
                `;
            }
            
            document.getElementById('modalContent').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3">Personal Information</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex"><dt class="w-32 text-gray-500">Name:</dt><dd class="font-medium">${app.first_name} ${app.middle_name || ''} ${app.last_name} ${app.suffix || ''}</dd></div>
                            <div class="flex"><dt class="w-32 text-gray-500">Birthday:</dt><dd>${app.birthday}</dd></div>
                            <div class="flex"><dt class="w-32 text-gray-500">Gender:</dt><dd>${app.gender}</dd></div>
                            <div class="flex"><dt class="w-32 text-gray-500">Civil Status:</dt><dd>${app.civil_status}</dd></div>
                            <div class="flex"><dt class="w-32 text-gray-500">Nationality:</dt><dd>${app.nationality}</dd></div>
                            <div class="flex"><dt class="w-32 text-gray-500">Birth Place:</dt><dd>${app.birth_place || '-'}</dd></div>
                        </dl>
                    </div>
                    
                    <!-- Contact Information -->
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3">Contact Information</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex"><dt class="w-32 text-gray-500">Email:</dt><dd>${app.email}</dd></div>
                            <div class="flex"><dt class="w-32 text-gray-500">Phone:</dt><dd>${app.contact_number}</dd></div>
                            <div class="flex"><dt class="w-32 text-gray-500">Address:</dt><dd>${app.home_address || '-'}, ${app.barangay || ''}, ${app.city || ''}, ${app.province || ''}</dd></div>
                        </dl>
                    </div>
                    
                    <!-- Parent/Guardian -->
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3">Parent / Guardian</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex"><dt class="w-32 text-gray-500">Father:</dt><dd>${app.father_name || '-'}</dd></div>
                            <div class="flex"><dt class="w-32 text-gray-500">Mother:</dt><dd>${app.mother_name || '-'}</dd></div>
                            <div class="flex"><dt class="w-32 text-gray-500">Guardian:</dt><dd>${app.guardian_name || '-'}</dd></div>
                            <div class="flex"><dt class="w-32 text-gray-500">Contact:</dt><dd>${app.guardian_contact || '-'}</dd></div>
                        </dl>
                    </div>
                    
                    <!-- Educational Background -->
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3">Educational Background</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex"><dt class="w-32 text-gray-500">Type:</dt><dd class="capitalize">${app.education_type}</dd></div>
                            ${app.education_type === 'freshman' ? `
                                <div class="flex"><dt class="w-32 text-gray-500">School:</dt><dd>${app.highschool_name || '-'}</dd></div>
                                <div class="flex"><dt class="w-32 text-gray-500">Strand:</dt><dd>${app.shs_strand || '-'}</dd></div>
                                <div class="flex"><dt class="w-32 text-gray-500">LRN:</dt><dd>${app.lrn || '-'}</dd></div>
                            ` : `
                                <div class="flex"><dt class="w-32 text-gray-500">Previous:</dt><dd>${app.previous_college || '-'}</dd></div>
                                <div class="flex"><dt class="w-32 text-gray-500">Course:</dt><dd>${app.previous_course || '-'}</dd></div>
                            `}
                        </dl>
                    </div>
                    
                    <!-- Course Selection -->
                    <div class="md:col-span-2">
                        <h3 class="font-bold text-gray-900 mb-3">Course Selection</h3>
                        <dl class="flex flex-wrap gap-6 text-sm">
                            <div><span class="text-gray-500">First Choice:</span> <span class="font-medium">${app.preferred_course}</span></div>
                            <div><span class="text-gray-500">Second Choice:</span> <span class="font-medium">${app.second_course || '-'}</span></div>
                            <div><span class="text-gray-500">Academic Year:</span> <span class="font-medium">${app.academic_year}</span></div>
                            <div><span class="text-gray-500">Semester:</span> <span class="font-medium">${app.semester}</span></div>
                        </dl>
                    </div>
                </div>
                
                ${documentsSection}
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
        
        // Close modal on outside click
        document.getElementById('applicationModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>

</html>