<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\JwtService;
use App\Shared\Service\Jwt\JwtEncoderInterface;
use App\Shared\Service\Jwt\JwtDecoderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class JwtServiceTest extends TestCase
{
    private JwtEncoderInterface&MockObject $encoderMock;
    private JwtDecoderInterface&MockObject $decoderMock;
    private JwtService $service;

    protected function setUp(): void
    {
        $this->encoderMock = $this->createMock(JwtEncoderInterface::class);
        $this->decoderMock = $this->createMock(JwtDecoderInterface::class);
        $this->service = new JwtService(
            'test-secret',
            'HS256',
            24,
            $this->encoderMock,
            $this->decoderMock
        );
    }

    public function testGenerateTokenIncludesStandardClaims(): void
    {
        $now = time();
        $expectedPayload = [
            'userId' => 'user-123',
            'email' => 'test@example.com',
            'iat' => $this->anything(),
            'exp' => $this->anything(),
            'nbf' => $this->anything(),
            'jti' => $this->anything(),
        ];

        $this->encoderMock->expects($this->once())
            ->method('encode')
            ->with($this->callback(function (array $payload) use (&$capturedPayload) {
                $capturedPayload = $payload;
                return array_key_exists('userId', $payload) && array_key_exists('email', $payload);
            }), 'test-secret', 'HS256')
            ->willReturn('generated-token');

        $token = $this->service->generateToken(['userId' => 'user-123', 'email' => 'test@example.com']);
        $this->assertSame('generated-token', $token);

        // Validate captured payload structure
        $this->assertArrayHasKey('iat', $capturedPayload);
        $this->assertArrayHasKey('exp', $capturedPayload);
        $this->assertArrayHasKey('nbf', $capturedPayload);
        $this->assertArrayHasKey('jti', $capturedPayload);
        $this->assertSame('user-123', $capturedPayload['userId']);
    }

    public function testGenerateTokenExpirationCalculatedCorrectly(): void
    {
        $this->encoderMock->expects($this->once())
            ->method('encode')
            ->with($this->callback(function (array $payload) {
                $this->assertIsInt($payload['iat']);
                $this->assertIsInt($payload['exp']);
                $this->assertIsInt($payload['nbf']);
                // exp should be iat + (expirationHours * 3600) approximately ± few seconds
                $expectedExp = $payload['iat'] + (24 * 3600);
                $this->assertEqualsWithDelta($expectedExp, $payload['exp'], 5);
                return true;
            }), 'test-secret', 'HS256')
            ->willReturn('token');

        $this->service->generateToken([]);
    }

    public function testValidateTokenReturnsPayloadWhenValid(): void
    {
        $decoded = (object) [
            'userId' => 'user-123',
            'email' => 'test@example.com',
            'exp' => time() + 3600,
            'nbf' => time() - 10,
        ];
        $this->decoderMock->expects($this->once())
            ->method('decode')
            ->with('valid-token', new \Firebase\JWT\Key('test-secret', 'HS256'))
            ->willReturn($decoded);

        $result = $this->service->validateToken('valid-token');
        $this->assertIsArray($result);
        $this->assertSame('user-123', $result['userId']);
    }

    public function testValidateTokenReturnsNullWhenExpired(): void
    {
        $decoded = (object) [
            'userId' => 'user-123',
            'exp' => time() - 100, // expired
            'nbf' => time() - 200,
        ];
        $this->decoderMock->expects($this->once())
            ->method('decode')
            ->willReturn($decoded);

        $result = $this->service->validateToken('expired-token');
        $this->assertNull($result);
    }

    public function testValidateTokenReturnsNullWhenNotYetValid(): void
    {
        $decoded = (object) [
            'userId' => 'user-123',
            'exp' => time() + 3600,
            'nbf' => time() + 100, // not yet valid
        ];
        $this->decoderMock->expects($this->once())
            ->method('decode')
            ->willReturn($decoded);

        $result = $this->service->validateToken('not-yet-valid-token');
        $this->assertNull($result);
    }

    public function testValidateTokenReturnsNullOnDecoderException(): void
    {
        $this->decoderMock->expects($this->once())
            ->method('decode')
            ->with('invalid-token', new \Firebase\JWT\Key('test-secret', 'HS256'))
            ->willThrowException(new \DomainException('Signature verification failed'));

        $result = $this->service->validateToken('invalid-token');
        $this->assertNull($result);
    }

    public function testGetUserFromTokenReturnsUserData(): void
    {
        $decoded = (object) [
            'userId' => 'user-123',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'exp' => time() + 3600,
            'nbf' => time() - 10,
        ];
        $this->decoderMock->expects($this->once())
            ->method('decode')
            ->willReturn($decoded);

        $user = $this->service->getUserFromToken('token');
        $this->assertSame('user-123', $user['id']);
        $this->assertSame('test@example.com', $user['email']);
        $this->assertSame('Test User', $user['name']);
    }

    public function testGetUserFromTokenReturnsNullWhenInvalid(): void
    {
        $this->decoderMock->expects($this->once())
            ->method('decode')
            ->willThrowException(new \Exception('Invalid'));

        $user = $this->service->getUserFromToken('bad-token');
        $this->assertNull($user);
    }

    public function testIsRefreshTokenValidFormat(): void
    {
        $validToken = bin2hex(random_bytes(32)); // 64 hex chars
        $this->assertTrue($this->service->isRefreshTokenValid($validToken, 'user-1', 'device-1'));

        $invalidTooShort = substr($validToken, 0, 32);
        $this->assertFalse($this->service->isRefreshTokenValid($invalidTooShort, 'user-1', 'device-1'));

        $invalidNonHex = str_repeat('x', 64);
        $this->assertFalse($this->service->isRefreshTokenValid($invalidNonHex, 'user-1', 'device-1'));
    }

    public function testGetRefreshTokenExpirationDays(): void
    {
        $this->assertSame(30, $this->service->getRefreshTokenExpirationDays());
    }
}
