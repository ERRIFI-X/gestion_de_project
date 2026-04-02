<?php
require_once __DIR__ . '/../routes/config.php';

class Sql
{

    public function getAll($sql = '')
    {
        $conn = getPDOConnectionDB();
        $stmt = $conn->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    public function getId($sql = '', $data = [])
    {
        $conn = getPDOConnectionDB();
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user;
    }

    public function update($sql = '', $data = [])
    {
        $conn = getPDOConnectionDB();
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        if ($stmt->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function delete($sql = '', $data = [])
    {
        $conn = getPDOConnectionDB();
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        if ($stmt->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }
}
