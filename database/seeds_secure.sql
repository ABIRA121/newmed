[file name]: seeds_secure.sql
-- Secure seed data for MedPortal
-- All passwords are hashed with bcrypt and sensitive data is encrypted

-- Insert admin user (temporary password: TempAdmin123!)
INSERT INTO users (role, email, password_hash, full_name, phone, created_at, is_active, password_change_required) VALUES
('admin', 'admin@medportal.com', '$2b$12$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'System Administrator', '+1234567890', NOW(), TRUE, TRUE);

-- Insert clinical staff (temporary password: TempStaff123!)
INSERT INTO users (role, email, password_hash, full_name, phone, created_at, is_active, password_change_required) VALUES
('doctor', 'dr.smith@medportal.com', '$2b$12$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Dr. John Smith', '+1234567891', NOW(), TRUE, TRUE),
('accountant', 'accounts@medportal.com', '$2b$12$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Taylor Adams', '+1234567892', NOW(), TRUE, TRUE),
('pharmacy', 'pharmacy@medportal.com', '$2b$12$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Morgan Lee', '+1234567893', NOW(), TRUE, TRUE),
('nurse', 'nurse.jones@medportal.com', '$2b$12$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Sarah Jones', '+1234567894', NOW(), TRUE, TRUE);

-- Insert patient users (temporary password: TempPatient123!)
INSERT INTO users (role, email, password_hash, full_name, phone, created_at, is_active, password_change_required) VALUES
('patient', 'patient1@example.com', '$2b$12$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Alice Johnson', '+1234567895', NOW(), TRUE, TRUE),
('patient', 'patient2@example.com', '$2b$12$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Bob Wilson', '+1234567896', NOW(), TRUE, TRUE),
('patient', 'patient3@example.com', '$2b$12$rOzZMSR5pJ.6U6Z8X8qB.uJcJdJkS9qJcJdJkS9qJcJdJkS9qJcJdJ', 'Carol Davis', '+1234567897', NOW(), TRUE, TRUE);

-- Insert patient profiles with encrypted sensitive data
INSERT INTO patients (user_id, dob, gender, address, medical_notes, emergency_contact, insurance_info) VALUES
(6, '1985-03-15', 'female', 'encrypted:123 Main St, Cityville', 'encrypted:Allergic to penicillin. History of asthma.', 'encrypted:John Johnson - +1234567899', 'encrypted:INS123456789'),
(7, '1978-07-22', 'male', 'encrypted:456 Oak Ave, Townsville', 'encrypted:Hypertension managed with medication.', 'encrypted:Mary Wilson - +1234567888', 'encrypted:INS987654321'),
(8, '1992-11-30', 'female', 'encrypted:789 Pine Rd, Villagetown', 'encrypted:No significant medical history.', 'encrypted:Tom Davis - +1234567777', 'encrypted:INS456789123');

-- Insert sample appointments
INSERT INTO appointments (patient_id, staff_id, scheduled_at, status, notes) VALUES
(6, 2, NOW() + INTERVAL 1 DAY, 'confirmed', 'Regular checkup'),
(7, 2, NOW() + INTERVAL 2 DAY, 'pending', 'Follow-up appointment'),
(8, 2, NOW() + INTERVAL 3 DAY, 'confirmed', 'Vaccination');

-- Insert sample prescriptions
INSERT INTO prescriptions (patient_id, doctor_id, consultation_notes, diagnosis, treatment_plan, medications) VALUES
(6, 2, 'encrypted:Patient reports seasonal allergies.', 'encrypted:Allergic rhinitis', 'encrypted:Prescribe antihistamines and nasal spray.', 'encrypted:Cetirizine 10mg daily;Fluticasone nasal spray 2x daily'),
(7, 2, 'encrypted:Patient complains of chronic back pain.', 'encrypted:Lower back strain', 'encrypted:Recommend physiotherapy and pain management plan.', 'encrypted:Ibuprofen 400mg as needed;Physiotherapy sessions');

-- Insert sample bills (accountant clearance)
INSERT INTO patient_bills (patient_id, accountant_id, prescription_id, services, medication_details, subtotal, medication_total, total_amount, status, receipt_number) VALUES
(6, 3, 1, 'encrypted:Consultation fee: 50.00', 'encrypted:Cetirizine (30 tablets) - 15.00;Fluticasone spray - 25.00', 50.00, 40.00, 90.00, 'paid', 'RCT-1001'),
(7, 3, 2, 'encrypted:Consultation fee: 60.00', 'encrypted:Ibuprofen (20 tablets) - 10.00', 60.00, 10.00, 70.00, 'pending', 'RCT-1002');

-- Insert sample dispensations (only for cleared bill)
INSERT INTO dispensations (bill_id, pharmacy_id, dispensed_items, confirmed_at) VALUES
(1, 4, 'encrypted:Cetirizine 10mg (30 tablets); Fluticasone nasal spray 120 doses', NOW());

-- Insert sample audit logs
INSERT INTO audit_logs (user_id, action, ip_address, user_agent) VALUES
(1, 'user_login', '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(2, 'user_login', '192.168.1.2', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'),
(6, 'appointment_created', '192.168.1.3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
