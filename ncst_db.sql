-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 04, 2026 at 01:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ncst_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL,
  `year` varchar(20) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `status` enum('active','inactive','enrollment_open','enrollment_closed') DEFAULT 'inactive',
  `enrollment_start` date DEFAULT NULL,
  `enrollment_end` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `year`, `semester`, `status`, `enrollment_start`, `enrollment_end`, `created_at`) VALUES
(1, '2026-2027', '1st Semester', 'enrollment_open', NULL, NULL, '2026-07-04 09:12:54'),
(2, '2026-2027', '2nd Semester', 'inactive', NULL, NULL, '2026-07-04 09:12:54'),
(3, '2027-2028', '1st Semester', 'inactive', NULL, NULL, '2026-07-04 09:12:54');

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `birthday` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Annulled') DEFAULT 'Single',
  `nationality` varchar(100) DEFAULT 'Filipino',
  `religion` varchar(100) DEFAULT NULL,
  `birth_place` varchar(255) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `education_type` enum('freshman','transferee','shiftee') DEFAULT 'freshman',
  `highschool_name` varchar(255) DEFAULT NULL,
  `highschool_address` varchar(255) DEFAULT NULL,
  `shs_strand` varchar(100) DEFAULT NULL,
  `shs_track` varchar(100) DEFAULT NULL,
  `year_graduated` varchar(20) DEFAULT NULL,
  `lrn` varchar(20) DEFAULT NULL,
  `previous_college` varchar(255) DEFAULT NULL,
  `previous_course` varchar(100) DEFAULT NULL,
  `last_year_level` varchar(20) DEFAULT NULL,
  `preferred_course` varchar(100) NOT NULL,
  `second_course` varchar(100) DEFAULT NULL,
  `semester` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected','revision') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 14 day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `gender`, `civil_status`, `nationality`, `religion`, `birth_place`, `email`, `contact_number`, `home_address`, `province`, `city`, `barangay`, `zip_code`, `father_name`, `mother_name`, `guardian_name`, `guardian_contact`, `guardian_relationship`, `education_type`, `highschool_name`, `highschool_address`, `shs_strand`, `shs_track`, `year_graduated`, `lrn`, `previous_college`, `previous_course`, `last_year_level`, `preferred_course`, `second_course`, `semester`, `academic_year`, `status`, `rejection_reason`, `reviewed_by`, `reviewed_at`, `user_id`, `created_at`, `updated_at`, `expires_at`) VALUES
