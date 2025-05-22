<?php
namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\Constants;

class AuthService {
    private static $algorithms = [
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512'
    ];
    
    public static function generateToken(int $userId, string $type = 'access'): string {
        $issuedAt = time();
        $expirationTime = $issuedAt + ($type === 'access' 
            ? Constants::JWT_ACCESS_TOKEN_EXPIRY 
            : Constants::JWT_REFRESH_TOKEN_EXPIRY);
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => $userId,
            'type' => $type
        ];
        
        return JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');
    }
    
    public static function validateToken(string $token): array {
        try {
            $decoded = JWT::decode($token, new Key(getenv('JWT_SECRET'), 'HS256'));
            return (array)$decoded;
        } catch (\Exception $e) {
            throw new \App\Exceptions\AuthException('Invalid or expired token');
        }
    }
    
    public static function refreshToken(string $refreshToken): array {
        $decoded = self::validateToken($refreshToken);
        
        if ($decoded['type'] !== 'refresh') {
            throw new \App\Exceptions\AuthException('Invalid token type');
        }
        
        return [
            'access_token' => self::generateToken($decoded['sub'], 'access'),
            'refresh_token' => self::generateToken($decoded['sub'], 'refresh')
        ];
    }
}