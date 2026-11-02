<?php

declare(strict_types=1);

namespace App\Shared\Service\Jwt;

use Firebase\JWT\Key;

interface JwtDecoderInterface
{
    /**
     * Decode a JWT token.
     *
     * @param string $token
     * @param string|Key $key
     * @return object|array Decoded payload
     */
    public function decode(string $token, string|Key $key): object|array;
}
