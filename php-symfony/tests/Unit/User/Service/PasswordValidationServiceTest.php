<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Service\PasswordValidationService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PasswordValidationServiceTest extends TestCase
{
    private PasswordValidationService $service;

    protected function setUp(): void
    {
        $this->service = new PasswordValidationService();
    }

    public function testValidateSuccess(): void
    {
        $password = 'StrongPass123';
        $this->service->validate($password);
        $this->addToAssertionCount(1); // just that no exception thrown
    }

    public function testValidateTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        $this->service->validate('short');
    }

    public function testValidateMissingUppercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one uppercase letter');

        $this->service->validate('lowercase1');
    }

    public function testValidateMissingLowercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one lowercase letter');

        $this->service->validate('UPPERCASE1');
    }

    public function testValidateMissingNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one number');

        $this->service->validate('NoNumber');
    }
}
