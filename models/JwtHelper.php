<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private static $secret;
    private static $alg = 'HS256';

    public static function setSecret($secret)
    {
        self::$secret = $secret;
    }

    /**
     * Encode a payload into a JWT token
     * 
     * @param array $payload
     * @return string
     */
    public static function encode($payload)
    {
        return JWT::encode($payload, self::$secret, self::$alg);
    }

    /**
     * Decode a JWT token
     * 
     * @param string $token
     * @return array|false
     */
    public static function decode($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(self::$secret, self::$alg));
            return (array) $decoded;
        } catch (Exception $e) {
            // Token is invalid or expired
            return false;
        }
    }
}
