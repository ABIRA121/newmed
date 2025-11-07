<?php

class Prescription
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO prescriptions (patient_id, doctor_id, consultation_notes, diagnosis, treatment_plan, medications)
                VALUES (:patient_id, :doctor_id, :consultation_notes, :diagnosis, :treatment_plan, :medications)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':doctor_id' => $data['doctor_id'],
            ':consultation_notes' => $data['consultation_notes'] ?? null,
            ':diagnosis' => $data['diagnosis'] ?? null,
            ':treatment_plan' => $data['treatment_plan'] ?? null,
            ':medications' => $data['medications'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getByDoctor($doctorId)
    {
        $sql = "SELECT p.*, u.full_name AS patient_name
                FROM prescriptions p
                INNER JOIN users u ON u.id = p.patient_id
                WHERE p.doctor_id = :doctor_id
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':doctor_id' => $doctorId]);
        return $stmt->fetchAll();
    }

    public function getAll()
    {
        $sql = "SELECT p.*, patient.full_name AS patient_name, doctor.full_name AS doctor_name
                FROM prescriptions p
                INNER JOIN users patient ON patient.id = p.patient_id
                INNER JOIN users doctor ON doctor.id = p.doctor_id
                ORDER BY p.created_at DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function findById($id)
    {
        $sql = "SELECT p.*, patient.full_name AS patient_name, doctor.full_name AS doctor_name
                FROM prescriptions p
                INNER JOIN users patient ON patient.id = p.patient_id
                INNER JOIN users doctor ON doctor.id = p.doctor_id
                WHERE p.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getUnbilled()
    {
        $sql = "SELECT p.*, patient.full_name AS patient_name
                FROM prescriptions p
                INNER JOIN users patient ON patient.id = p.patient_id
                LEFT JOIN patient_bills b ON b.prescription_id = p.id
                WHERE b.id IS NULL OR b.status != 'paid'
                ORDER BY p.created_at DESC";

        return $this->db->query($sql)->fetchAll();
    }
}


