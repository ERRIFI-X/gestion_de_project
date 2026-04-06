<?php

require_once __DIR__ . '/../models/Sql.php';


class Invoices
{
    public function getAll()
    {
        $sql = new Sql();
        $invoices = $sql->getAll("SELECT * FROM invoices");
        return [
            'success' => true,
            'data' => $invoices
        ];
    }

    public function getById($id)
    {
        $sql = new Sql();
        $invoice = $sql->getAll("SELECT * FROM invoices WHERE id = ?", [$id]);
        if (empty($invoice)) {
            return [
                'success' => false,
                'message' => 'Invoice not found'
            ];
        }
        return [
            'success' => true,
            'data' => $invoice[0]
        ];
    }

    public function create($data)
    {
        $sql = new Sql();
        $sql->create("INSERT INTO invoices (client_id, project_id, amount, status) VALUES (?, ?, ?, ?)", [
            $data['client_id'],
            $data['project_id'],
            $data['amount'],
            $data['status']
        ]);
        return [
            'success' => true,
            'message' => 'Invoice created successfully'
        ];
    }


    public function update($id, $data)
    {
        $sql = new Sql();
        $sql->update("UPDATE invoices SET client_id = ?, project_id = ?, amount = ?, status = ? WHERE id = ?", [
            $data['client_id'],
            $data['project_id'],
            $data['amount'],
            $data['status'],
            $id
        ]);
        return [
            'success' => true,
            'message' => 'Invoice updated successfully'
        ];
    }
}

?>