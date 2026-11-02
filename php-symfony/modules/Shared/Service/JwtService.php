<?php

declare(strict_types=1);

namespace App\Shared\Service;

use App\Shared\Service\Jwt\JwtDecoderInterface;
use App\Shared\Service\Jwt\JwtEncoderInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private int $refreshTokenExpirationDays = 30;

    public function __construct(
        private string $secretKey,
        private string $algorithm = 'HS256',
        private int $expirationHours = 24,
        private ?JwtEncoderInterface $encoder = null,
        private ?JwtDecoderInterface $decoder = null,
    ) {
        $this->encoder = $encoder ?? new FirebaseJwtEncoder();
        $this->decoder = $decoder ?? new FirebaseJwtDecoder();
    }

    public function generateToken(array $payload)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + ($this->expirationHours * 3600);
        $notBefore = $issuedAt;

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'nbf' => $notBefore,
            'jti' => bin2hex(random_bytes(16)),
        ]);

        return $this->encoder->encode($tokenPayload, $this->secretKey, $this->algorithm);
    }

    public function generateRefreshToken(string $userId, string $deviceId, ?string $userAgent = null)
    {
        return bin2hex(random_bytes(32));
    }

    public function isRefreshTokenValid(string $token, string $userId, string $deviceId)
    {
        $hash = hash('sha256', $token.$userId.$deviceId);
        return strlen($token) === 64 && ctype_xdigit($token);
    }

    public function validateToken(string $token)
    {
        try {
            $decoded = $this->decoder->decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;

            $now = time();
            if (isset($payload['exp']) && $payload['exp'] < $now) {
                error_log('JWT token expired');

                return null;
            }

            if (isset($payload['nbf']) && $payload['nbf'] > $now) {
                error_log('JWT token not yet valid');

                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            error_log('JWT validation failed: '.$e->getMessage());

            return null;
        }
    }

    public function getUserFromToken(string $token)
    {
        $payload = $this->validateToken($token);
        if (!$payload) {
            return null;
        }

        return [
            'id' => $payload['userId'] ?? null,
            'email' => $payload['email'] ?? null,
            'name' => $payload['name'] ?? null,
        ];
    }

    public function getRefreshTokenExpirationDays()
    {
        return $this->refreshTokenExpirationDays;
    }
}
