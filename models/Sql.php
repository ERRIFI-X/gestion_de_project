<?php
require_once __DIR__ . '/Database.php';

class Sql
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function getAll($sql = '', $data = [])
    {
        if (empty($data)) {
            $stmt = $this->pdo->query($sql);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
        }
        return $stmt->fetchAll();
    }

    public function getId($sql = '', $data = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $stmt->fetch();
    }

    public function create($sql = '', $data = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId(); // Return Last Insert ID instead of boolean for better flexibility
    }

    public function update($sql = '', $data = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount() > 0;
    }

    public function delete($sql = '', $data = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount() > 0;
    }

    /**
     * Expose PDO instance for complex transactions
     */
    public function getPdo()
    {
        return $this->pdo;
    }
}
