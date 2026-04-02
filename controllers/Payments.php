<?php 

require_once __DIR__ . '/../models/Sql.php';

class Payments
{
    public function getAll()
    {
        $sql = new Sql();
        return $sql->getAll("SELECT * FROM payments");
    }

    public function show($id)
    {
        $sql = new Sql();
        return $sql->getId("SELECT * FROM payments WHERE id = :id", ['id' => $id]);
    }

    public function store($amount, $payment_date, $client_id)
    {
        $sql = new Sql();
        return $sql->update("INSERT INTO payments (amount, payment_date, client_id) VALUES (:amount, :payment_date, :client_id)", ['amount' => $amount, 'payment_date' => $payment_date, 'client_id' => $client_id]);
    }

    public function update($id, $amount, $payment_date, $client_id)
    {
        $sql = new Sql();
        return $sql->update("UPDATE payments SET amount = :amount, payment_date = :payment_date, client_id = :client_id WHERE id = :id", ['id' => $id, 'amount' => $amount, 'payment_date' => $payment_date, 'client_id' => $client_id]);
    }

    public function delete($id)
    {
        $sql = new Sql();
        return $sql->update("DELETE FROM payments WHERE id = :id", ['id' => $id]);
    }
}







?>