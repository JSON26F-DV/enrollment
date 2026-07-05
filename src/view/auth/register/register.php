<?php
require_once __DIR__ . '/../../../../src/config/bootstrap.php';

if (check_logged_in()) {
    if (check_admin()) {
        header("Location: " . url('/src/view/admin/'));
    } elseif (check_staff()) {
        header("Location: " . url('/src/view/staff/dashboard.php'));
    } else {
        header("Location: " . url('/src/view/student/dashboard.php'));
    }
    exit;
}

$errors = [];
$success = '';
$old = [];

// Get courses for dropdown
$courses = [];
try {
    $stmt = $pdo->query("SELECT id, code, name FROM courses WHERE is_active = 1 ORDER BY name");
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    // Courses table might not exist yet
    $courses = [];
}

// Get academic years for dropdown
$academicYears = [];
try {
    $stmt = $pdo->query("SELECT id, year, semester FROM academic_years WHERE status IN ('active', 'enrollment_open') ORDER BY year DESC, semester");
    $academicYears = $stmt->fetchAll();
} catch (PDOException $e) {
    // Use default
    $academicYears = [['id' => 1, 'year' => '2026-2027', 'semester' => '1st Semester']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $old = $_POST;

        // Personal Information
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

        // Contact Information
        $email = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $home_address = trim($_POST['home_address'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');

        // Parent/Guardian
        $father_name = trim($_POST['father_name'] ?? '');
        $mother_name = trim($_POST['mother_name'] ?? '');
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $guardian_contact = trim($_POST['guardian_contact'] ?? '');
        $guardian_relationship = trim($_POST['guardian_relationship'] ?? '');

        // Educational Background
        $education_type = trim($_POST['education_type'] ?? 'freshman');
        $highschool_name = trim($_POST['highschool_name'] ?? '');
        $highschool_address = trim($_POST['highschool_address'] ?? '');
        $shs_track = trim($_POST['shs_track'] ?? '');
        $shs_strand = trim($_POST['shs_strand'] ?? '');
        $year_graduated = trim($_POST['year_graduated'] ?? '');
        $lrn = trim($_POST['lrn'] ?? '');
        $previous_college = trim($_POST['previous_college'] ?? '');
        $previous_course = trim($_POST['previous_course'] ?? '');
        $last_year_level = trim($_POST['last_year_level'] ?? '');

        // Course Selection
        $preferred_course = trim($_POST['preferred_course'] ?? '');
        $second_course = trim($_POST['second_course'] ?? '');
        $academic_year_id = trim($_POST['academic_year_id'] ?? '');

        // Validation
        if (empty($first_name))
            $errors[] = 'First name is required.';
        if (empty($last_name))
            $errors[] = 'Last name is required.';
        if (empty($email))
            $errors[] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = 'Invalid email format.';
        if (empty($birthday))
            $errors[] = 'Birthday is required.';
        if (empty($gender))
            $errors[] = 'Gender is required.';
        if (empty($contact_number))
            $errors[] = 'Contact number is required.';
        if (empty($preferred_course))
            $errors[] = 'Please select a preferred course.';
        if (empty($academic_year_id))
            $errors[] = 'Please select academic year.';

        // Age validation
        if (!empty($birthday)) {
            $birthDate = new DateTime($birthday);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            if ($age < 17)
                $errors[] = 'You must be at least 17 years old to enroll.';
            elseif ($age > 100)
                $errors[] = 'Invalid age. Please check your birthdate.';
        }

        // Check for existing application
        try {
            $stmt = $pdo->prepare("SELECT id FROM applicants WHERE email = ? AND status = 'pending'");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'You already have a pending application. Please wait for it to be reviewed.';
            }
        } catch (PDOException $e) {
            // Applicants table might not exist yet
        }

        // Get academic year info
        $academicYearInfo = null;
        if ($academic_year_id) {
            try {
                $stmt = $pdo->prepare("SELECT year, semester FROM academic_years WHERE id = ?");
                $stmt->execute([$academic_year_id]);
                $academicYearInfo = $stmt->fetch();
            } catch (PDOException $e) {
                // Use defaults
                $academicYearInfo = ['year' => '2026-2027', 'semester' => '1st Semester'];
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Insert applicant
                $stmt = $pdo->prepare("
                    INSERT INTO applicants (
                        first_name, middle_name, last_name, suffix, birthday, gender,
                        civil_status, nationality, religion, birth_place,
                        email, contact_number, home_address, province, city, barangay, zip_code,
                        father_name, mother_name, guardian_name, guardian_contact, guardian_relationship,
                        education_type, highschool_name, highschool_address, shs_track, shs_strand, year_graduated, lrn,
                        previous_college, previous_course, last_year_level,
                        preferred_course, second_course, semester, academic_year,
                        status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $first_name, $middle_name, $last_name, $suffix, $birthday, $gender,
                    $civil_status, $nationality, $religion, $birth_place,
                    $email, $contact_number, $home_address, $province, $city, $barangay, $zip_code,
                    $father_name, $mother_name, $guardian_name, $guardian_contact, $guardian_relationship,
                    $education_type, $highschool_name, $highschool_address, $shs_track, $shs_strand, $year_graduated, $lrn,
                    $previous_college, $previous_course, $last_year_level,
                    $preferred_course, $second_course,
                    $academicYearInfo['semester'] ?? '1st Semester', $academicYearInfo['year'] ?? '2026-2027',
                    'pending'
                ]);

                $applicant_id = $pdo->lastInsertId();

                // Handle document uploads - use configurable path from database
                $upload_path = get_document_path('applicant_document') ?? '/assets/uploads/documents/applicants/';
                $upload_dir = PROJECT_ROOT . $upload_path;
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $document_types = [
                    'psa_birth_certificate',
                    'form_138',
                    'good_moral',
                    'certificate_of_graduation',
                    'id_photo_2x2',
                    'valid_id',
                    'tor',
                    'honorable_dismissal'
                ];

                foreach ($document_types as $doc_type) {
                    if (isset($_FILES[$doc_type]) && $_FILES[$doc_type]['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES[$doc_type];
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

                        if (in_array($file['type'], $allowed_types) && $file['size'] <= 5 * 1024 * 1024) {
                            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $new_name = $doc_type . '_' . $applicant_id . '_' . time() . '.' . $ext;
                            $dest_path = $upload_dir . $new_name;

                            if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                                $relative_path = $upload_path . $new_name;
                                $stmt_doc = $pdo->prepare("
                                    INSERT INTO applicant_documents (applicant_id, document_type, file_name, file_path, file_size, mime_type)
                                    VALUES (?, ?, ?, ?, ?, ?)
                                ");
                                $stmt_doc->execute([
                                    $applicant_id,
                                    $doc_type,
                                    $file['name'],
                                    $relative_path,
                                    $file['size'],
                                    $file['type']
                                ]);
                            }
                        }
                    }
                }

                $pdo->commit();

                $success = 'Application submitted successfully! You will receive an email once your application is reviewed. Your student number and temporary password will be sent to your email after approval.';
                $old = [];

            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$step_labels = [
    'Personal Information',
    'Contact Information',
    'Parent / Guardian',
    'Educational Background',
    'Course Selection',
    'Upload Requirements'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NCST Enrollment Application</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <style>
        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        .file-upload-area {
            border: 2px dashed #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-upload-area:hover {
            border-color: var(--google-blue);
            background: rgba(26, 115, 232, 0.05);
        }

        .file-upload-area.has-file {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.05);
        }

        .file-name-display {
            font-size: 0.75rem;
            color: #22c55e;
            margin-top: 0.5rem;
            word-break: break-all;
        }

        .education-section {
            display: none;
        }

        .education-section.active {
            display: block;
        }
    </style>
</head>

<body>

    <nav class="absolute top-0 left-0 w-full p-4 md:p-8 z-10">
        <a href="<?= url('/src/view/guest/landing/landingpage.php') ?>"
            class="inline-flex items-center gap-2 text-sm font-bold text-black/60 hover:text-black transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back
        </a>
    </nav>

    <div class="min-h-screen flex items-center justify-center p-4 md:p-8 py-16">
        <div class="w-full max-w-[1040px] bg-white rounded-[32px] md:rounded-[40px] shadow-2xl overflow-hidden">

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 md:p-8 text-white">
                <div class="flex items-center gap-4">
                    <img src="<?= url('/public/images/ncst.png') ?>" alt="NCST"
                        class="w-12 h-12 rounded-xl object-cover bg-white">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-black">NCST Enrollment Application</h1>
                        <p class="text-blue-100 text-sm">Fill out the form below to apply for enrollment</p>
                    </div>
                </div>
            </div>

            <div class="p-6 md:p-8">

                <?php if (!empty($errors)): ?>
                    <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                        <?php foreach ($errors as $e): ?>
                            <p class="text-sm font-bold text-red-600"><?= htmlspecialchars($e) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="text-center py-8">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Application Submitted!</h2>
                        <p class="text-gray-600 max-w-md mx-auto mb-6"><?= htmlspecialchars($success) ?></p>
                        <a href="<?= url('/src/view/auth/login/loginpage.php') ?>"
                            class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm inline-block">
                            Go to Login
                        </a>
                    </div>
                <?php else: ?>

                    <form id="applicationForm" method="POST" action="" novalidate enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="current_step" id="currentStep" value="1">

                        <!-- Progress Bar -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-medium text-black/50">Step <span id="currentStepNum">1</span> of
                                    <?= count($step_labels) ?></span>
                                <span id="stepLabelText"
                                    class="text-xs font-medium text-google-blue"><?= $step_labels[0] ?></span>
                            </div>
                            <div class="flex gap-1.5">
                                <?php for ($i = 1; $i <= count($step_labels); $i++): ?>
                                    <div class="h-1.5 flex-1 rounded-full step-dot <?= $i === 1 ? 'bg-google-blue' : 'bg-black/20' ?>"
                                        data-step="<?= $i ?>"></div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Step 1: Personal Information -->
                        <div class="step-content active" data-step="1">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">1</span>
                                Personal Information
                            </h3>
                            <div class="flex flex-col gap-4">
                                <div class="flex gap-4">
                                    <div
                                        class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">First Name *</label>
                                        <input type="text" name="first_name"
                                            value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Juan">
                                    </div>
                                    <div
                                        class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Middle Name</label>
                                        <input type="text" name="middle_name"
                                            value="<?= htmlspecialchars($old['middle_name'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Santos">
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div
                                        class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Last Name *</label>
                                        <input type="text" name="last_name"
                                            value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Dela Cruz">
                                    </div>
                                    <div
                                        class="flex-[2] relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Suffix</label>
                                        <input type="text" name="suffix"
                                            value="<?= htmlspecialchars($old['suffix'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Jr., Sr., III">
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div
                                        class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Birthday *</label>
                                        <input type="date" name="birthday" id="birthday"
                                            value="<?= htmlspecialchars($old['birthday'] ?? '') ?>" required
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1"
                                            onchange="calculateAge()">
                                    </div>
                                    <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Gender *</label>
                                        <select name="gender" required
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?= ($old['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>
                                                Male</option>
                                            <option value="Female" <?= ($old['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                            <option value="Other" <?= ($old['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>
                                                Other</option>
                                        </select>
                                    </div>
                                    <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Age</label>
                                        <input type="text" name="age_display" id="age" value="" readonly
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1"
                                            placeholder="Auto-computed">
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div
                                        class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Birth Place</label>
                                        <input type="text" name="birth_place"
                                            value="<?= htmlspecialchars($old['birth_place'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="City/Municipality, Province">
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Civil Status</label>
                                        <select name="civil_status"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                            <option value="Single" <?= ($old['civil_status'] ?? 'Single') === 'Single' ? 'selected' : '' ?>>Single</option>
                                            <option value="Married" <?= ($old['civil_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                                            <option value="Widowed" <?= ($old['civil_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                            <option value="Separated" <?= ($old['civil_status'] ?? '') === 'Separated' ? 'selected' : '' ?>>Separated</option>
                                            <option value="Annulled" <?= ($old['civil_status'] ?? '') === 'Annulled' ? 'selected' : '' ?>>Annulled</option>
                                        </select>
                                    </div>
                                    <div
                                        class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Nationality</label>
                                        <input type="text" name="nationality"
                                            value="<?= htmlspecialchars($old['nationality'] ?? 'Filipino') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Filipino">
                                    </div>
                                    <div
                                        class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Religion</label>
                                        <input type="text" name="religion"
                                            value="<?= htmlspecialchars($old['religion'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Catholic, etc.">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Contact Information -->
                        <div class="step-content" data-step="2">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">2</span>
                                Contact Information
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div
                                    class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Email Address *</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                                        required
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="juan.delacruz@email.com">
                                </div>
                                <div
                                    class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Mobile Number *</label>
                                    <input type="tel" name="contact_number"
                                        value="<?= htmlspecialchars($old['contact_number'] ?? '') ?>" required
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="09123456789">
                                </div>
                                <div
                                    class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Home Address</label>
                                    <input type="text" name="home_address"
                                        value="<?= htmlspecialchars($old['home_address'] ?? '') ?>"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="House No., Street Name">
                                </div>
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Province</label>
                                    <input type="text" name="province"
                                        value="<?= htmlspecialchars($old['province'] ?? '') ?>"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="Cavite">
                                </div>
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">City/Municipality</label>
                                    <input type="text" name="city" value="<?= htmlspecialchars($old['city'] ?? '') ?>"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="Imus">
                                </div>
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Barangay</label>
                                    <input type="text" name="barangay"
                                        value="<?= htmlspecialchars($old['barangay'] ?? '') ?>"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="Bucandala">
                                </div>
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">ZIP Code</label>
                                    <input type="text" name="zip_code"
                                        value="<?= htmlspecialchars($old['zip_code'] ?? '') ?>"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="4103">
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Parent/Guardian -->
                        <div class="step-content" data-step="3">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">3</span>
                                Parent / Guardian Information
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Father's Name</label>
                                    <input type="text" name="father_name"
                                        value="<?= htmlspecialchars($old['father_name'] ?? '') ?>"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="Juan Sr. Dela Cruz">
                                </div>
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Mother's Name</label>
                                    <input type="text" name="mother_name"
                                        value="<?= htmlspecialchars($old['mother_name'] ?? '') ?>"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="Maria Santos Dela Cruz">
                                </div>
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Guardian's Name (if applicable)</label>
                                    <input type="text" name="guardian_name"
                                        value="<?= htmlspecialchars($old['guardian_name'] ?? '') ?>"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="If different from parents">
                                </div>
                                <div
                                    class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Guardian's Contact Number</label>
                                    <input type="tel" name="guardian_contact"
                                        value="<?= htmlspecialchars($old['guardian_contact'] ?? '') ?>"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                        placeholder="09123456789">
                                </div>
                                <div class="relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Relationship to Guardian</label>
                                    <select name="guardian_relationship"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                        <option value="">Select</option>
                                        <option value="Parent" <?= ($old['guardian_relationship'] ?? '') === 'Parent' ? 'selected' : '' ?>>Parent</option>
                                        <option value="Sibling" <?= ($old['guardian_relationship'] ?? '') === 'Sibling' ? 'selected' : '' ?>>Sibling</option>
                                        <option value="Grandparent" <?= ($old['guardian_relationship'] ?? '') === 'Grandparent' ? 'selected' : '' ?>>Grandparent</option>
                                        <option value="Uncle/Aunt" <?= ($old['guardian_relationship'] ?? '') === 'Uncle/Aunt' ? 'selected' : '' ?>>Uncle/Aunt</option>
                                        <option value="Other Relative" <?= ($old['guardian_relationship'] ?? '') === 'Other Relative' ? 'selected' : '' ?>>Other Relative</option>
                                        <option value="Legal Guardian" <?= ($old['guardian_relationship'] ?? '') === 'Legal Guardian' ? 'selected' : '' ?>>Legal Guardian</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Educational Background -->
                        <div class="step-content" data-step="4">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">4</span>
                                Educational Background
                            </h3>

                            <div class="mb-4">
                                <label class="text-sm font-medium text-gray-700 mb-2 block">Education Type</label>
                                <div class="flex flex-wrap gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="education_type" value="senior_high"
                                            <?= ($old['education_type'] ?? '') === 'senior_high' ? 'checked' : '' ?>
                                            onchange="toggleEducationFields()">
                                        <span class="text-sm">Senior High School</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="education_type" value="college_freshman"
                                            <?= ($old['education_type'] ?? '') === 'college_freshman' ? 'checked' : '' ?>
                                            onchange="toggleEducationFields()">
                                        <span class="text-sm">College (Freshman)</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="education_type" value="transferee"
                                            <?= ($old['education_type'] ?? '') === 'transferee' ? 'checked' : '' ?>
                                            onchange="toggleEducationFields()">
                                        <span class="text-sm">College (Transferee)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Senior High School Fields -->
                            <div id="seniorHighFields"
                                class="education-section <?= ($old['education_type'] ?? '') === 'senior_high' ? 'active' : '' ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div
                                        class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Junior High School Name</label>
                                        <input type="text" name="highschool_name"
                                            value="<?= htmlspecialchars($old['highschool_name'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Name of your school">
                                    </div>
                                    <div
                                        class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">School Address</label>
                                        <input type="text" name="highschool_address"
                                            value="<?= htmlspecialchars($old['highschool_address'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Complete school address">
                                    </div>
                                    <div class="relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                        <label class="text-xs font-medium text-black/50">SHS Track</label>
                                        <select name="shs_track" class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                            <option value="">Select Track</option>
                                            <option value="Academic" <?= ($old['shs_track'] ?? '') === 'Academic' ? 'selected' : '' ?>>Academic</option>
                                            <option value="Technical-Vocational" <?= ($old['shs_track'] ?? '') === 'Technical-Vocational' ? 'selected' : '' ?>>Technical-Vocational</option>
                                            <option value="Sports" <?= ($old['shs_track'] ?? '') === 'Sports' ? 'selected' : '' ?>>Sports</option>
                                            <option value="Arts and Design" <?= ($old['shs_track'] ?? '') === 'Arts and Design' ? 'selected' : '' ?>>Arts and Design</option>
                                        </select>
                                    </div>
                                    <div class="relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                        <label class="text-xs font-medium text-black/50">SHS Strand</label>
                                        <select name="shs_strand" id="shs_strand"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                            <option value="">Select Strand</option>
                                            <option value="STEM" <?= ($old['shs_strand'] ?? '') === 'STEM' ? 'selected' : '' ?>>STEM</option>
                                            <option value="HUMSS" <?= ($old['shs_strand'] ?? '') === 'HUMSS' ? 'selected' : '' ?>>HUMSS</option>
                                            <option value="ABM" <?= ($old['shs_strand'] ?? '') === 'ABM' ? 'selected' : '' ?>>ABM</option>
                                            <option value="TVL" <?= ($old['shs_strand'] ?? '') === 'TVL' ? 'selected' : '' ?>>TVL</option>
                                            <option value="GAS" <?= ($old['shs_strand'] ?? '') === 'GAS' ? 'selected' : '' ?>>GAS</option>
                                            <option value="Sports" <?= ($old['shs_strand'] ?? '') === 'Sports' ? 'selected' : '' ?>>Sports</option>
                                            <option value="Arts" <?= ($old['shs_strand'] ?? '') === 'Arts' ? 'selected' : '' ?>>Arts</option>
                                        </select>
                                    </div>
                                    <div
                                        class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Year Graduated</label>
                                        <input type="text" name="year_graduated"
                                            value="<?= htmlspecialchars($old['year_graduated'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="2026">
                                    </div>
                                    <div
                                        class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">LRN (Learner Reference Number)</label>
                                        <input type="text" name="lrn" value="<?= htmlspecialchars($old['lrn'] ?? '') ?>"
                                            maxlength="12"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="12-digit LRN">
                                    </div>
                                </div>
                            </div>

                            <!-- College Freshman Fields -->
                            <div id="collegeFreshmanFields"
                                class="education-section <?= ($old['education_type'] ?? '') === 'college_freshman' ? 'active' : '' ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div
                                        class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Senior High School Name</label>
                                        <input type="text" name="highschool_name"
                                            value="<?= htmlspecialchars($old['highschool_name'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Name of your school">
                                    </div>
                                    <div
                                        class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">School Address</label>
                                        <input type="text" name="highschool_address"
                                            value="<?= htmlspecialchars($old['highschool_address'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Complete school address">
                                    </div>
                                    <div class="relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                        <label class="text-xs font-medium text-black/50">SHS Track</label>
                                        <select name="shs_track" class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                            <option value="">Select Track</option>
                                            <option value="Academic" <?= ($old['shs_track'] ?? '') === 'Academic' ? 'selected' : '' ?>>Academic</option>
                                            <option value="Technical-Vocational" <?= ($old['shs_track'] ?? '') === 'Technical-Vocational' ? 'selected' : '' ?>>Technical-Vocational</option>
                                            <option value="Sports" <?= ($old['shs_track'] ?? '') === 'Sports' ? 'selected' : '' ?>>Sports</option>
                                            <option value="Arts and Design" <?= ($old['shs_track'] ?? '') === 'Arts and Design' ? 'selected' : '' ?>>Arts and Design</option>
                                        </select>
                                    </div>
                                    <div class="relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                        <label class="text-xs font-medium text-black/50">SHS Strand</label>
                                        <select name="shs_strand"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                            <option value="">Select Strand</option>
                                            <option value="STEM" <?= ($old['shs_strand'] ?? '') === 'STEM' ? 'selected' : '' ?>>STEM</option>
                                            <option value="HUMSS" <?= ($old['shs_strand'] ?? '') === 'HUMSS' ? 'selected' : '' ?>>HUMSS</option>
                                            <option value="ABM" <?= ($old['shs_strand'] ?? '') === 'ABM' ? 'selected' : '' ?>>ABM</option>
                                            <option value="TVL" <?= ($old['shs_strand'] ?? '') === 'TVL' ? 'selected' : '' ?>>TVL</option>
                                            <option value="GAS" <?= ($old['shs_strand'] ?? '') === 'GAS' ? 'selected' : '' ?>>GAS</option>
                                            <option value="Sports" <?= ($old['shs_strand'] ?? '') === 'Sports' ? 'selected' : '' ?>>Sports</option>
                                            <option value="Arts" <?= ($old['shs_strand'] ?? '') === 'Arts' ? 'selected' : '' ?>>Arts</option>
                                        </select>
                                    </div>
                                    <div
                                        class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Year Graduated</label>
                                        <input type="text" name="year_graduated"
                                            value="<?= htmlspecialchars($old['year_graduated'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="2026">
                                    </div>
                                    <div
                                        class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">LRN (Learner Reference Number)</label>
                                        <input type="text" name="lrn" value="<?= htmlspecialchars($old['lrn'] ?? '') ?>"
                                            maxlength="12"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="12-digit LRN">
                                    </div>
                                </div>
                            </div>

                            <!-- College Transferee Fields -->
                            <div id="transfereeFields"
                                class="education-section <?= ($old['education_type'] ?? '') === 'transferee' ? 'active' : '' ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div
                                        class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Previous College/University</label>
                                        <input type="text" name="previous_college"
                                            value="<?= htmlspecialchars($old['previous_college'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="Name of previous school">
                                    </div>
                                    <div
                                        class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Course Taken</label>
                                        <input type="text" name="previous_course"
                                            value="<?= htmlspecialchars($old['previous_course'] ?? '') ?>"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                            placeholder="BS Information Technology">
                                    </div>
                                    <div class="relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                        <label class="text-xs font-medium text-black/50">Last Year Level</label>
                                        <select name="last_year_level"
                                            class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                            <option value="">Select Year Level</option>
                                            <option value="1st Year" <?= ($old['last_year_level'] ?? '') === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                            <option value="2nd Year" <?= ($old['last_year_level'] ?? '') === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                            <option value="3rd Year" <?= ($old['last_year_level'] ?? '') === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                            <option value="4th Year" <?= ($old['last_year_level'] ?? '') === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                                            <option value="5th Year" <?= ($old['last_year_level'] ?? '') === '5th Year' ? 'selected' : '' ?>>5th Year</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Course Selection (Only for College) -->
                        <div id="courseSelectionStep" class="step-content <?= ($old['education_type'] ?? '') === 'senior_high' ? 'hidden' : '' ?>" data-step="5">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">5</span>
                                Course Selection
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Preferred Course *</label>
                                    <select name="preferred_course" id="preferred_course" required
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                        <option value="">Select Course</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?= htmlspecialchars($course['code']) ?>"
                                                <?= ($old['preferred_course'] ?? '') === $course['code'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($course['code']) ?> -
                                                <?= htmlspecialchars($course['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Second Choice Course (Optional)</label>
                                    <select name="second_course"
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                        <option value="">Select Course</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?= htmlspecialchars($course['code']) ?>" <?= ($old['second_course'] ?? '') === $course['code'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($course['code']) ?> -
                                                <?= htmlspecialchars($course['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Academic Year *</label>
                                    <select name="academic_year_id" required
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                        <option value="">Select Academic Year</option>
                                        <?php foreach ($academicYears as $ay): ?>
                                            <option value="<?= $ay['id'] ?>" <?= ($old['academic_year_id'] ?? '') === $ay['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ay['year']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                    <label class="text-xs font-medium text-black/50">Semester *</label>
                                    <select name="semester" required
                                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                        <option value="1st Semester" <?= ($old['semester'] ?? '') === '1st Semester' ? 'selected' : '' ?>>1st Semester</option>
                                        <option value="2nd Semester" <?= ($old['semester'] ?? '') === '2nd Semester' ? 'selected' : '' ?>>2nd Semester</option>
                                        <option value="Summer" <?= ($old['semester'] ?? '') === 'Summer' ? 'selected' : '' ?>>
                                            Summer</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Example Display -->
                            <div class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
                                <h4 class="text-sm font-bold text-blue-800 mb-2">Example:</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div><span class="text-blue-600">Course:</span> <span class="font-medium">BS Information
                                            Technology</span></div>
                                    <div><span class="text-blue-600">Academic Year:</span> <span
                                            class="font-medium">2026-2027</span></div>
                                    <div><span class="text-blue-600">Semester:</span> <span class="font-medium">1st
                                            Semester</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 6: Document Upload -->
                        <div class="step-content" data-step="6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">6</span>
                                Upload Requirements
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">Upload clear images/scans of the following documents.
                                Files should be JPG, PNG, or PDF (max 5MB each).</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- PSA Birth Certificate -->
                                <div>
                                    <label class="text-xs font-medium text-black/50 mb-1 block">PSA Birth Certificate
                                        *</label>
                                    <div class="file-upload-area"
                                        onclick="document.getElementById('psa_birth_certificate').click()">
                                        <input type="file" id="psa_birth_certificate" name="psa_birth_certificate"
                                            accept=".jpg,.jpeg,.png,.pdf" class="hidden" onchange="updateFileName(this)">
                                        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-sm text-gray-500">Click to upload</p>
                                        <p class="file-name-display" id="psa_birth_certificate_name"></p>
                                    </div>
                                </div>

                                <!-- Form 138 -->
                                <div>
                                    <label class="text-xs font-medium text-black/50 mb-1 block">Form 138 / Report
                                        Card</label>
                                    <div class="file-upload-area" onclick="document.getElementById('form_138').click()">
                                        <input type="file" id="form_138" name="form_138" accept=".jpg,.jpeg,.png,.pdf"
                                            class="hidden" onchange="updateFileName(this)">
                                        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-sm text-gray-500">Click to upload</p>
                                        <p class="file-name-display" id="form_138_name"></p>
                                    </div>
                                </div>

                                <!-- Good Moral -->
                                <div>
                                    <label class="text-xs font-medium text-black/50 mb-1 block">Good Moral
                                        Certificate</label>
                                    <div class="file-upload-area" onclick="document.getElementById('good_moral').click()">
                                        <input type="file" id="good_moral" name="good_moral" accept=".jpg,.jpeg,.png,.pdf"
                                            class="hidden" onchange="updateFileName(this)">
                                        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-sm text-gray-500">Click to upload</p>
                                        <p class="file-name-display" id="good_moral_name"></p>
                                    </div>
                                </div>

                                <!-- Certificate of Graduation -->
                                <div>
                                    <label class="text-xs font-medium text-black/50 mb-1 block">Certificate of Graduation
                                        (optional)</label>
                                    <div class="file-upload-area"
                                        onclick="document.getElementById('certificate_of_graduation').click()">
                                        <input type="file" id="certificate_of_graduation" name="certificate_of_graduation"
                                            accept=".jpg,.jpeg,.png,.pdf" class="hidden" onchange="updateFileName(this)">
                                        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-sm text-gray-500">Click to upload (optional)</p>
                                        <p class="file-name-display" id="certificate_of_graduation_name"></p>
                                    </div>
                                </div>

                                <!-- 2x2 ID Photo -->
                                <div>
                                    <label class="text-xs font-medium text-black/50 mb-1 block">2x2 ID Picture</label>
                                    <div class="file-upload-area" onclick="document.getElementById('id_photo_2x2').click()">
                                        <input type="file" id="id_photo_2x2" name="id_photo_2x2" accept=".jpg,.jpeg,.png"
                                            class="hidden" onchange="updateFileName(this)">
                                        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-sm text-gray-500">Click to upload</p>
                                        <p class="file-name-display" id="id_photo_2x2_name"></p>
                                    </div>
                                </div>

                                <!-- Valid ID -->
                                <div>
                                    <label class="text-xs font-medium text-black/50 mb-1 block">Valid ID (optional)</label>
                                    <div class="file-upload-area" onclick="document.getElementById('valid_id').click()">
                                        <input type="file" id="valid_id" name="valid_id" accept=".jpg,.jpeg,.png,.pdf"
                                            class="hidden" onchange="updateFileName(this)">
                                        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-sm text-gray-500">Click to upload (optional)</p>
                                        <p class="file-name-display" id="valid_id_name"></p>
                                    </div>
                                </div>

                                <!-- TOR (for transferees) -->
                                <div>
                                    <label class="text-xs font-medium text-black/50 mb-1 block">Transcript of Records
                                        (Transferees only)</label>
                                    <div class="file-upload-area" onclick="document.getElementById('tor').click()">
                                        <input type="file" id="tor" name="tor" accept=".jpg,.jpeg,.png,.pdf" class="hidden"
                                            onchange="updateFileName(this)">
                                        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-sm text-gray-500">Click to upload (for transferees)</p>
                                        <p class="file-name-display" id="tor_name"></p>
                                    </div>
                                </div>

                                <!-- Honorable Dismissal -->
                                <div>
                                    <label class="text-xs font-medium text-black/50 mb-1 block">Honorable Dismissal
                                        (Transferees only)</label>
                                    <div class="file-upload-area"
                                        onclick="document.getElementById('honorable_dismissal').click()">
                                        <input type="file" id="honorable_dismissal" name="honorable_dismissal"
                                            accept=".jpg,.jpeg,.png,.pdf" class="hidden" onchange="updateFileName(this)">
                                        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-sm text-gray-500">Click to upload (for transferees)</p>
                                        <p class="file-name-display" id="honorable_dismissal_name"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 p-4 bg-amber-50 rounded-xl border border-amber-200">
                                <p class="text-sm text-amber-800">
                                    <strong>Note:</strong> Documents are optional at this stage. You can upload them later
                                    if needed. However, your application will be processed faster if all documents are
                                    submitted.
                                </p>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex items-center justify-between mt-8 pt-6 border-t border-black/10">
                            <button type="button" id="backBtn" onclick="prevStep()"
                                class="px-6 py-2 text-sm font-bold text-google-blue hover:bg-google-blue/10 rounded-full transition-colors active:scale-95">
                                ← Back
                            </button>
                            <div class="flex gap-2">
                                <button type="button" id="nextBtn" onclick="nextStep()"
                                    class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95">
                                    Next →
                                </button>
                                <button type="submit" name="submit_application" id="submitBtn"
                                    class="px-6 py-2.5 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-full transition-colors shadow-sm active:scale-95 hidden">
                                    Submit Application ✓
                                </button>
                            </div>
                        </div>

                    </form>

                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="<?= url('/assets/js/main.js') ?>"></script>
    <script>
        let currentStep = 1;
        const totalSteps = <?= count($step_labels) ?>;
        const stepLabels = <?= json_encode($step_labels) ?>;

        function updateStepDisplay() {
            // Update step content visibility
            document.querySelectorAll('.step-content').forEach(el => {
                el.classList.remove('active');
                if (parseInt(el.dataset.step) === currentStep) {
                    el.classList.add('active');
                }
            });

            // Update step indicators
            document.querySelectorAll('.step-dot').forEach(dot => {
                const step = parseInt(dot.dataset.step);
                dot.className = 'h-1.5 flex-1 rounded-full step-dot ' + (step <= currentStep ? 'bg-google-blue' : 'bg-black/20');
            });

            // Update step label
            document.getElementById('currentStepNum').textContent = currentStep;
            document.getElementById('stepLabelText').textContent = stepLabels[currentStep - 1];
            document.getElementById('currentStep').value = currentStep;

            // Show/hide navigation buttons
            document.getElementById('backBtn').style.visibility = currentStep === 1 ? 'hidden' : 'visible';
            document.getElementById('nextBtn').classList.toggle('hidden', currentStep === totalSteps);
            document.getElementById('submitBtn').classList.toggle('hidden', currentStep !== totalSteps);
        }

        function validateStep(step) {
            const currentContent = document.querySelector(`.step-content[data-step="${step}"]`);
            if (!currentContent) return true;

            const requiredFields = currentContent.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '';
                }
            });

            return isValid;
        }

        function nextStep() {
            if (!validateStep(currentStep)) {
                alert('Please fill in all required fields.');
                return;
            }

            if (currentStep < totalSteps) {
                currentStep++;
                updateStepDisplay();
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepDisplay();
            }
        }

        function calculateAge() {
            const birthday = document.getElementById('birthday').value;
            if (birthday) {
                const birthDate = new Date(birthday);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                document.getElementById('age').value = age;
            }
        }

        function toggleEducationFields() {
            const educationType = document.querySelector('input[name="education_type"]:checked')?.value || '';
            document.getElementById('seniorHighFields').classList.toggle('active', educationType === 'senior_high');
            document.getElementById('collegeFreshmanFields').classList.toggle('active', educationType === 'college_freshman');
            document.getElementById('transfereeFields').classList.toggle('active', educationType === 'transferee');
            // Hide course selection for Senior High
            document.getElementById('courseSelectionStep').classList.toggle('hidden', educationType === 'senior_high');
        }

        function updateFileName(input) {
            const nameDisplay = document.getElementById(input.id + '_name');
            const uploadArea = input.closest('.file-upload-area');

            if (input.files && input.files[0]) {
                nameDisplay.textContent = input.files[0].name;
                uploadArea.classList.add('has-file');
            } else {
                nameDisplay.textContent = '';
                uploadArea.classList.remove('has-file');
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            updateStepDisplay();
            toggleEducationFields();
            calculateAge();
        });
    </script>
</body>

</html>