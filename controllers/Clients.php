<?php

require_once __DIR__ . '/../models/Sql.php';

class Clients
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
    }

    public function getAll()
    {
        return $this->sql->getAll("SELECT * FROM clients ORDER BY created_at DESC");
    }

    public function show($id)
    {
        return $this->sql->getId("SELECT * FROM clients WHERE id = :id", ['id' => $id]);
    }

    public function store($data)
    {
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $result = $this->sql->create(
            "INSERT INTO clients (name, phone, email, address) VALUES (:name, :phone, :email, :address)",
            [
                ':name' => htmlspecialchars(trim($data['name'])),
                ':phone' => htmlspecialchars(trim($data['phone'] ?? '')),
                ':email' => htmlspecialchars(trim($data['email'])),
                ':address' => htmlspecialchars(trim($data['address'] ?? ''))
            ]
        );

        return $result ? ['success' => true, 'id' => $result] : ['success' => false, 'error' => 'Failed to create client'];
    }

    public function update($id, $data)
    {
        $errors = $this->validate($data, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $result = $this->sql->update(
            "UPDATE clients SET name = :name, phone = :phone, email = :email, address = :address WHERE id = :id",
            [
                ':id' => $id,
                ':name' => htmlspecialchars(trim($data['name'])),
                ':phone' => htmlspecialchars(trim($data['phone'] ?? '')),
                ':email' => htmlspecialchars(trim($data['email'])),
                ':address' => htmlspecialchars(trim($data['address'] ?? ''))
            ]
        );

        return ['success' => $result];
    }

    public function delete($id)
    {
        // Add check if client has projects before deleting (Business Logic)
        $projects = $this->sql->getAll("SELECT id FROM projects WHERE client_id = :id", ['id' => $id]);
        if (!empty($projects)) {
            return ['success' => false, 'error' => 'Cannot delete client with active projects.'];
        }

        $result = $this->sql->delete("DELETE FROM clients WHERE id = :id", ['id' => $id]);
        return ['success' => $result];
    }

    private function validate($data, $id = null)
    {
        $errors = [];
        if (empty($data['name'])) $errors[] = "Name is required.";
        if (empty($data['email'])) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } else {
            // Check for duplicate email
            $sql = "SELECT id FROM clients WHERE email = :email";
            $params = [':email' => $data['email']];
            if ($id) {
                $sql .= " AND id != :id";
                $params[':id'] = $id;
            }
            if ($this->sql->getId($sql, $params)) {
                $errors[] = "Email already in use.";
            }
        }
        return $errors;
    }
}