<?php

declare(strict_types=1);

namespace App\Shared\Service\Jwt;

interface JwtEncoderInterface
{
    /**
     * Encode a payload into a JWT token.
     *
     * @param array<string, mixed> $payload
     * @param string $secretKey
     * @param string $algorithm
     * @return string JWT token
     */
    public function encode(array $payload, string $secretKey, string $algorithm): string;
}
