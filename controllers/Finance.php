<?php

require_once __DIR__ . '/../models/Sql.php';

class Finance
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
    }

    // --- Invoices Methods ---

    public function getAllInvoices()
    {
        return $this->sql->getAll("
            SELECT i.*, c.name as client_name, p.name as project_name 
            FROM invoices i 
            JOIN clients c ON i.client_id = c.id 
            JOIN projects p ON i.project_id = p.id 
            ORDER BY i.created_at DESC
        ");
    }

    public function getInvoice($id)
    {
        $invoice = $this->sql->getId("
            SELECT i.*, 
                   c.name as client_name, c.email as client_email, c.phone as client_phone, c.address as client_address, 
                   p.name as project_name 
            FROM invoices i 
            JOIN clients c ON i.client_id = c.id 
            JOIN projects p ON i.project_id = p.id
            WHERE i.id = :id
        ", ['id' => $id]);

        if ($invoice) {
            $invoice['services'] = $this->sql->getAll(
                "SELECT * FROM services WHERE project_id = :project_id", 
                ['project_id' => $invoice['project_id']]
            );
        }

        return $invoice;
    }

    public function createInvoice($data)
    {
        $id = $this->sql->create(
            "INSERT INTO invoices (project_id, client_id, amount, due_date, status, notes) 
             VALUES (:project_id, :client_id, :amount, :due_date, :status, :notes)",
            [
                ':project_id' => (int)$data['project_id'],
                ':client_id' => (int)$data['client_id'],
                ':amount' => (float)$data['amount'],
                ':due_date' => $data['due_date'] ?? null,
                ':status' => $data['status'] ?? 'draft',
                ':notes' => htmlspecialchars($data['notes'] ?? '')
            ]
        );
        return ['success' => (bool)$id, 'id' => $id];
    }

    public function updateInvoice($id, $data)
    {
        $result = $this->sql->update(
            "UPDATE invoices SET project_id=:project_id, client_id=:client_id, amount=:amount, due_date=:due_date, status=:status, notes=:notes WHERE id=:id",
            [
                ':project_id' => (int)$data['project_id'],
                ':client_id' => (int)$data['client_id'],
                ':amount' => (float)$data['amount'],
                ':due_date' => $data['due_date'] ?? null,
                ':status' => $data['status'] ?? 'draft',
                ':notes' => htmlspecialchars($data['notes'] ?? ''),
                ':id' => $id
            ]
        );
        return ['success' => $result];
    }

    public function deleteInvoice($id)
    {
        $result = $this->sql->delete("DELETE FROM invoices WHERE id = :id", [':id' => $id]);
        return ['success' => $result];
    }

    // --- Payments Methods ---

    public function getAllPayments()
    {
        return $this->sql->getAll("
            SELECT pay.*, c.name as client_name, p.name as project_name 
            FROM payments pay 
            JOIN clients c ON pay.client_id = c.id 
            JOIN projects p ON pay.project_id = p.id 
            ORDER BY pay.created_at DESC
        ");
    }

    public function recordPayment($data)
    {
        $pdo = $this->sql->getPdo();
        
        try {
            $pdo->beginTransaction();

            // 1. Insert Payment
            $stmt = $pdo->prepare("
                INSERT INTO payments (project_id, client_id, invoice_id, amount, payment_date, payment_method, notes) 
                VALUES (:project_id, :client_id, :invoice_id, :amount, :payment_date, :payment_method, :notes)
            ");
            
            $stmt->execute([
                ':project_id' => (int)$data['project_id'],
                ':client_id' => (int)$data['client_id'],
                ':invoice_id' => !empty($data['invoice_id']) ? (int)$data['invoice_id'] : null,
                ':amount' => (float)$data['amount'],
                ':payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                ':payment_method' => $data['payment_method'] ?? 'transfer',
                ':notes' => htmlspecialchars($data['notes'] ?? '')
            ]);

            $paymentId = $pdo->lastInsertId();

            // 2. Update Project Remaining Amount
            $stmtUpdateProject = $pdo->prepare("
                UPDATE projects 
                SET remaining_amount = total_cost - (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE project_id = :project_id)
                WHERE id = :project_id_update
            ");
            $stmtUpdateProject->execute([
                ':project_id' => (int)$data['project_id'],
                ':project_id_update' => (int)$data['project_id']
            ]);

            // 3. If linked to an invoice, potentially update status
            if (!empty($data['invoice_id'])) {
                $stmtUpdateInvoice = $pdo->prepare("
                    UPDATE invoices SET status = 'paid' WHERE id = :invoice_id
                ");
                $stmtUpdateInvoice->execute([':invoice_id' => (int)$data['invoice_id']]);
            }

            $pdo->commit();
            return ['success' => true, 'id' => $paymentId];

        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updatePayment($id, $data)
    {
        // Triggers in the database will automatically re-calculate the project remaining amount upon UPDATE
        $result = $this->sql->update(
            "UPDATE payments SET project_id=:project_id, client_id=:client_id, invoice_id=:invoice_id, amount=:amount, payment_date=:payment_date, payment_method=:payment_method, notes=:notes WHERE id=:id",
            [
                ':project_id' => (int)$data['project_id'],
                ':client_id' => (int)$data['client_id'],
                ':invoice_id' => !empty($data['invoice_id']) ? (int)$data['invoice_id'] : null,
                ':amount' => (float)$data['amount'],
                ':payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                ':payment_method' => $data['payment_method'] ?? 'transfer',
                ':notes' => htmlspecialchars($data['notes'] ?? ''),
                ':id' => $id
            ]
        );
        return ['success' => $result];
    }

    public function deletePayment($id)
    {
        $pdo = $this->sql->getPdo();
        $payment = $this->sql->getId("SELECT project_id, invoice_id FROM payments WHERE id = :id", [':id' => $id]);
        
        if (!$payment) return ['success' => false, 'error' => 'Payment not found'];

        try {
            $pdo->beginTransaction();

            $this->sql->delete("DELETE FROM payments WHERE id = :id", [':id' => $id]);

            // Update Project Remaining Amount
            $stmtUpdateProject = $pdo->prepare("
                UPDATE projects 
                SET remaining_amount = total_cost - (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE project_id = :project_id)
                WHERE id = :project_id_update
            ");
            $stmtUpdateProject->execute([
                ':project_id' => $payment['project_id'],
                ':project_id_update' => $payment['project_id']
            ]);
            
            // If it was linked to an invoice, reset status to sent (as a guess of previous state)
            if ($payment['invoice_id']) {
                $pdo->prepare("UPDATE invoices SET status = 'sent' WHERE id = ?")->execute([$payment['invoice_id']]);
            }

            $pdo->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
