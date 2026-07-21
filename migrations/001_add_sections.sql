-- Migration: Add Sections Table and Section Assignment
-- Created: 2026-07-21

-- Create sections table
CREATE TABLE `sections` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `section_name` VARCHAR(20) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `section_name` (`section_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sections table with 12 sections
-- M1-M4: Morning (7:00 AM - 12:00 PM)
-- A1-A4: Afternoon (12:00 PM - 5:00 PM)
-- E1-E4: Evening (5:00 PM - 10:00 PM)

INSERT INTO `sections` (`section_name`, `start_time`, `end_time`) VALUES
('M1', '07:00:00', '12:00:00'),
('M2', '07:00:00', '12:00:00'),
('M3', '07:00:00', '12:00:00'),
('M4', '07:00:00', '12:00:00'),
('A1', '12:00:00', '17:00:00'),
('A2', '12:00:00', '17:00:00'),
('A3', '12:00:00', '17:00:00'),
('A4', '12:00:00', '17:00:00'),
('E1', '17:00:00', '22:00:00'),
('E2', '17:00:00', '22:00:00'),
('E3', '17:00:00', '22:00:00'),
('E4', '17:00:00', '22:00:00');

-- Add section_id to students table
ALTER TABLE `students` 
    ADD COLUMN `section_id` INT(11) DEFAULT NULL AFTER `enrollment_status`,
    ADD CONSTRAINT `students_section_id_fk` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL;

-- Create index for faster section lookups
CREATE INDEX `idx_students_section_id` ON `students`(`section_id`);
