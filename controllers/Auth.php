<?php

require_once __DIR__ . '/../models/Sql.php';
require_once __DIR__ . '/../models/JwtHelper.php';
require_once __DIR__ . '/../routes/config.php';

class Auth
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
        JwtHelper::setSecret(JWT_SECRET);
    }

    public function register($data)
    {
        $username = htmlspecialchars(trim($data['username'] ?? ''));
        $name = htmlspecialchars(trim($data['name'] ?? ''));
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Username and password are required.'];
        }

        // Check if username exists
        $exists = $this->sql->getId("SELECT id FROM admin WHERE username = :username", ['username' => $username]);
        if ($exists) {
            return ['success' => false, 'error' => 'Username already exists.'];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $id = $this->sql->create(
            "INSERT INTO admin (username, name, password) VALUES (:username, :name, :password)",
            ['username' => $username, 'name' => $name, 'password' => $hashedPassword]
        );

        if ($id) {
            return ['success' => true, 'message' => 'Administrator registered successfully.', 'id' => $id];
        }
        return ['success' => false, 'error' => 'Registration failed.'];
    }

    public function login($data)
    {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        // 1. Check Admin Table
        $admin = $this->sql->getId("SELECT * FROM admin WHERE username = :username", ['username' => $username]);
        if ($admin && password_verify($password, $admin['password'])) {
            $payload = [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'name' => $admin['name'],
                'role' => 'admin',
                'exp' => time() + (24 * 60 * 60) // 24 hours
            ];
            $token = JwtHelper::encode($payload);
            return [
                'success' => true,
                'message' => 'Connexion réussie (Admin)',
                'token' => $token,
                'user' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'name' => $admin['name'],
                    'role' => 'admin'
                ]
            ];
        }

        // 2. Check Clients Table
        $client = $this->sql->getId("SELECT * FROM clients WHERE username = :username OR email = :username", ['username' => $username]);
        if ($client && $client['password'] && password_verify($password, $client['password'])) {
            $payload = [
                'id' => $client['id'],
                'username' => $client['username'] ?? $client['email'],
                'name' => $client['name'],
                'role' => 'client',
                'exp' => time() + (24 * 60 * 60) // 24 hours
            ];
            $token = JwtHelper::encode($payload);
            return [
                'success' => true,
                'message' => 'Connexion réussie (Client)',
                'token' => $token,
                'user' => [
                    'id' => $client['id'],
                    'username' => $client['username'] ?? $client['email'],
                    'name' => $client['name'],
                    'role' => 'client'
                ]
            ];
        }

        return ['success' => false, 'error' => 'Identifiants invalides.'];
    }

}
