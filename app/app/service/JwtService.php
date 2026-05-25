<?php
namespace app\service;

use Firebase\JWT\JWT;

class JwtService
{
    private static function secret(): string
    {
        return _env('JWT_SECRET', 'wetalk_default_jwt_secret_key_at_least_32_chars');
    }

    public static function generate(int $userId): string
    {
        $expire = (int)_env('JWT_EXPIRE', '604800');
        $payload = [
            'iss' => 'wetalk',
            'iat' => time(),
            'exp' => time() + $expire,
            'uid' => $userId,
        ];
        return JWT::encode($payload, self::secret(), 'HS256');
    }

    public static function decode(string $token): object
    {
        return JWT::decode($token, new \Firebase\JWT\Key(self::secret(), 'HS256'));
    }
}
