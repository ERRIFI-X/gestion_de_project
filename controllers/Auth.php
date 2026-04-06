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

        $admin = $this->sql->getId("SELECT * FROM admin WHERE username = :username", ['username' => $username]);

        if ($admin && password_verify($password, $admin['password'])) {
            $payload = [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'name' => $admin['name'],
                'exp' => time() + (24 * 60 * 60) // 24 hours
            ];
            $token = JwtHelper::encode($payload);
            return [
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'name' => $admin['name']
                ]
            ];
        }

        return ['success' => false, 'error' => 'Invalid credentials.'];
    }
}