(1, 'Ebony', 'Kasimir Chaney', 'Dotson', 'Minim voluptatem mol', '2005-07-06', 'Other', 'Married', 'Dolorem quod adipisi', 'Ipsum aliqua Dolor', 'Animi libero alias', 'lesibedym@mailinator.com', '+1 (112) 277-1977', 'Sit distinctio Qua', 'Et non reprehenderit', 'Ea repellendus Dolo', 'Nemo sint enim aute', '99569', 'Erasmus Knapp', 'Holly Walton', 'Charissa Riddle', '+1 (387) 517-4498', 'Other Relative', 'transferee', '', '', '', NULL, '', '', 'Omnis id voluptatum', 'Reprehenderit ut qu', '1st Year', 'BSBA', 'BSCRIM', '1st Semester', '2026-2027', 'approved', NULL, 1, '2026-07-04 10:40:56', 3, '2026-07-04 09:57:43', '2026-07-04 10:40:56', '2026-07-18 09:57:43'),
(2, 'Student', '', 'NCST', '', '2002-05-15', 'Male', 'Single', 'Filipino', '', '', 'student@ncst.edu.ph', '+639223456789', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 'freshman', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', '', 'approved', NULL, NULL, NULL, 2, '2026-07-04 10:34:23', '2026-07-04 10:34:23', '2026-07-18 10:34:23');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_documents`
--

CREATE TABLE `applicant_documents` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `document_type` enum('psa_birth_certificate','form_138','good_moral','certificate_of_graduation','id_photo_2x2','valid_id','tor','honorable_dismissal','other') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `status` enum('pending','submitted','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 14 day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `code`, `name`, `description`, `department`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 'Study of computer systems, software development, and network management', 'Computer Studies', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(2, 'BSCS', 'Bachelor of Science in Computer Science', 'Study of computation, algorithms, and programming', 'Computer Studies', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(3, 'BSBA', 'Bachelor of Science in Business Administration', 'Study of business management and entrepreneurship', 'Business', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(4, 'BSED', 'Bachelor of Secondary Education', 'Teaching degree for secondary school levels', 'Education', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(5, 'BEED', 'Bachelor of Elementary Education', 'Teaching degree for elementary school levels', 'Education', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(6, 'BSHM', 'Bachelor of Science in Hospitality Management', 'Study of hotel and restaurant management', 'Hospitality', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(7, 'BSTM', 'Bachelor of Science in Tourism Management', 'Study of tourism and travel management', 'Tourism', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(8, 'BSCRIM', 'Bachelor of Science in Criminology', 'Study of criminal justice and law enforcement', 'Criminal Justice', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `education_type` enum('freshman','transferee','shiftee') DEFAULT 'freshman',
  `highschool_name` varchar(255) DEFAULT NULL,
  `highschool_address` varchar(255) DEFAULT NULL,
  `shs_strand` varchar(100) DEFAULT NULL,
  `shs_track` varchar(100) DEFAULT NULL,
  `year_graduated` varchar(20) DEFAULT NULL,
  `lrn` varchar(20) DEFAULT NULL,
  `previous_college` varchar(255) DEFAULT NULL,
  `previous_course` varchar(100) DEFAULT NULL,
  `last_year_level` varchar(20) DEFAULT NULL,
  `preferred_course` varchar(100) DEFAULT NULL,
  `second_course` varchar(100) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `enrollment_status` enum('pending','enrolled','not_enrolled','graduated','dropped') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `father_name`, `mother_name`, `guardian_name`, `guardian_contact`, `guardian_relationship`, `education_type`, `highschool_name`, `highschool_address`, `shs_strand`, `shs_track`, `year_graduated`, `lrn`, `previous_college`, `previous_course`, `last_year_level`, `preferred_course`, `second_course`, `semester`, `academic_year`, `enrollment_status`, `created_at`, `updated_at`) VALUES
(1, 2, '', '', '', '', '', 'freshman', '', '', '', '', '', '', '', '', '', '', '', '', '', 'pending', '2026-07-04 10:34:53', '2026-07-04 10:34:53'),
(2, 3, 'Erasmus Knapp', 'Holly Walton', 'Charissa Riddle', '+1 (387) 517-4498', 'Other Relative', 'transferee', '', '', '', NULL, '', '', 'Omnis id voluptatum', 'Reprehenderit ut qu', '1st Year', 'BSBA', 'BSCRIM', '1st Semester', '2026-2027', 'pending', '2026-07-04 10:40:56', '2026-07-04 10:40:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `birthday` date NOT NULL,
  `age` int(3) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Annulled') DEFAULT 'Single',
  `nationality` varchar(100) DEFAULT 'Filipino',
  `religion` varchar(100) DEFAULT NULL,
  `birth_place` varchar(255) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','student','registrar') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive','pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `deleted_at` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `age`, `gender`, `civil_status`, `nationality`, `religion`, `birth_place`, `email`, `contact_number`, `home_address`, `province`, `city`, `barangay`, `zip_code`, `password`, `role`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Admin', NULL, 'NCST', NULL, '1990-01-01', NULL, 'Male', 'Single', 'Filipino', NULL, NULL, 'admin@ncst.edu.ph', '+639123456789', NULL, NULL, NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NULL, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(2, 'jason', 'Verzosa', 'Begornia', '', '2002-05-15', NULL, 'Male', 'Single', 'Filipino', '', '', 'student@ncst.edu.ph', '+639223456789', '', '', '', '', '', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active', NULL, '2026-07-04 09:12:54', '2026-07-04 10:36:15'),
(3, 'Ebony', 'Kasimir Chaney', 'Dotson', 'Minim voluptatem mol', '2005-07-06', NULL, 'Other', 'Married', 'Dolorem quod adipisi', 'Ipsum aliqua Dolor', 'Animi libero alias', 'lesibedym@mailinator.com', '+1 (112) 277-1977', 'Sit distinctio Qua', 'Et non reprehenderit', 'Ea repellendus Dolo', 'Nemo sint enim aute', '99569', '$2y$10$fBnDlCpm6j5tm7Gvnjcw9OH5FZ9fMk.o/0EYU.RbzKqrAQTFNIr96', 'student', 'active', 1, '2026-07-04 10:40:56', '2026-07-04 10:40:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year_semester` (`year`,`semester`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `status` (`status`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `document_type` (`document_type`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  ADD CONSTRAINT `applicant_documents_applicant_id_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

--
-- Table structure for table `shs_tracks`
--

CREATE TABLE `shs_tracks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `track_name` varchar(100) NOT NULL,
  `track_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `track_code` (`track_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shs_tracks`
--

INSERT INTO `shs_tracks` (`id`, `track_name`, `track_code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Science, Technology, Engineering, and Mathematics', 'STEM', 'Science, Technology, Engineering, and Mathematics strand focuses on advanced science and math courses.', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(2, 'Humanities and Social Sciences', 'HUMSS', 'Humanities and Social Sciences strand focuses on literature, arts, and social sciences.', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(3, 'Accountancy, Business and Management', 'ABM', 'Accountancy, Business and Management strand focuses on business and entrepreneurship.', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(4, 'Technical-Vocational Livelihood', 'TVL', 'Technical-Vocational Livelihood strand focuses on practical skills and technical courses.', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(5, 'General Academic', 'GA', 'General Academic strand provides a balanced mix of academic subjects.', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(6, 'Sports', 'Sports', 'Sports strand focuses on physical education and sports-related activities.', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(7, 'Arts and Design', 'Arts', 'Arts and Design strand focuses on creative arts and design disciplines.', 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54');

--
-- Table structure for table `document_paths`
--

CREATE TABLE `document_paths` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path_key` varchar(100) NOT NULL,
  `path_value` varchar(500) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `path_key` (`path_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_paths`
--

INSERT INTO `document_paths` (`id`, `path_key`, `path_value`, `description`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'applicant_photo', '/assets/uploads/images/applicants/', 'Applicant 2x2 ID photo upload path', 1, 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(2, 'applicant_document', '/assets/uploads/documents/applicants/', 'Applicant document upload path (PSA, Form 138, etc.)', 1, 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(3, 'student_photo', '/assets/uploads/images/students/', 'Student ID photo upload path', 1, 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54'),
(4, 'student_document', '/assets/uploads/documents/students/', 'Student document upload path', 1, 1, '2026-07-04 09:12:54', '2026-07-04 09:12:54');
