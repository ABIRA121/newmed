<?php

class PatientBill
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO patient_bills (patient_id, accountant_id, prescription_id, services, medication_details, subtotal, medication_total, total_amount, status, receipt_number)
                VALUES (:patient_id, :accountant_id, :prescription_id, :services, :medication_details, :subtotal, :medication_total, :total_amount, :status, :receipt_number)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':accountant_id' => $data['accountant_id'],
            ':prescription_id' => $data['prescription_id'] ?? null,
            ':services' => $data['services'] ?? null,
            ':medication_details' => $data['medication_details'] ?? null,
            ':subtotal' => $data['subtotal'] ?? 0,
            ':medication_total' => $data['medication_total'] ?? 0,
            ':total_amount' => $data['total_amount'] ?? 0,
            ':status' => $data['status'] ?? 'pending',
            ':receipt_number' => $data['receipt_number'] ?? $this->generateReceiptNumber(),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function markAsPaid($billId)
    {
        $sql = "UPDATE patient_bills SET status = 'paid' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $billId]);
    }

    public function findById($billId)
    {
        $sql = "SELECT b.*, patient.full_name AS patient_name, accountant.full_name AS accountant_name
                FROM patient_bills b
                INNER JOIN users patient ON patient.id = b.patient_id
                INNER JOIN users accountant ON accountant.id = b.accountant_id
                LEFT JOIN prescriptions p ON p.id = b.prescription_id
                WHERE b.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $billId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getByAccountant($accountantId)
    {
        $sql = "SELECT b.*, patient.full_name AS patient_name
                FROM patient_bills b
                INNER JOIN users patient ON patient.id = b.patient_id
                WHERE b.accountant_id = :accountant_id
                ORDER BY b.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':accountant_id' => $accountantId]);
        return $stmt->fetchAll();
    }

    public function getPaidBillsAwaitingDispensation()
    {
        $sql = "SELECT b.*, patient.full_name AS patient_name
                FROM patient_bills b
                INNER JOIN users patient ON patient.id = b.patient_id
                LEFT JOIN dispensations d ON d.bill_id = b.id
                WHERE b.status = 'paid' AND d.id IS NULL
                ORDER BY b.created_at ASC";

        return $this->db->query($sql)->fetchAll();
    }

    public function getBillsByPatient($patientId)
    {
        $sql = "SELECT * FROM patient_bills WHERE patient_id = :patient_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function generateReceiptNumber()
    {
        return 'RCT-' . strtoupper(bin2hex(random_bytes(3))) . '-' . date('His');
    }
}


