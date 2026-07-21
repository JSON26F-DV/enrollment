<?php
$page_title = 'Manage Student';
require_once __DIR__ . '/sidebar.php';

$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = $edit_id > 0;

$errors = [];
$success = '';
$old = [];

$user = [];
$student = [];
$applicant_id = null;
$documents = [];

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

if ($is_edit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role IN ('shs','college')");
        $stmt->execute([$edit_id]);
        $user = $stmt->fetch();
        if (!$user) {
            header("Location: " . url('/src/view/admin/students.php'));
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
        $stmt->execute([$edit_id]);
        $student = $stmt->fetch() ?: [];

        $stmt = $pdo->prepare("SELECT id FROM applicants WHERE user_id = ?");
        $stmt->execute([$edit_id]);
        $app = $stmt->fetch();
        if ($app) {
            $applicant_id = $app['id'];
            $stmt = $pdo->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ? ORDER BY created_at DESC");
            $stmt->execute([$applicant_id]);
            $documents = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $old = $_POST;

        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $suffix = trim($_POST['suffix'] ?? '');
        $birthday = trim($_POST['birthday'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $civil_status = trim($_POST['civil_status'] ?? 'Single');
        $nationality = trim($_POST['nationality'] ?? 'Filipino');
        $religion = trim($_POST['religion'] ?? '');
        $birth_place = trim($_POST['birth_place'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $home_address = trim($_POST['home_address'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');
        $password = $_POST['password'] ?? '';
        $status = trim($_POST['status'] ?? 'active');

        $father_name = trim($_POST['father_name'] ?? '');
        $mother_name = trim($_POST['mother_name'] ?? '');
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $guardian_contact = trim($_POST['guardian_contact'] ?? '');
        $guardian_relationship = trim($_POST['guardian_relationship'] ?? '');
        $education_type = trim($_POST['education_type'] ?? 'freshman');
        $highschool_name = trim($_POST['highschool_name'] ?? '');
        $highschool_address = trim($_POST['highschool_address'] ?? '');
        $shs_strand = trim($_POST['shs_strand'] ?? '');
        $shs_track = trim($_POST['shs_track'] ?? '');
        $year_graduated = trim($_POST['year_graduated'] ?? '');
        $lrn = trim($_POST['lrn'] ?? '');
        $previous_college = trim($_POST['previous_college'] ?? '');
        $previous_course = trim($_POST['previous_course'] ?? '');
        $last_year_level = trim($_POST['last_year_level'] ?? '');
        $preferred_course = trim($_POST['preferred_course'] ?? '');
        $second_course = trim($_POST['second_course'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $enrollment_status = trim($_POST['enrollment_status'] ?? 'pending');

        // Validation
        if (empty($first_name)) $errors[] = 'First name is required.';
        if (empty($last_name)) $errors[] = 'Last name is required.';
        if (empty($email)) $errors[] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
        if (empty($birthday)) $errors[] = 'Birthday is required.';
        if (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other'])) $errors[] = 'Gender is required.';
        if (empty($contact_number)) $errors[] = 'Contact number is required.';
        if (!$is_edit && empty($password)) $errors[] = 'Password is required.';
        elseif (!empty($password) && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $edit_id ?: 0]);
        if ($stmt->fetch()) $errors[] = 'An account with this email already exists.';

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                if ($is_edit) {
                    if (!empty($password)) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, suffix=?, birthday=?, gender=?, civil_status=?, nationality=?, religion=?, birth_place=?, email=?, contact_number=?, home_address=?, province=?, city=?, barangay=?, zip_code=?, status=?, password=? WHERE id=?");
                        $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $birthday, $gender, $civil_status, $nationality, $religion, $birth_place, $email, $contact_number, $home_address, $province, $city, $barangay, $zip_code, $status, $hashed, $edit_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, suffix=?, birthday=?, gender=?, civil_status=?, nationality=?, religion=?, birth_place=?, email=?, contact_number=?, home_address=?, province=?, city=?, barangay=?, zip_code=?, status=? WHERE id=?");
                        $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $birthday, $gender, $civil_status, $nationality, $religion, $birth_place, $email, $contact_number, $home_address, $province, $city, $barangay, $zip_code, $status, $edit_id]);
                    }

                    // Upsert students table
                    if (!empty($student)) {
                        $stmt = $pdo->prepare("UPDATE students SET father_name=?, mother_name=?, guardian_name=?, guardian_contact=?, guardian_relationship=?, education_type=?, highschool_name=?, highschool_address=?, shs_strand=?, shs_track=?, year_graduated=?, lrn=?, previous_college=?, previous_course=?, last_year_level=?, preferred_course=?, second_course=?, semester=?, academic_year=?, enrollment_status=? WHERE user_id=?");
                        $stmt->execute([$father_name, $mother_name, $guardian_name, $guardian_contact, $guardian_relationship, $education_type, $highschool_name, $highschool_address, $shs_strand, $shs_track, $year_graduated, $lrn, $previous_college, $previous_course, $last_year_level, $preferred_course, $second_course, $semester, $academic_year, $enrollment_status, $edit_id]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO students (user_id, father_name, mother_name, guardian_name, guardian_contact, guardian_relationship, education_type, highschool_name, highschool_address, shs_strand, shs_track, year_graduated, lrn, previous_college, previous_course, last_year_level, preferred_course, second_course, semester, academic_year, enrollment_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $stmt->execute([$edit_id, $father_name, $mother_name, $guardian_name, $guardian_contact, $guardian_relationship, $education_type, $highschool_name, $highschool_address, $shs_strand, $shs_track, $year_graduated, $lrn, $previous_college, $previous_course, $last_year_level, $preferred_course, $second_course, $semester, $academic_year, $enrollment_status]);
                    }

                    $success = 'Student updated successfully!';
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, suffix, birthday, gender, civil_status, nationality, religion, birth_place, email, contact_number, home_address, province, city, barangay, zip_code, password, role, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'student', ?)");
                    $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $birthday, $gender, $civil_status, $nationality, $religion, $birth_place, $email, $contact_number, $home_address, $province, $city, $barangay, $zip_code, $hashed, $status]);
                    $edit_id = (int) $pdo->lastInsertId();

                    $stmt = $pdo->prepare("INSERT INTO students (user_id, father_name, mother_name, guardian_name, guardian_contact, guardian_relationship, education_type, highschool_name, highschool_address, shs_strand, shs_track, year_graduated, lrn, previous_college, previous_course, last_year_level, preferred_course, second_course, semester, academic_year, enrollment_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$edit_id, $father_name, $mother_name, $guardian_name, $guardian_contact, $guardian_relationship, $education_type, $highschool_name, $highschool_address, $shs_strand, $shs_track, $year_graduated, $lrn, $previous_college, $previous_course, $last_year_level, $preferred_course, $second_course, $semester, $academic_year, $enrollment_status]);

                    $success = 'Student created successfully!';
                }

                $pdo->commit();

                // Re-fetch data after save
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$edit_id]);
                $user = $stmt->fetch();

                $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
                $stmt->execute([$edit_id]);
                $student = $stmt->fetch() ?: [];

                $stmt = $pdo->prepare("SELECT id FROM applicants WHERE user_id = ?");
                $stmt->execute([$edit_id]);
                $app = $stmt->fetch();
                $applicant_id = $app ? $app['id'] : null;
                if ($applicant_id) {
                    $stmt = $pdo->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$applicant_id]);
                    $documents = $stmt->fetchAll();
                }

                $old = [];
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } elseif (!$is_edit && !$edit_id) {
        $errors[] = 'Save the student first before uploading documents.';
    } else {
        $document_type = $_POST['document_type'] ?? '';
        $allowed_types = ['psa_birth_certificate', 'form_138', 'good_moral', 'certificate_of_graduation', 'id_photo_2x2', 'valid_id', 'tor', 'honorable_dismissal', 'other'];

        if (!in_array($document_type, $allowed_types)) {
            $errors[] = 'Invalid document type.';
        } elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please select a file to upload.';
        } else {
            $file = $_FILES['document_file'];
            $max_size = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $max_size) {
                $errors[] = 'File is too large. Maximum size is 10MB.';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'];
                if (!in_array($ext, $allowed_exts)) {
                    $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowed_exts);
                } else {
                    try {
                        // Ensure applicant record exists
                        if (!$applicant_id) {
                            $stmt = $pdo->prepare("SELECT id FROM applicants WHERE user_id = ?");
                            $stmt->execute([$edit_id]);
                            $app = $stmt->fetch();
                            if ($app) {
                                $applicant_id = $app['id'];
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO applicants (first_name, middle_name, last_name, suffix, birthday, gender, civil_status, nationality, religion, birth_place, email, contact_number, home_address, province, city, barangay, zip_code, preferred_course, semester, academic_year, status, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'approved', ?)");
                                $stmt->execute([
                                    $user['first_name'], $user['middle_name'] ?? '', $user['last_name'], $user['suffix'] ?? '',
                                    $user['birthday'], $user['gender'], $user['civil_status'] ?? 'Single',
                                    $user['nationality'] ?? 'Filipino', $user['religion'] ?? '', $user['birth_place'] ?? '',
                                    $user['email'], $user['contact_number'], $user['home_address'] ?? '',
                                    $user['province'] ?? '', $user['city'] ?? '', $user['barangay'] ?? '', $user['zip_code'] ?? '',
                                    $student['preferred_course'] ?? '', $student['semester'] ?? '', $student['academic_year'] ?? '',
                                    $edit_id
                                ]);
                                $applicant_id = (int) $pdo->lastInsertId();
                            }
                        }

                        // Get upload path from database (falls back to default)
                        $upload_path = get_document_path('student_document') ?? '/assets/uploads/documents/students/';
                        $upload_dir = PROJECT_ROOT . $upload_path;
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                        $filename = $document_type . '_' . $edit_id . '_' . time() . '.' . $ext;
                        $dest = $upload_dir . $filename;

                        if (move_uploaded_file($file['tmp_name'], $dest)) {
                            $file_path = $upload_path . $filename;
                            $stmt = $pdo->prepare("INSERT INTO applicant_documents (applicant_id, document_type, file_name, file_path, file_size, mime_type) VALUES (?,?,?,?,?,?)");
                            $stmt->execute([$applicant_id, $document_type, $file['name'], $file_path, $file['size'], $file['type']]);
                            $success = 'Document uploaded successfully!';

                            // Refresh documents
                            $stmt = $pdo->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ? ORDER BY created_at DESC");
                            $stmt->execute([$applicant_id]);
                            $documents = $stmt->fetchAll();
                        } else {
                            $errors[] = 'Failed to upload file.';
                        }
                    } catch (PDOException $e) {
                        $errors[] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

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

            if ($applicant_id) {
                $stmt = $pdo->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ? ORDER BY created_at DESC");
                $stmt->execute([$applicant_id]);
                $documents = $stmt->fetchAll();
            }
        }
    }
}

// Handle document delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doc'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $doc_id = (int) ($_POST['doc_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("SELECT file_path FROM applicant_documents WHERE id = ?");
            $stmt->execute([$doc_id]);
            $doc = $stmt->fetch();
            if ($doc) {
                $file_path = PROJECT_ROOT . $doc['file_path'];
                if (file_exists($file_path)) unlink($file_path);
                $stmt = $pdo->prepare("DELETE FROM applicant_documents WHERE id = ?");
                $stmt->execute([$doc_id]);
                $success = 'Document deleted.';

                // Refresh
                if ($applicant_id) {
                    $stmt = $pdo->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$applicant_id]);
                    $documents = $stmt->fetchAll();
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle soft delete (requires admin password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soft_delete'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } elseif (!$is_edit) {
        $errors[] = 'Student not found.';
    } else {
        $admin_password = $_POST['admin_password'] ?? '';
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($admin_password, $admin['password'])) {
            $errors[] = 'Invalid admin password.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET deleted_at = CURDATE(), status = 'pending' WHERE id = ? AND role IN ('shs','college')");
            $stmt->execute([$edit_id]);
            $success = 'Student has been soft-deleted. Account will be permanently removed after 30 days.';
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$edit_id]);
            $user = $stmt->fetch();
        }
    }
}

