<?php

class Dispensation
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO dispensations (bill_id, pharmacy_id, dispensed_items, confirmed_at)
                VALUES (:bill_id, :pharmacy_id, :dispensed_items, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':bill_id' => $data['bill_id'],
            ':pharmacy_id' => $data['pharmacy_id'],
            ':dispensed_items' => $data['dispensed_items'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function hasDispensation($billId)
    {
        $sql = "SELECT COUNT(*) as count FROM dispensations WHERE bill_id = :bill_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':bill_id' => $billId]);
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 0;
    }

    public function getByPharmacy($pharmacyId)
    {
        $sql = "SELECT d.*, b.receipt_number, b.total_amount, patient.full_name AS patient_name
                FROM dispensations d
                INNER JOIN patient_bills b ON b.id = d.bill_id
                INNER JOIN users patient ON patient.id = b.patient_id
                WHERE d.pharmacy_id = :pharmacy_id
                ORDER BY d.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pharmacy_id' => $pharmacyId]);
        return $stmt->fetchAll();
    }
}


