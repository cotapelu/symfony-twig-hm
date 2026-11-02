<?php

declare(strict_types=1);

namespace App\Shared\Service\Jwt;

use Firebase\JWT\JWT;

final class FirebaseJwtEncoder implements JwtEncoderInterface
{
    public function encode(array $payload, string $secretKey, string $algorithm): string
    {
        return JWT::encode($payload, $secretKey, $algorithm);
    }
}
