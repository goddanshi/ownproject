<?php
namespace common\components;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private static $secret = 'your-super-secret-key-change-me-in-production'; // Измени на случайную строку!
    private static $algorithm = 'HS256';

    public static function generateToken($userId, $username, $email)
    {
        $payload = [
            'iss' => 'ownproject',
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60), // 7 дней
            'userId' => $userId,
            'username' => $username,
            'email' => $email,
        ];

        return JWT::encode($payload, self::$secret, self::$algorithm);
    }

    public static function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(self::$secret, self::$algorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}