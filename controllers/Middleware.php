<?php

require_once __DIR__ . '/../models/JwtHelper.php';
require_once __DIR__ . '/../routes/config.php';

class Middleware
{
    /**
     * Authenticates the request using JWT Bearer Token
     * 
     * @return array|void Returns decoded user data on success, or exits with 401 on failure.
     */
    public static function authenticate()
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized: Missing or invalid Authorization header.'
            ]);
            exit;
        }

        $token = substr($authHeader, 7); // Remove "Bearer " prefix
        JwtHelper::setSecret(JWT_SECRET);
        $decoded = JwtHelper::decode($token);

        if (!$decoded) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized: Invalid or expired token.'
            ]);
            exit;
        }

        return $decoded;
    }
}
