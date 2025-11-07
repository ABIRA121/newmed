-- Seed data for MedPortal

SET FOREIGN_KEY_CHECKS = 0;

----------------------------------------
-- ✅ NEW ADMIN USER (Password: Admin12!)
----------------------------------------
INSERT INTO users 
(role, email, password_hash, full_name, phone, created_at, is_active, password_change_required) 
VALUES
('admin', 'admin@medportal.com', '$2y$10$cziYDP3N/sy1htNrq1YCC.qrlbv5/XgWpkqcQn4VYOCpQnFdrIcJe', 
'System Administrator', '+1234567890', NOW(), TRUE, FALSE);

----------------------------------------
-- ✅ Clinical Staff (unchanged)
----------------------------------------
INSERT INTO users 
(role, email, password_hash, full_name, phone, created_at, is_active, password_change_required) 
VALUES
('doctor', 'dr.smith@medportal.com', '$2y$10$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Dr. John Smith', '+1234567891', NOW(), TRUE, TRUE),
('accountant', 'accounts@medportal.com', '$2y$10$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Taylor Adams', '+1234567892', NOW(), TRUE, TRUE),
('pharmacy', 'pharmacy@medportal.com', '$2y$10$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Morgan Lee', '+1234567893', NOW(), TRUE, TRUE),
('nurse', 'nurse.jones@medportal.com', '$2y$10$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Sarah Jones', '+1234567894', NOW(), TRUE, TRUE);

----------------------------------------
-- ✅ Patient Users (unchanged)
----------------------------------------
INSERT INTO users 
(role, email, password_hash, full_name, phone, created_at, is_active, password_change_required)
VALUES
('patient', 'patient1@example.com', '$2y$10$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Alice Johnson', '+1234567895', NOW(), TRUE, TRUE),
('patient', 'patient2@example.com', '$2y$10$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Bob Wilson', '+1234567896', NOW(), TRUE, TRUE),
('patient', 'patient3@example.com', '$2y$10$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Carol Davis', '+1234567897', NOW(), TRUE, TRUE);

----------------------------------------
-- ✅ Patient Profiles (unchanged)
----------------------------------------
INSERT INTO patients 
(user_id, dob, gender, address, medical_notes, emergency_contact, insurance_info)
VALUES
(6, '1985-03-15', 'female', '123 Main St, Cityville', 'Allergic to penicillin. History of asthma.', 'John Johnson - +1234567899', 'INS123456789'),
(7, '1978-07-22', 'male', '456 Oak Ave, Townsville', 'Hypertension managed with medication.', 'Mary Wilson - +1234567888', 'INS987654321'),
(8, '1992-11-30', 'female', '789 Pine Rd, Villagetown', 'No significant medical history.', 'Tom Davis - +1234567777', 'INS456789123');

----------------------------------------
-- ✅ Sample Appointments (unchanged)
----------------------------------------
INSERT INTO appointments 
(patient_id, staff_id, scheduled_at, status, notes) 
VALUES
(6, 2, NOW() + INTERVAL 1 DAY, 'confirmed', 'Regular checkup'),
(7, 2, NOW() + INTERVAL 2 DAY, 'pending', 'Follow-up appointment'),
(8, 2, NOW() + INTERVAL 3 DAY, 'confirmed', 'Vaccination');

----------------------------------------
-- ✅ Sample Prescriptions (unchchanged)
----------------------------------------
INSERT INTO prescriptions 
(patient_id, doctor_id, consultation_notes, diagnosis, treatment_plan, medications) 
VALUES
(6, 2, 'Patient reports seasonal allergies.', 'Allergic rhinitis', 'Prescribe antihistamines and nasal spray.', 'Cetirizine 10mg daily;Fluticasone nasal spray 2x daily'),
(7, 2, 'Patient complains of chronic back pain.', 'Lower back strain', 'Recommend physiotherapy and pain management plan.', 'Ibuprofen 400mg as needed;Physiotherapy sessions');

----------------------------------------
-- ✅ Sample Bills (unchanged)
----------------------------------------
INSERT INTO patient_bills 
(patient_id, accountant_id, prescription_id, services, medication_details, subtotal, medication_total, total_amount, status, receipt_number)
VALUES
(6, 3, 1, 'Consultation fee: 50.00', 'Cetirizine (30 tablets) - 15.00;Fluticasone spray - 25.00', 50.00, 40.00, 90.00, 'paid', 'RCT-1001'),
(7, 3, 2, 'Consultation fee: 60.00', 'Ibuprofen (20 tablets) - 10.00', 60.00, 10.00, 70.00, 'pending', 'RCT-1002');

----------------------------------------
-- ✅ Sample Dispensations (unchanged)
----------------------------------------
INSERT INTO dispensations 
(bill_id, pharmacy_id, dispensed_items, confirmed_at) 
VALUES
(1, 4, 'Cetirizine 10mg (30 tablets); Fluticasone nasal spray 120 doses', NOW());

----------------------------------------
-- ✅ Sample Audit Logs (unchanged)
----------------------------------------
INSERT INTO audit_logs 
(user_id, action, ip_address, user_agent) 
VALUES
(1, 'user_login', '192.168.1.1', 'Mozilla/5.0'),
(2, 'user_login', '192.168.1.2', 'Mozilla/5.0'),
(6, 'appointment_created', '192.168.1.3', 'Mozilla/5.0');

SET FOREIGN_KEY_CHECKS = 1;
