-- =======================================================
-- CampusFix - Campus Complaint Tracking System
-- Database Schema Definition & Demo Sample Data
-- =======================================================

CREATE DATABASE IF NOT EXISTS campusfix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campusfix;

-- -------------------------------------------------------
-- 1. Table structure for table: users
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(30) UNIQUE NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Table structure for table: complaints
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_code VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category ENUM(
        'Wi-Fi / Internet',
        'Electrical',
        'Classroom',
        'Lab Equipment',
        'Cleanliness',
        'Water Supply',
        'Furniture',
        'Washroom',
        'Security',
        'Other'
    ) NOT NULL,
    location VARCHAR(150) NOT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    description TEXT NOT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved') NOT NULL DEFAULT 'Pending',
    resolution_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_complaints_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. Realistic Demo Data (DEMO ONLY)
-- Passwords:
-- Admin: admin@campusfix.edu / admin123
-- Students: password123
-- -------------------------------------------------------

-- Insert Administrator (ID: 1)
INSERT INTO users (id, full_name, student_id, email, password, role) VALUES
(1, 'System Administrator', NULL, 'admin@campusfix.edu', '$2y$10$fjDlWQKYMJ67a2ueHJSKROY2QdWnozgdRE8X1D8f66/.5kvD7BPPe', 'admin')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), role=VALUES(role);

-- Insert Student Demo Accounts (IDs: 2, 3, 4)
-- Hash below corresponds to 'password123'
INSERT INTO users (id, full_name, student_id, email, password, role) VALUES
(2, 'Nadia Rahman', 'STU-2024-001', 'nadia@campus.edu', '$2y$10$0JVMaGc7T4VSPN5qAquxRuRNYfyCrhSW.pNv0n2CcrY6WIK2etV0K', 'student'),
(3, 'Arif Hasan', 'STU-2024-002', 'arif@campus.edu', '$2y$10$0JVMaGc7T4VSPN5qAquxRuRNYfyCrhSW.pNv0n2CcrY6WIK2etV0K', 'student'),
(4, 'Demo Student', 'STU-2024-003', 'demo@campus.edu', '$2y$10$0JVMaGc7T4VSPN5qAquxRuRNYfyCrhSW.pNv0n2CcrY6WIK2etV0K', 'student')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), student_id=VALUES(student_id), password=VALUES(password);

-- Insert Realistic Sample Complaints across Categories, Priorities, and Statuses
INSERT INTO complaints (id, complaint_code, user_id, title, category, location, priority, description, status, resolution_note) VALUES
(1, 'CMP-0001', 2, 'Wi-Fi unavailable in CSE Lab 2', 'Wi-Fi / Internet', 'Academic Building 2, CSE Lab 2', 'High', 'Students and lab instructors cannot connect to the Wi-Fi access point in CSE Lab 2 since this morning. Network SSID is not broadcasting.', 'Pending', NULL),
(2, 'CMP-0002', 2, 'Projector not displaying', 'Classroom', 'Room 304, Lecture Building', 'Medium', 'The overhead ceiling projector in Room 304 has an orange blinking lamp indicator and fails to project HDMI input from teacher laptop.', 'Resolved', 'Replaced the projector bulb and verified HDMI signal transmission with IT technician on Sep 21.'),
(3, 'CMP-0003', 3, 'Broken fan in Room 402', 'Electrical', 'Science Complex, Room 402', 'Medium', 'Ceiling fan #3 makes a loud squeaking sound and wobbles violently when set to high speed.', 'In Progress', 'Maintenance team assigned ticket #EL-402; motor bearing replacement scheduled.'),
(4, 'CMP-0004', 3, 'Water filter not working', 'Water Supply', 'Ground Floor, Library Annex', 'High', 'The main drinking water cooler and filter dispensing unit has stopped flowing water. Students have no drinking water on this floor.', 'Pending', NULL),
(5, 'CMP-0005', 4, 'Keyboard missing keys', 'Lab Equipment', 'Software Engineering Lab, PC 14', 'Low', 'Desktop computer station 14 has several damaged and missing keys (Enter, Space, Ctrl) making coding assignments impossible.', 'Resolved', 'Replaced mechanical keyboard with a new standard USB keyboard from lab inventory.'),
(6, 'CMP-0006', 4, 'Washroom light broken', 'Washroom', '3rd Floor Male Washroom, East Wing', 'Medium', 'Both overhead LED tube lights in the 3rd-floor east wing washroom are completely dark. Urgent repair needed for safety.', 'Pending', NULL),
(7, 'CMP-0007', 3, 'Damaged bench in Room 101', 'Furniture', 'Main Building, Room 101', 'Low', 'Two wooden benches in row 4 have loose screws and splintered wood edges that can tear clothes.', 'In Progress', 'Carpentry workshop notified. Work scheduled for weekend maintenance.'),
(8, 'CMP-0008', 2, 'Staircase exit door lock jammed', 'Security', 'Emergency Staircase B, Level 2', 'High', 'Emergency exit door lock mechanism is jammed shut. This creates a severe fire safety hazard during emergencies.', 'Pending', NULL)
ON DUPLICATE KEY UPDATE 
    title=VALUES(title), 
    category=VALUES(category), 
    location=VALUES(location), 
    priority=VALUES(priority), 
    description=VALUES(description), 
    status=VALUES(status), 
    resolution_note=VALUES(resolution_note);
