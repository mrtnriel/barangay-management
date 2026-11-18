-- Barangay Health Outreach Management System
-- Seed Data for Testing & Development

CREATE DATABASE IF NOT EXISTS barangay_health_db;
USE barangay_health_db;

-- Clear existing data safely without foreign key conflicts
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM appointments;
DELETE FROM inventory;
DELETE FROM service_records;
DELETE FROM registrations;
DELETE FROM programs;
DELETE FROM residents;
DELETE FROM users;

ALTER TABLE appointments AUTO_INCREMENT = 1;
ALTER TABLE inventory AUTO_INCREMENT = 1;
ALTER TABLE service_records AUTO_INCREMENT = 1;
ALTER TABLE registrations AUTO_INCREMENT = 1;
ALTER TABLE programs AUTO_INCREMENT = 1;
ALTER TABLE residents AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

-- Ensure date columns support timestamp values matching schema.sql
ALTER TABLE programs MODIFY start_date DATETIME NOT NULL, MODIFY end_date DATETIME NULL;
ALTER TABLE registrations MODIFY registration_date DATETIME DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE service_records MODIFY service_date DATETIME NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. System Users (Default password: <role>123 e.g. admin123, staff123, bhw123)
INSERT INTO users (full_name, username, password, role) VALUES
('Barangay Administrator', 'admin', '$2y$10$pWp9IkHmoi1OzbWoENhPjur6ErIPHgwdpZ.KHDsl5pjGcCzXNrF..', 'Admin'),
('Barangay Staff', 'staff', '$2y$10$tPaAbG0ijC/5cYqAWsFZA.mlByGSlD/bFrCUT3mnH7lG5IsiR1Cn.', 'Staff'),
('Barangay Health Worker', 'bhw', '$2y$10$oRMwKNC9Pf.7GT3XNCHE.eROfcWhe2YkKtMFPFW2rkppVXJNojPnO', 'BHW');

-- 2. Residents
INSERT INTO residents (first_name, last_name, middle_name, birth_date, sex, address, contact_number) VALUES
('Juan', 'Dela Cruz', 'Mercado', '1985-05-12', 'Male', 'Zone 1, Barangay Sample', '0917-123-4567'),
('Maria', 'Santos', 'Garcia', '1992-08-03', 'Female', 'Zone 2, Barangay Sample', '0918-234-5678'),
('Pedro', 'Reyes', 'Villanueva', '2001-01-21', 'Male', 'Zone 3, Barangay Sample', '0919-345-6789'),
('Ana', 'Cruz', 'Mendoza', '1995-11-14', 'Female', 'Zone 1, Barangay Sample', '0920-456-7890'),
('Elena', 'Bautista', 'Lopez', '1978-04-09', 'Female', 'Zone 4, Barangay Sample', '0921-567-8901'),
('Roberto', 'Ramos', 'Aquino', '1963-12-30', 'Male', 'Zone 2, Barangay Sample', '0922-678-9012');

-- 3. Health Programs
INSERT INTO programs (program_name, description, program_type, venue, start_date, end_date, program_status) VALUES
('Community Medical Mission', 'Free medical check-ups, diagnostic services, and basic medicine distribution for all residents.', 'Medical Mission', 'Barangay Hall Ground Floor', '2026-11-15 08:00:00', '2026-11-15 17:00:00', 'Ongoing'),
('Free Circumcision Program', 'Annual community minor surgical outreach for eligible male children and youth.', 'Free Service', 'Barangay Health Center', '2026-11-20 08:00:00', '2026-11-20 16:00:00', 'Upcoming'),
('Barangay Health Consultation', 'General health consultation with visiting municipal physician and BHWs.', 'Consultation', 'Barangay Health Center', '2026-11-25 09:00:00', '2026-11-25 15:00:00', 'Upcoming'),
('Nutrition Awareness & Feeding', 'Educational seminar on maternal and child nutrition plus supplementary feeding demonstration.', 'Health Education', 'Barangay Covered Court', '2026-10-28 08:30:00', '2026-10-28 12:00:00', 'Completed');

-- 4. Program Registrations
INSERT INTO registrations (resident_id, program_id, attendance_status, registration_date) VALUES
(1, 1, 'Attended', '2026-11-15 08:30:00'),   -- Juan Dela Cruz in Community Medical Mission
(4, 1, 'Attended', '2026-11-15 09:10:00'),   -- Ana Cruz in Community Medical Mission
(3, 2, 'Registered', '2026-11-14 14:00:00'), -- Pedro Reyes in Free Circumcision Program
(2, 3, 'Registered', '2026-11-13 11:20:00'), -- Maria Santos in Barangay Health Consultation
(5, 4, 'Attended', '2026-10-28 08:15:00'),   -- Elena Bautista in Nutrition Awareness
(6, 4, 'Attended', '2026-10-28 08:45:00');   -- Roberto Ramos in Nutrition Awareness

-- 5. Service Records
INSERT INTO service_records (registration_id, service_description, service_date) VALUES
(1, 'General physical examination and prescribed maintenance medications (Amlodipine 5mg, 30 days).', '2026-11-15 09:30:00'),
(2, 'Blood pressure screening (110/70) and dispensing of pediatric vitamins for dependents.', '2026-11-15 10:15:00'),
(5, 'Attended maternal & child nutrition counseling workshop and received family dietary supplement kit.', '2026-10-28 10:00:00'),
(6, 'Senior citizen dietary assessment and random blood sugar test (FBS: 98 mg/dL - Normal).', '2026-10-28 10:45:00');

-- 6. Consultations & Appointments
INSERT INTO appointments (resident_id, appointment_date, purpose, physician_name, status) VALUES
(1, '2026-11-22 09:30:00', 'Hypertension Follow-up Checkup', 'Dr. Maria Ramos', 'Scheduled'),
(2, '2026-11-23 10:15:00', 'Prenatal Second Trimester Assessment', 'Dr. Maria Ramos', 'Scheduled'),
(3, '2026-11-19 14:00:00', 'Minor Surgical Wound Dressing', 'Dr. Maria Ramos', 'Completed'),
(5, '2026-11-24 11:00:00', 'Pediatric Vaccination Consultation', 'Dr. Maria Ramos', 'Scheduled');

-- 7. Supplies & Medicine Inventory
INSERT INTO inventory (item_name, category, stock_quantity, unit, stock_status) VALUES
('Paracetamol 500mg Tablets', 'Analgesics / Antipyretics', 450, 'tablets', 'Adequate'),
('Amoxicillin 500mg Capsules', 'Antibiotics', 120, 'capsules', 'Adequate'),
('Amlodipine 5mg Tablets', 'Cardiovascular', 80, 'tablets', 'Low Stock'),
('Metformin 500mg Tablets', 'Antidiabetic', 210, 'tablets', 'Adequate'),
('Oral Rehydration Salts (ORS)', 'Hydration / Pediatric', 25, 'sachets', 'Low Stock'),
('Sterile Gauze Bandages 4x4', 'Medical Consumables', 60, 'packs', 'Adequate'),
('Ascorbic Acid (Vitamin C) 500mg', 'Vitamins & Supplements', 300, 'tablets', 'Adequate'),
('Cotton Balls 500g', 'Medical Consumables', 0, 'packs', 'Out of Stock');