// Handle restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_student'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } elseif (!$is_edit) {
        $errors[] = 'Student not found.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET deleted_at = NULL, status = 'active' WHERE id = ? AND role IN ('shs','college')");
        $stmt->execute([$edit_id]);
        $success = 'Student account restored successfully.';
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$edit_id]);
        $user = $stmt->fetch();
    }
}

// Fetch courses for dropdown
try {
    $courses = $pdo->query("SELECT code, name FROM courses WHERE is_active = 1 ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $courses = [];
}
?>
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900"><?= $is_edit ? 'Edit Student' : 'New Student' ?></h1>
        <p class="text-gray-500 mt-1"><?= $is_edit ? 'Update student information and documents' : 'Register a new student' ?></p>
    </div>
    <a href="<?= url('/src/view/admin/students.php') ?>" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 border rounded-lg hover:bg-gray-50 transition-colors">Back to Students</a>
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

<form method="POST" action="" enctype="multipart/form-data" class="space-y-8">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <!-- Personal Information -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Personal Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($old['first_name'] ?? $user['first_name'] ?? '') ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($old['middle_name'] ?? $user['middle_name'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($old['last_name'] ?? $user['last_name'] ?? '') ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Suffix</label>
                <input type="text" name="suffix" value="<?= htmlspecialchars($old['suffix'] ?? $user['suffix'] ?? '') ?>" placeholder="Jr., III, etc."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
                <input type="date" name="birthday" value="<?= htmlspecialchars($old['birthday'] ?? $user['birthday'] ?? '') ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select name="gender" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <option value="">Select</option>
                    <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                        <option value="<?= $g ?>" <?= (($old['gender'] ?? $user['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Civil Status</label>
                <select name="civil_status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <?php foreach (['Single', 'Married', 'Widowed', 'Separated', 'Annulled'] as $cs): ?>
                        <option value="<?= $cs ?>" <?= (($old['civil_status'] ?? $user['civil_status'] ?? 'Single') === $cs) ? 'selected' : '' ?>><?= $cs ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nationality</label>
                <input type="text" name="nationality" value="<?= htmlspecialchars($old['nationality'] ?? $user['nationality'] ?? 'Filipino') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                <input type="text" name="religion" value="<?= htmlspecialchars($old['religion'] ?? $user['religion'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div class="md:col-span-2 lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Birth Place</label>
                <input type="text" name="birth_place" value="<?= htmlspecialchars($old['birth_place'] ?? $user['birth_place'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Contact Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $user['email'] ?? '') ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                <input type="text" name="contact_number" value="<?= htmlspecialchars($old['contact_number'] ?? $user['contact_number'] ?? '') ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Home Address</label>
                <input type="text" name="home_address" value="<?= htmlspecialchars($old['home_address'] ?? $user['home_address'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                <input type="text" name="province" value="<?= htmlspecialchars($old['province'] ?? $user['province'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City / Municipality</label>
                <input type="text" name="city" value="<?= htmlspecialchars($old['city'] ?? $user['city'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                <input type="text" name="barangay" value="<?= htmlspecialchars($old['barangay'] ?? $user['barangay'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Zip Code</label>
                <input type="text" name="zip_code" value="<?= htmlspecialchars($old['zip_code'] ?? $user['zip_code'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
        </div>
    </div>

    <!-- Academic Information -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Academic Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Course</label>
                <select name="preferred_course"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <option value="">Select course</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= htmlspecialchars($c['code']) ?>" <?= (($old['preferred_course'] ?? $student['preferred_course'] ?? '') === $c['code']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Second Course</label>
                <select name="second_course"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <option value="">Select course</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= htmlspecialchars($c['code']) ?>" <?= (($old['second_course'] ?? $student['second_course'] ?? '') === $c['code']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                <input type="text" name="academic_year" value="<?= htmlspecialchars($old['academic_year'] ?? $student['academic_year'] ?? '') ?>" placeholder="e.g. 2026-2027"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                <input type="text" name="semester" value="<?= htmlspecialchars($old['semester'] ?? $student['semester'] ?? '') ?>" placeholder="e.g. 1st Semester"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Education Type</label>
                <select name="education_type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <?php foreach (['freshman', 'transferee', 'shiftee'] as $et): ?>
                        <option value="<?= $et ?>" <?= (($old['education_type'] ?? $student['education_type'] ?? 'freshman') === $et) ? 'selected' : '' ?>><?= ucfirst($et) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Enrollment Status</label>
                <select name="enrollment_status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <?php foreach (['pending', 'enrolled', 'not_enrolled', 'graduated', 'dropped'] as $es): ?>
                        <option value="<?= $es ?>" <?= (($old['enrollment_status'] ?? $student['enrollment_status'] ?? 'pending') === $es) ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $es)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lg:col-span-3">
                <hr class="my-2">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Previous Education</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">High School Name</label>
                <input type="text" name="highschool_name" value="<?= htmlspecialchars($old['highschool_name'] ?? $student['highschool_name'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">High School Address</label>
                <input type="text" name="highschool_address" value="<?= htmlspecialchars($old['highschool_address'] ?? $student['highschool_address'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SHS Strand</label>
                <input type="text" name="shs_strand" value="<?= htmlspecialchars($old['shs_strand'] ?? $student['shs_strand'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SHS Track</label>
                <input type="text" name="shs_track" value="<?= htmlspecialchars($old['shs_track'] ?? $student['shs_track'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year Graduated</label>
                <input type="text" name="year_graduated" value="<?= htmlspecialchars($old['year_graduated'] ?? $student['year_graduated'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">LRN (Learner Reference No.)</label>
                <input type="text" name="lrn" value="<?= htmlspecialchars($old['lrn'] ?? $student['lrn'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Previous College</label>
                <input type="text" name="previous_college" value="<?= htmlspecialchars($old['previous_college'] ?? $student['previous_college'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Previous Course</label>
                <input type="text" name="previous_course" value="<?= htmlspecialchars($old['previous_course'] ?? $student['previous_course'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Year Level</label>
                <input type="text" name="last_year_level" value="<?= htmlspecialchars($old['last_year_level'] ?? $student['last_year_level'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
        </div>
    </div>

    <!-- Parent / Guardian Information -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Parent / Guardian</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                <input type="text" name="father_name" value="<?= htmlspecialchars($old['father_name'] ?? $student['father_name'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Name</label>
                <input type="text" name="mother_name" value="<?= htmlspecialchars($old['mother_name'] ?? $student['mother_name'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Guardian's Name</label>
                <input type="text" name="guardian_name" value="<?= htmlspecialchars($old['guardian_name'] ?? $student['guardian_name'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Contact</label>
                <input type="text" name="guardian_contact" value="<?= htmlspecialchars($old['guardian_contact'] ?? $student['guardian_contact'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Relationship</label>
                <input type="text" name="guardian_relationship" value="<?= htmlspecialchars($old['guardian_relationship'] ?? $student['guardian_relationship'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
            </div>
        </div>
    </div>

    <!-- Account Settings -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Account</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <?php foreach (['active', 'inactive', 'pending'] as $st): ?>
                        <option value="<?= $st ?>" <?= (($old['status'] ?? $user['status'] ?? 'active') === $st) ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password <?= $is_edit ? '<span class="text-gray-400 font-normal">(leave blank to keep)</span>' : '' ?></label>
                <input type="password" name="password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none"
                    placeholder="<?= $is_edit ? 'Leave blank to keep current' : 'Min. 8 characters' ?>">
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" name="save"
            class="px-6 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
            <?= $is_edit ? 'Update Student' : 'Create Student' ?>
        </button>
        <a href="<?= url('/src/view/admin/students.php') ?>"
            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">Cancel</a>
        <?php if ($is_edit): ?>
            <?php if (!empty($user['deleted_at'])): ?>
                <form method="POST" class="inline" onsubmit="return confirm('Restore this student account?')">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="restore_student" value="1">
                    <button type="submit"
                        class="px-6 py-2.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">Restore Account</button>
                </form>
            <?php else: ?>
                <button type="button" onclick="openDeleteModal()"
                    class="px-6 py-2.5 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">Delete Student</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</form>

<!-- Documents Section (edit mode only) -->
<?php if ($is_edit): ?>
<div class="mt-8 bg-white rounded-xl shadow-sm p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4">Documents</h2>

    <!-- Upload form -->
    <form method="POST" action="" enctype="multipart/form-data" class="mb-6 p-4 bg-gray-50 rounded-lg">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="upload_doc" value="1">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                <select name="document_type" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                    <option value="">Select type</option>
                    <?php
                    $doc_types = [
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
                    foreach ($doc_types as $val => $label): ?>
                        <option value="<?= $val ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">File</label>
                <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <div>
                <button type="submit"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">Upload</button>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Allowed: PDF, JPG, PNG, GIF, DOC, DOCX (max 10MB)</p>
    </form>

    <!-- Document list -->
    <?php if (empty($documents)): ?>
        <p class="text-sm text-gray-500 text-center py-4">No documents uploaded yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2 px-2 text-xs font-medium text-gray-500 uppercase">Document Type</th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-gray-500 uppercase">File Name</th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-gray-500 uppercase">Size</th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-gray-500 uppercase">Notes</th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $d):
                        $status = $d['status'] ?? 'pending';
                        $status_class = match ($status) {
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'submitted' => 'bg-blue-100 text-blue-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-2 capitalize"><?= htmlspecialchars($doc_type_labels[$d['document_type']] ?? str_replace('_', ' ', $d['document_type'])) ?></td>
                            <td class="py-3 px-2">
                                <a href="<?= htmlspecialchars(url($d['file_path'])) ?>" target="_blank" class="text-blue-600 hover:underline"><?= htmlspecialchars($d['file_name']) ?></a>
                            </td>
                            <td class="py-3 px-2 text-gray-500">
                                <?php
                                $size = (int) $d['file_size'];
                                echo $size > 1048576 ? round($size / 1048576, 1) . ' MB' : round($size / 1024, 1) . ' KB';
                                ?>
                            </td>
                            <td class="py-3 px-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $status_class ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </td>
                            <td class="py-3 px-2 text-xs text-gray-500 max-w-[150px] truncate">
                                <?= htmlspecialchars($d['notes'] ?? '') ?: '<span class="text-gray-300">—</span>' ?>
                            </td>
                            <td class="py-3 px-2 text-right space-x-1">
                                <button type="button" onclick="editDocStatus(<?= $d['id'] ?>, '<?= $status ?>', '<?= htmlspecialchars($d['notes'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars(url($d['file_path']), ENT_QUOTES) ?>', '<?= htmlspecialchars($d['file_name'], ENT_QUOTES) ?>')"
                                    class="text-blue-600 hover:underline text-xs">Edit</button>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this document?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="delete_doc" value="1">
                                    <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:underline text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Document Status Edit Modal -->
<div id="docStatusModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full flex flex-col" style="max-height: 90vh;">
        <div class="flex-shrink-0 bg-white border-b px-6 py-4 flex items-center justify-between rounded-t-2xl">
            <h2 class="text-lg font-bold" id="docModalTitle">Edit Document Status</h2>
            <button onclick="closeDocModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="overflow-y-auto flex-1">
            <div id="docPreview" class="p-4 bg-gray-50 flex items-center justify-center min-h-[80px]"></div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="update_doc_status" value="1">
                <input type="hidden" name="doc_id" id="docStatusDocId">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="doc_status" id="docStatusSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="pending">Pending</option>
                        <option value="submitted">Submitted</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quick Notes</label>
                    <div class="flex flex-wrap gap-1 mb-2">
                        <button type="button" onclick="setDocNote('For OSA review and verification of documents.')" class="text-[11px] px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Need verification — refer to OSA</button>
                        <button type="button" onclick="setDocNote('Document is unclear. Please resubmit a clearer copy.')" class="text-[11px] px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Need resubmit — unclear copy</button>
                        <button type="button" onclick="setDocNote('Document is incomplete. Please resubmit the complete version.')" class="text-[11px] px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Need resubmit — incomplete</button>
                        <button type="button" onclick="setDocNote('Document is currently under review.')" class="text-[11px] px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Under review</button>
                        <button type="button" onclick="setDocNote('Document has been verified and approved.')" class="text-[11px] px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Approved — all clear</button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="doc_notes" id="docStatusNotes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Add notes about this document..."></textarea>
                </div>

                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="closeDocModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editDocStatus(id, status, notes, filePath, fileName) {
        document.getElementById('docStatusDocId').value = id;
        document.getElementById('docStatusSelect').value = status;
        document.getElementById('docStatusNotes').value = notes;
        document.getElementById('docModalTitle').textContent = 'Edit Document Status';

        const preview = document.getElementById('docPreview');
        if (filePath) {
            const isImg = filePath.match(/\.(jpg|jpeg|png|gif|webp)$/i);
            if (isImg) {
                preview.innerHTML = '<a href="' + filePath + '" target="_blank"><img src="' + filePath + '" alt="' + (fileName || 'Document') + '" class="max-h-48 mx-auto rounded-lg border"></a>';
            } else {
                preview.innerHTML = '<a href="' + filePath + '" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg text-sm hover:bg-blue-100"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> View File: ' + (fileName || 'Document') + '</a>';
            }
        } else {
            preview.innerHTML = '';
        }

        document.getElementById('docStatusModal').classList.remove('hidden');
        document.getElementById('docStatusModal').classList.add('flex');
    }

    function setDocNote(note) {
        document.getElementById('docStatusNotes').value = note;
    }

    function closeDocModal() {
        document.getElementById('docStatusModal').classList.add('hidden');
        document.getElementById('docStatusModal').classList.remove('flex');
    }

    document.getElementById('docStatusModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDocModal();
    });

    // Delete/restore functions
    function openDeleteModal() {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('flex');
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deletePassword').value = '';
    }

    document.getElementById('deleteModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
</script>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[80]" onclick="if(event.target===this)closeDeleteModal()">
    <div class="bg-white rounded-2xl max-w-md w-full m-4">
        <div class="border-b px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-red-700">Delete Student</h2>
            <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="soft_delete" value="1">

            <div class="p-4 bg-red-50 rounded-lg text-sm text-red-800">
                <strong>Warning:</strong> This will soft-delete the student. The account will be set to pending and permanently removed after 30 days. Enter your admin password to confirm.
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-bold text-gray-900 mb-2">Full Account Info</h3>
                <dl class="text-xs space-y-1">
                    <div class="flex"><dt class="w-28 text-gray-500">Name:</dt><dd class="font-medium"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></dd></div>
                    <div class="flex"><dt class="w-28 text-gray-500">Email:</dt><dd><?= htmlspecialchars($user['email'] ?? '') ?></dd></div>
                    <div class="flex"><dt class="w-28 text-gray-500">Contact:</dt><dd><?= htmlspecialchars($user['contact_number'] ?? '') ?></dd></div>
                    <div class="flex"><dt class="w-28 text-gray-500">Course:</dt><dd><?= htmlspecialchars($student['preferred_course'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-28 text-gray-500">Status:</dt><dd><?= htmlspecialchars($user['status'] ?? '') ?></dd></div>
                </dl>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Admin Password *</label>
                <input type="password" name="admin_password" id="deletePassword" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none"
                    placeholder="Enter your admin password to confirm">
            </div>

            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Confirm Delete</button>
            </div>
        </form>
    </div>
</div>

</main>
</div>
</body>
</html>
