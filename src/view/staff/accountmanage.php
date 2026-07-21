<?php
require_once __DIR__ . '/../../../header.php';
require_staff();

$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = $edit_id > 0;

$errors = [];
$success = '';
$old = [];

$courses = [];
try {
    $stmt = $pdo->query("SELECT id, code, name FROM courses WHERE is_active = 1 ORDER BY name");
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}

$bachelorCourses = array_values(array_filter($courses, function ($c) {
    return str_contains($c['name'], 'Bachelor') || str_starts_with($c['code'], 'B');
}));

$academicYears = [];
try {
    $stmt = $pdo->query("SELECT id, year, semester FROM academic_years WHERE status IN ('active', 'enrollment_open') ORDER BY year DESC, semester");
    $academicYears = $stmt->fetchAll();
} catch (PDOException $e) {
    $academicYears = [['id' => 1, 'year' => '2026-2027', 'semester' => '1st Semester']];
}

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student' AND deleted_at IS NULL");
    $stmt->execute([$edit_id]);
    $user = $stmt->fetch();
    if (!$user) {
        header("Location: " . url('/src/view/staff/dashboard.php'));
        exit;
    }

    $student = [];
    $stmt2 = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt2->execute([$edit_id]);
    $student = $stmt2->fetch() ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
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

        $father_name = trim($_POST['father_name'] ?? '');
        $mother_name = trim($_POST['mother_name'] ?? '');
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $guardian_contact = trim($_POST['guardian_contact'] ?? '');
        $guardian_relationship = trim($_POST['guardian_relationship'] ?? '');

        $education_type = trim($_POST['education_type'] ?? 'college_freshman');
        $highschool_name = trim($_POST['highschool_name'] ?? '');
        $highschool_address = trim($_POST['highschool_address'] ?? '');
        $shs_track = trim($_POST['shs_track'] ?? '');
        $shs_strand = trim($_POST['shs_strand'] ?? '');
        $year_graduated = trim($_POST['year_graduated'] ?? '');
        $lrn = trim($_POST['lrn'] ?? '');
        $previous_college = trim($_POST['previous_college'] ?? '');
        $previous_course = trim($_POST['previous_course'] ?? '');
        $last_year_level = trim($_POST['last_year_level'] ?? '');

        $preferred_course = trim($_POST['preferred_course'] ?? '');
        $second_course = trim($_POST['second_course'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $academic_year_id = trim($_POST['academic_year_id'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($first_name)) $errors[] = 'First name is required.';
        if (empty($last_name)) $errors[] = 'Last name is required.';
        if (empty($email)) $errors[] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
        if (empty($birthday)) $errors[] = 'Birthday is required.';
        if (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other'])) $errors[] = 'Gender is required.';
        if (empty($contact_number)) $errors[] = 'Contact number is required.';
        if (empty($preferred_course)) $errors[] = 'Please select a preferred course.';
        if (empty($academic_year_id)) $errors[] = 'Please select an academic year.';

        if (!$is_edit) {
            if (empty($password)) $errors[] = 'Password is required.';
            elseif (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        } else {
            if (!empty($password) && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $edit_id ?: 0]);
        if ($stmt->fetch()) $errors[] = 'An account with this email already exists.';

        $academicYearInfo = null;
        if ($academic_year_id) {
            try {
                $stmt = $pdo->prepare("SELECT year, semester FROM academic_years WHERE id = ?");
                $stmt->execute([$academic_year_id]);
                $academicYearInfo = $stmt->fetch();
            } catch (PDOException $e) {
                $academicYearInfo = ['year' => '2026-2027', 'semester' => '1st Semester'];
            }
        }

        if (!empty($birthday)) {
            $birthDate = new DateTime($birthday);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            if ($age < 15) $errors[] = 'Student must be at least 15 years old.';
            elseif ($age > 100) $errors[] = 'Invalid age. Please check the birthdate.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $age = null;
                if (!empty($birthday)) {
                    $birthDate = new DateTime($birthday);
                    $today = new DateTime();
                    $age = $today->diff($birthDate)->y;
                }

                if ($is_edit) {
                    if (!empty($password)) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, birthday = ?, age = ?, gender = ?, civil_status = ?, nationality = ?, religion = ?, birth_place = ?, email = ?, contact_number = ?, home_address = ?, province = ?, city = ?, barangay = ?, zip_code = ?, password = ? WHERE id = ?");
                        $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $birthday, $age, $gender, $civil_status, $nationality, $religion, $birth_place, $email, $contact_number, $home_address, $province, $city, $barangay, $zip_code, $hashed, $edit_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, birthday = ?, age = ?, gender = ?, civil_status = ?, nationality = ?, religion = ?, birth_place = ?, email = ?, contact_number = ?, home_address = ?, province = ?, city = ?, barangay = ?, zip_code = ? WHERE id = ?");
                        $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $birthday, $age, $gender, $civil_status, $nationality, $religion, $birth_place, $email, $contact_number, $home_address, $province, $city, $barangay, $zip_code, $edit_id]);
                    }

                    $stmt = $pdo->prepare("UPDATE students SET father_name = ?, mother_name = ?, guardian_name = ?, guardian_contact = ?, guardian_relationship = ?, education_type = ?, highschool_name = ?, highschool_address = ?, shs_track = ?, shs_strand = ?, year_graduated = ?, lrn = ?, previous_college = ?, previous_course = ?, last_year_level = ?, preferred_course = ?, second_course = ?, semester = ?, academic_year = ? WHERE user_id = ?");
                    $stmt->execute([$father_name, $mother_name, $guardian_name, $guardian_contact, $guardian_relationship, $education_type, $highschool_name, $highschool_address, $shs_track, $shs_strand, $year_graduated, $lrn, $previous_college, $previous_course, $last_year_level, $preferred_course, $second_course, $semester, $academicYearInfo['year'] ?? '', $edit_id]);

                    $success = 'Student updated successfully!';
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, suffix, birthday, age, gender, civil_status, nationality, religion, birth_place, email, contact_number, home_address, province, city, barangay, zip_code, password, role, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 'pending', ?)");
                    $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $birthday, $age, $gender, $civil_status, $nationality, $religion, $birth_place, $email, $contact_number, $home_address, $province, $city, $barangay, $zip_code, $hashed, $_SESSION['user_id']]);
                    $user_id = $pdo->lastInsertId();

                    $stmt = $pdo->prepare("INSERT INTO students (user_id, father_name, mother_name, guardian_name, guardian_contact, guardian_relationship, education_type, highschool_name, highschool_address, shs_track, shs_strand, year_graduated, lrn, previous_college, previous_course, last_year_level, preferred_course, second_course, semester, academic_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $father_name, $mother_name, $guardian_name, $guardian_contact, $guardian_relationship, $education_type, $highschool_name, $highschool_address, $shs_track, $shs_strand, $year_graduated, $lrn, $previous_college, $previous_course, $last_year_level, $preferred_course, $second_course, $semester, $academicYearInfo['year'] ?? '']);

                    $success = 'Student account created successfully!';
                    $old = [];
                }

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$page_title = $is_edit ? 'Edit Student' : 'Register Student';

$step_labels = [
    'Personal Information',
    'Contact Information',
    'Parent / Guardian',
    'Educational Background',
    'Course Selection'
];

function uval($field, $is_edit, $user = [], $student = [], $old = []) {
    if (isset($old[$field]) && $old[$field] !== '') return $old[$field];
    if ($is_edit && isset($student[$field]) && $student[$field] !== '') return $student[$field];
    if ($is_edit && isset($user[$field]) && $user[$field] !== '') return $user[$field];
    return '';
}
?>

<div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12 flex flex-col items-center justify-center">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="<?= url('/src/view/staff/dashboard.php') ?>"
                class="inline-flex items-center gap-1 text-sm font-bold text-google-blue hover:underline">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </a>
            <h1 class="text-4xl font-black tracking-tighter italic uppercase"><?= $page_title ?></h1>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-xl w-full max-w-2xl">
            <?php foreach ($errors as $e): ?>
                <p class="text-sm font-bold text-red-600"><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 rounded-xl">
            <p class="text-sm font-bold text-green-600"><?= htmlspecialchars($success) ?></p>
        </div>
        <a href="<?= url('/src/view/staff/dashboard.php') ?>"
            class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm active:scale-95 inline-block">Back
            to Dashboard</a>
    <?php else: ?>

        <div class="border border-black/10 rounded-[32px] p-6 md:p-8 bg-white max-w-2xl">
            <form id="applicationForm" method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="current_step" id="currentStep" value="1">

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
                        <span class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">1</span>
                        Personal Information
                    </h3>
                    <div class="flex flex-col gap-4">
                        <div class="flex gap-4">
                            <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">First Name *</label>
                                <input type="text" name="first_name" value="<?= htmlspecialchars(uval('first_name', $is_edit, $user ?? [], $student ?? [], $old)) ?>" required
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Juan">
                            </div>
                            <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Middle Name</label>
                                <input type="text" name="middle_name" value="<?= htmlspecialchars(uval('middle_name', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Santos">
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Last Name *</label>
                                <input type="text" name="last_name" value="<?= htmlspecialchars(uval('last_name', $is_edit, $user ?? [], $student ?? [], $old)) ?>" required
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Dela Cruz">
                            </div>
                            <div class="flex-[2] relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Suffix</label>
                                <input type="text" name="suffix" value="<?= htmlspecialchars(uval('suffix', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Jr., Sr., III">
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Birthday *</label>
                                <input type="date" name="birthday" id="birthday" value="<?= htmlspecialchars(uval('birthday', $is_edit, $user ?? [], $student ?? [], $old)) ?>" required
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1" onchange="calculateAge()">
                            </div>
                            <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Gender *</label>
                                <select name="gender" required class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= uval('gender', $is_edit, $user ?? [], $student ?? [], $old) === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= uval('gender', $is_edit, $user ?? [], $student ?? [], $old) === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= uval('gender', $is_edit, $user ?? [], $student ?? [], $old) === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                                <label class="text-xs font-medium text-black/50">Age</label>
                                <input type="text" name="age_display" id="age" value="<?= $is_edit ? ($user['age'] ?? '') : '' ?>" readonly
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1" placeholder="Auto-computed">
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Birth Place</label>
                                <input type="text" name="birth_place" value="<?= htmlspecialchars(uval('birth_place', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="City/Municipality, Province">
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Civil Status</label>
                                <select name="civil_status" class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                    <option value="Single" <?= uval('civil_status', $is_edit, $user ?? [], $student ?? [], $old) === 'Single' ? 'selected' : '' ?>>Single</option>
                                    <option value="Married" <?= uval('civil_status', $is_edit, $user ?? [], $student ?? [], $old) === 'Married' ? 'selected' : '' ?>>Married</option>
                                    <option value="Widowed" <?= uval('civil_status', $is_edit, $user ?? [], $student ?? [], $old) === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                    <option value="Separated" <?= uval('civil_status', $is_edit, $user ?? [], $student ?? [], $old) === 'Separated' ? 'selected' : '' ?>>Separated</option>
                                    <option value="Annulled" <?= uval('civil_status', $is_edit, $user ?? [], $student ?? [], $old) === 'Annulled' ? 'selected' : '' ?>>Annulled</option>
                                </select>
                            </div>
                            <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Nationality</label>
                                <input type="text" name="nationality" value="<?= htmlspecialchars(uval('nationality', $is_edit, $user ?? [], $student ?? [], $old) ?: 'Filipino') ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Filipino">
                            </div>
                            <div class="flex-1 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Religion</label>
                                <input type="text" name="religion" value="<?= htmlspecialchars(uval('religion', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Catholic, etc.">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Contact Information -->
                <div class="step-content" data-step="2">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">2</span>
                        Contact Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Email Address *</label>
                            <input type="email" name="email" value="<?= htmlspecialchars(uval('email', $is_edit, $user ?? [], $student ?? [], $old)) ?>" required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="you@ncst.edu.ph">
                        </div>
                        <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Mobile Number *</label>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-sm font-bold text-black/50 whitespace-nowrap">+63</span>
                                <input type="tel" name="contact_number" value="<?= htmlspecialchars(uval('contact_number', $is_edit, $user ?? [], $student ?? [], $old)) ?>" required
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none py-1 placeholder:text-black/30"
                                    placeholder="912 345 6789" pattern="[0-9\s\-]{7,15}">
                            </div>
                        </div>
                        <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Home Address</label>
                            <input type="text" name="home_address" value="<?= htmlspecialchars(uval('home_address', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="House No., Street Name">
                        </div>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Province</label>
                            <input type="text" name="province" value="<?= htmlspecialchars(uval('province', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Cavite">
                        </div>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">City/Municipality</label>
                            <input type="text" name="city" value="<?= htmlspecialchars(uval('city', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Imus">
                        </div>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Barangay</label>
                            <input type="text" name="barangay" value="<?= htmlspecialchars(uval('barangay', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Bucandala">
                        </div>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">ZIP Code</label>
                            <input type="text" name="zip_code" value="<?= htmlspecialchars(uval('zip_code', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="4103">
                        </div>
                    </div>
                </div>

                <!-- Step 3: Parent/Guardian -->
                <div class="step-content" data-step="3">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">3</span>
                        Parent / Guardian Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Father's Name</label>
                            <input type="text" name="father_name" value="<?= htmlspecialchars(uval('father_name', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Juan Sr. Dela Cruz">
                        </div>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Mother's Name</label>
                            <input type="text" name="mother_name" value="<?= htmlspecialchars(uval('mother_name', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Maria Santos Dela Cruz">
                        </div>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Guardian's Name (if applicable)</label>
                            <input type="text" name="guardian_name" value="<?= htmlspecialchars(uval('guardian_name', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="If different from parents">
                        </div>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Guardian's Contact Number</label>
                            <input type="tel" name="guardian_contact" value="<?= htmlspecialchars(uval('guardian_contact', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="09123456789">
                        </div>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Relationship to Guardian</label>
                            <select name="guardian_relationship" class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                <option value="">Select</option>
                                <option value="Parent" <?= uval('guardian_relationship', $is_edit, $user ?? [], $student ?? [], $old) === 'Parent' ? 'selected' : '' ?>>Parent</option>
                                <option value="Sibling" <?= uval('guardian_relationship', $is_edit, $user ?? [], $student ?? [], $old) === 'Sibling' ? 'selected' : '' ?>>Sibling</option>
                                <option value="Grandparent" <?= uval('guardian_relationship', $is_edit, $user ?? [], $student ?? [], $old) === 'Grandparent' ? 'selected' : '' ?>>Grandparent</option>
                                <option value="Uncle/Aunt" <?= uval('guardian_relationship', $is_edit, $user ?? [], $student ?? [], $old) === 'Uncle/Aunt' ? 'selected' : '' ?>>Uncle/Aunt</option>
                                <option value="Other Relative" <?= uval('guardian_relationship', $is_edit, $user ?? [], $student ?? [], $old) === 'Other Relative' ? 'selected' : '' ?>>Other Relative</option>
                                <option value="Legal Guardian" <?= uval('guardian_relationship', $is_edit, $user ?? [], $student ?? [], $old) === 'Legal Guardian' ? 'selected' : '' ?>>Legal Guardian</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Educational Background -->
                <div class="step-content" data-step="4">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">4</span>
                        Educational Background
                    </h3>

                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Education Type</label>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="education_type" value="senior_high" <?= uval('education_type', $is_edit, $user ?? [], $student ?? [], $old) === 'senior_high' ? 'checked' : '' ?> onchange="toggleEducationFields()">
                                <span class="text-sm">Senior High School</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="education_type" value="college_freshman" <?= (uval('education_type', $is_edit, $user ?? [], $student ?? [], $old) ?: 'college_freshman') === 'college_freshman' ? 'checked' : '' ?> onchange="toggleEducationFields()">
                                <span class="text-sm">College (Freshman)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="education_type" value="transferee" <?= uval('education_type', $is_edit, $user ?? [], $student ?? [], $old) === 'transferee' ? 'checked' : '' ?> onchange="toggleEducationFields()">
                                <span class="text-sm">College (Transferee)</span>
                            </label>
                        </div>
                    </div>

                    <div id="seniorHighFields" class="education-section <?= uval('education_type', $is_edit, $user ?? [], $student ?? [], $old) === 'senior_high' ? 'active' : '' ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Junior High School Name</label>
                                <input type="text" name="highschool_name" value="<?= htmlspecialchars(uval('highschool_name', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Name of your school">
                            </div>
                            <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">School Address</label>
                                <input type="text" name="highschool_address" value="<?= htmlspecialchars(uval('highschool_address', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Complete school address">
                            </div>
                            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Year Graduated</label>
                                <input type="text" name="year_graduated" value="<?= htmlspecialchars(uval('year_graduated', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="2026">
                            </div>
                            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">LRN (Learner Reference Number)</label>
                                <input type="text" name="lrn" value="<?= htmlspecialchars(uval('lrn', $is_edit, $user ?? [], $student ?? [], $old)) ?>" maxlength="12"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="12-digit LRN">
                            </div>
                        </div>
                    </div>

                    <div id="collegeFreshmanFields" class="education-section <?= uval('education_type', $is_edit, $user ?? [], $student ?? [], $old) === 'college_freshman' ? 'active' : '' ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Senior High School Name</label>
                                <input type="text" name="highschool_name" value="<?= htmlspecialchars(uval('highschool_name', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Name of your school">
                            </div>
                            <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">School Address</label>
                                <input type="text" name="highschool_address" value="<?= htmlspecialchars(uval('highschool_address', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Complete school address">
                            </div>
                            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">SHS Track</label>
                                <select name="shs_track" class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                    <option value="">Select Track</option>
                                    <option value="Academic" <?= uval('shs_track', $is_edit, $user ?? [], $student ?? [], $old) === 'Academic' ? 'selected' : '' ?>>Academic</option>
                                    <option value="Technical-Vocational" <?= uval('shs_track', $is_edit, $user ?? [], $student ?? [], $old) === 'Technical-Vocational' ? 'selected' : '' ?>>Technical-Vocational</option>
                                    <option value="Sports" <?= uval('shs_track', $is_edit, $user ?? [], $student ?? [], $old) === 'Sports' ? 'selected' : '' ?>>Sports</option>
                                    <option value="Arts and Design" <?= uval('shs_track', $is_edit, $user ?? [], $student ?? [], $old) === 'Arts and Design' ? 'selected' : '' ?>>Arts and Design</option>
                                </select>
                            </div>
                            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">SHS Strand</label>
                                <select name="shs_strand" class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                    <option value="">Select Strand</option>
                                    <option value="STEM" <?= uval('shs_strand', $is_edit, $user ?? [], $student ?? [], $old) === 'STEM' ? 'selected' : '' ?>>STEM</option>
                                    <option value="HUMSS" <?= uval('shs_strand', $is_edit, $user ?? [], $student ?? [], $old) === 'HUMSS' ? 'selected' : '' ?>>HUMSS</option>
                                    <option value="ABM" <?= uval('shs_strand', $is_edit, $user ?? [], $student ?? [], $old) === 'ABM' ? 'selected' : '' ?>>ABM</option>
                                    <option value="TVL" <?= uval('shs_strand', $is_edit, $user ?? [], $student ?? [], $old) === 'TVL' ? 'selected' : '' ?>>TVL</option>
                                    <option value="GAS" <?= uval('shs_strand', $is_edit, $user ?? [], $student ?? [], $old) === 'GAS' ? 'selected' : '' ?>>GAS</option>
                                    <option value="Sports" <?= uval('shs_strand', $is_edit, $user ?? [], $student ?? [], $old) === 'Sports' ? 'selected' : '' ?>>Sports</option>
                                    <option value="Arts" <?= uval('shs_strand', $is_edit, $user ?? [], $student ?? [], $old) === 'Arts' ? 'selected' : '' ?>>Arts</option>
                                </select>
                            </div>
                            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Year Graduated</label>
                                <input type="text" name="year_graduated" value="<?= htmlspecialchars(uval('year_graduated', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="2026">
                            </div>
                            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">LRN (Learner Reference Number)</label>
                                <input type="text" name="lrn" value="<?= htmlspecialchars(uval('lrn', $is_edit, $user ?? [], $student ?? [], $old)) ?>" maxlength="12"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="12-digit LRN">
                            </div>
                        </div>
                    </div>

                    <div id="transfereeFields" class="education-section <?= uval('education_type', $is_edit, $user ?? [], $student ?? [], $old) === 'transferee' ? 'active' : '' ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Previous College/University</label>
                                <input type="text" name="previous_college" value="<?= htmlspecialchars(uval('previous_college', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="Name of previous school">
                            </div>
                            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Course Taken</label>
                                <input type="text" name="previous_course" value="<?= htmlspecialchars(uval('previous_course', $is_edit, $user ?? [], $student ?? [], $old)) ?>"
                                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30" placeholder="BS Information Technology">
                            </div>
                            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                                <label class="text-xs font-medium text-black/50">Last Year Level</label>
                                <select name="last_year_level" class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                    <option value="">Select Year Level</option>
                                    <option value="1st Year" <?= uval('last_year_level', $is_edit, $user ?? [], $student ?? [], $old) === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                    <option value="2nd Year" <?= uval('last_year_level', $is_edit, $user ?? [], $student ?? [], $old) === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                    <option value="3rd Year" <?= uval('last_year_level', $is_edit, $user ?? [], $student ?? [], $old) === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                    <option value="4th Year" <?= uval('last_year_level', $is_edit, $user ?? [], $student ?? [], $old) === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                                    <option value="5th Year" <?= uval('last_year_level', $is_edit, $user ?? [], $student ?? [], $old) === '5th Year' ? 'selected' : '' ?>>5th Year</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Course Selection + Password -->
                <div class="step-content" data-step="5">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-google-blue text-white text-xs flex items-center justify-center">5</span>
                        Course Selection
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Preferred Course *</label>
                            <select name="preferred_course" id="preferred_course" required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                <option value="">Select Course</option>
                                <?php foreach ($bachelorCourses as $course): ?>
                                    <option value="<?= htmlspecialchars($course['code']) ?>" <?= uval('preferred_course', $is_edit, $user ?? [], $student ?? [], $old) === $course['code'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['code']) ?> - <?= htmlspecialchars($course['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Second Choice Course (Optional)</label>
                            <select name="second_course"
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                <option value="">Select Course</option>
                                <?php foreach ($bachelorCourses as $course): ?>
                                    <option value="<?= htmlspecialchars($course['code']) ?>" <?= uval('second_course', $is_edit, $user ?? [], $student ?? [], $old) === $course['code'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['code']) ?> - <?= htmlspecialchars($course['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Academic Year *</label>
                            <select name="academic_year_id" required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                <option value="">Select Academic Year</option>
                                <?php foreach ($academicYears as $ay): ?>
                                    <option value="<?= $ay['id'] ?>" <?= uval('academic_year_id', $is_edit, $user ?? [], $student ?? [], $old) == $ay['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ay['year']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Semester *</label>
                            <select name="semester" required
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                                <option value="1st Semester" <?= uval('semester', $is_edit, $user ?? [], $student ?? [], $old) === '1st Semester' ? 'selected' : '' ?>>1st Semester</option>
                                <option value="2nd Semester" <?= uval('semester', $is_edit, $user ?? [], $student ?? [], $old) === '2nd Semester' ? 'selected' : '' ?>>2nd Semester</option>
                                <option value="Summer" <?= uval('semester', $is_edit, $user ?? [], $student ?? [], $old) === 'Summer' ? 'selected' : '' ?>>Summer</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            Account Password
                        </h4>
                        <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                            <label class="text-xs font-medium text-black/50">Password <?= $is_edit ? '(leave blank to keep current)' : '*' ?></label>
                            <input type="password" name="password" <?= $is_edit ? '' : 'required' ?>
                                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                                placeholder="<?= $is_edit ? 'Leave blank to keep current' : 'Minimum 8 characters' ?>">
                        </div>
                    </div>
                </div>

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
                        <button type="submit" name="save" id="submitBtn"
                            class="px-6 py-2.5 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-full transition-colors shadow-sm active:scale-95 hidden">
                            <?= $is_edit ? 'Update Student' : 'Create Account' ?> ✓
                        </button>
                    </div>
                </div>
            </form>
        </div>

    <?php endif; ?>
</div>

<script>
    let currentStep = 1;
    const totalSteps = <?= count($step_labels) ?>;
    const stepLabels = <?= json_encode($step_labels) ?>;

    function updateStepDisplay() {
        document.querySelectorAll('.step-content').forEach(el => {
            el.classList.remove('active');
            if (parseInt(el.dataset.step) === currentStep) {
                el.classList.add('active');
            }
        });

        document.querySelectorAll('.step-dot').forEach(dot => {
            const step = parseInt(dot.dataset.step);
            dot.className = 'h-1.5 flex-1 rounded-full step-dot ' + (step <= currentStep ? 'bg-google-blue' : 'bg-black/20');
        });

        document.getElementById('currentStepNum').textContent = currentStep;
        document.getElementById('stepLabelText').textContent = stepLabels[currentStep - 1];
        document.getElementById('currentStep').value = currentStep;

        document.getElementById('backBtn').style.visibility = currentStep === 1 ? 'hidden' : 'visible';
        document.getElementById('nextBtn').classList.toggle('hidden', currentStep === totalSteps);
        document.getElementById('submitBtn').classList.toggle('hidden', currentStep !== totalSteps);
    }

    function validateStep(step) {
        const currentContent = document.querySelector(`.step-content[data-step="${step}"]`);
        if (!currentContent) return true;

        const requiredFields = currentContent.querySelectorAll('[required]');
        let isValid = true;

        currentContent.querySelectorAll('.border').forEach(el => {
            el.style.borderColor = '';
        });

        requiredFields.forEach(field => {
            if (field.disabled) return;
            if (!field.value.trim()) {
                isValid = false;
                const wrapper = field.closest('.border');
                if (wrapper) wrapper.style.borderColor = '#ef4444';
            }
        });

        return isValid;
    }

    function nextStep() {
        if (!validateStep(currentStep)) {
            alert('Please fill in all required fields marked with an asterisk (*).');
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
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
            document.getElementById('age').value = age;
        }
    }

    function toggleEducationFields() {
        const educationType = document.querySelector('input[name="education_type"]:checked')?.value || '';
        const isSeniorHigh = educationType === 'senior_high';
        const isCollege = educationType === 'college_freshman' || educationType === 'transferee';

        document.getElementById('seniorHighFields').classList.toggle('active', isSeniorHigh);
        document.getElementById('collegeFreshmanFields').classList.toggle('active', educationType === 'college_freshman');
        document.getElementById('transfereeFields').classList.toggle('active', educationType === 'transferee');

        document.querySelectorAll('.education-section:not(.active) select, .education-section:not(.active) input').forEach(el => {
            el.disabled = true;
        });
        document.querySelectorAll('.education-section.active select, .education-section.active input').forEach(el => {
            el.disabled = false;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateStepDisplay();
        toggleEducationFields();
        calculateAge();
    });
</script>

<?php require_once __DIR__ . '/../../../footer.php'; ?>
