<?php

declare(strict_types=1);

namespace App\Shared\Service\Jwt;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class FirebaseJwtDecoder implements JwtDecoderInterface
{
    public function decode(string $token, string|Key $key): object|array
    {
        return JWT::decode($token, $key);
    }
}
