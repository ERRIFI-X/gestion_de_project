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

        $password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : null;

        $result = $this->sql->create(
            "INSERT INTO clients (name, username, password, phone, email, address) VALUES (:name, :username, :password, :phone, :email, :address)",
            [
                ':name' => htmlspecialchars(trim($data['name'])),
                ':username' => htmlspecialchars(trim($data['username'] ?? '')),
                ':password' => $password,
                ':phone' => htmlspecialchars(trim($data['phone'] ?? '')),
                ':email' => htmlspecialchars(trim($data['email'])),
                ':address' => htmlspecialchars(trim($data['address'] ?? ''))
            ]
        );

        return $result ? ['success' => true, 'id' => $result] : ['success' => false, 'error' => 'Échec de la création du client'];
    }

    public function update($id, $data)
    {
        $errors = $this->validate($data, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $sql = "UPDATE clients SET name = :name, username = :username, phone = :phone, email = :email, address = :address";
        $params = [
            ':id' => $id,
            ':name' => htmlspecialchars(trim($data['name'])),
            ':username' => htmlspecialchars(trim($data['username'] ?? '')),
            ':phone' => htmlspecialchars(trim($data['phone'] ?? '')),
            ':email' => htmlspecialchars(trim($data['email'])),
            ':address' => htmlspecialchars(trim($data['address'] ?? ''))
        ];

        if (!empty($data['password'])) {
            $sql .= ", password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $sql .= " WHERE id = :id";
        $result = $this->sql->update($sql, $params);

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
        if (empty($data['name'])) $errors[] = "Le nom est requis.";
        
        if (empty($data['email'])) {
            $errors[] = "L'email est requis.";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'email invalide.";
        } else {
            // Check for duplicate email
            $sql = "SELECT id FROM clients WHERE email = :email";
            $params = [':email' => $data['email']];
            if ($id) {
                $sql .= " AND id != :id";
                $params[':id'] = $id;
            }
            if ($this->sql->getId($sql, $params)) {
                $errors[] = "L'email est déjà utilisé.";
            }
        }

        // Check for duplicate username
        if (!empty($data['username'])) {
            $sql = "SELECT id FROM clients WHERE username = :username";
            $params = [':username' => $data['username']];
            if ($id) {
                $sql .= " AND id != :id";
                $params[':id'] = $id;
            }
            if ($this->sql->getId($sql, $params)) {
                $errors[] = "Le nom d'utilisateur est déjà utilisé.";
            }
        }

        return $errors;
    }

}