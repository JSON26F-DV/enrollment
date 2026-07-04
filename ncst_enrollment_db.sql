-- NCST Enrollment System Database Schema
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 04, 2026

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
-- Table structure for table `users` (expanded with all personal data)
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  
  -- Extended Personal Information (moved from students table)
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
  
  -- Contact Information
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  
  -- Account Info
  `password` varchar(255) NOT NULL,
  
  -- Role & Status
  `role` enum('admin','staff','student','registrar') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive','pending','approved','rejected') NOT NULL DEFAULT 'pending',
  
  -- Metadata
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `birthday`, `gender`, `contact_number`, `email`, `password`, `role`, `status`) VALUES
(1, 'Admin', 'NCST', '1990-01-01', 'Male', '+639123456789', 'admin@ncst.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
(2, 'Student', 'NCST', '2002-05-15', 'Male', '+639223456789', 'student@ncst.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `students` (additional student data)
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  
  -- Parent/Guardian Information
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  
  -- Educational Background
  `education_type` enum('freshman','transferee','shiftee') DEFAULT 'freshman',
  `highschool_name` varchar(255) DEFAULT NULL,
  `highschool_address` varchar(255) DEFAULT NULL,
  `shs_strand` varchar(100) DEFAULT NULL,
  `shs_track` varchar(100) DEFAULT NULL,
  `year_graduated` varchar(20) DEFAULT NULL,
  `lrn` varchar(20) DEFAULT NULL,
  
  -- Transferee specific
  `previous_college` varchar(255) DEFAULT NULL,
  `previous_course` varchar(100) DEFAULT NULL,
  `last_year_level` varchar(20) DEFAULT NULL,
  
  -- Course Selection
  `preferred_course` varchar(100) DEFAULT NULL,
  `second_course` varchar(100) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  
  -- Enrollment Status
  `enrollment_status` enum('pending','enrolled','not_enrolled','graduated','dropped') DEFAULT 'pending',
  
  -- Timestamps
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `students_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicants` (pending applications)
--

CREATE TABLE `applicants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  
  -- Personal Information
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
  
  -- Contact Information
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  
  -- Parent/Guardian Information
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  
  -- Educational Background
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
  
  -- Course Selection
  `preferred_course` varchar(100) NOT NULL,
  `second_course` varchar(100) DEFAULT NULL,
  `semester` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  
  -- Application Status
  `status` enum('pending','approved','rejected','revision') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  
  -- User account created after approval
  `user_id` int(11) DEFAULT NULL,
  
  -- Timestamps
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (CURRENT_TIMESTAMP + INTERVAL 14 DAY),
  
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_documents`
--

CREATE TABLE `applicant_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `document_type` enum(
    'psa_birth_certificate',
    'form_138',
    'good_moral',
    'certificate_of_graduation',
    'id_photo_2x2',
    'valid_id',
    'tor',
    'honorable_dismissal',
    'other'
  ) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (CURRENT_TIMESTAMP + INTERVAL 14 DAY),
  
  PRIMARY KEY (`id`),
  KEY `applicant_id` (`applicant_id`),
  KEY `document_type` (`document_type`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `applicant_documents_applicant_id_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Sample courses data
--

INSERT INTO `courses` (`code`, `name`, `description`, `department`) VALUES
('BSIT', 'Bachelor of Science in Information Technology', 'Study of computer systems, software development, and network management', 'Computer Studies'),
('BSCS', 'Bachelor of Science in Computer Science', 'Study of computation, algorithms, and programming', 'Computer Studies'),
('BSBA', 'Bachelor of Science in Business Administration', 'Study of business management and entrepreneurship', 'Business'),
('BSED', 'Bachelor of Secondary Education', 'Teaching degree for secondary school levels', 'Education'),
('BEED', 'Bachelor of Elementary Education', 'Teaching degree for elementary school levels', 'Education'),
('BSHM', 'Bachelor of Science in Hospitality Management', 'Study of hotel and restaurant management', 'Hospitality'),
('BSTM', 'Bachelor of Science in Tourism Management', 'Study of tourism and travel management', 'Tourism'),
('BSCRIM', 'Bachelor of Science in Criminology', 'Study of criminal justice and law enforcement', 'Criminal Justice');

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` varchar(20) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `status` enum('active','inactive','enrollment_open','enrollment_closed') DEFAULT 'inactive',
  `enrollment_start` date DEFAULT NULL,
  `enrollment_end` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_semester` (`year`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Sample academic year data
--

INSERT INTO `academic_years` (`year`, `semester`, `status`) VALUES
('2026-2027', '1st Semester', 'enrollment_open'),
('2026-2027', '2nd Semester', 'inactive'),
('2027-2028', '1st Semester', 'inactive');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;