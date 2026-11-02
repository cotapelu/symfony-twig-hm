<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Service\PhoneValidationService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PhoneValidationServiceTest extends TestCase
{
    private PhoneValidationService $service;

    protected function setUp(): void
    {
        $this->service = new PhoneValidationService();
    }

    public function testValidateSuccess(): void
    {
        $this->service->validate('+84912345678');
        $this->service->validate('84912345678'); // without +
        $this->service->validate('+1-555-123-4567'); // should strip hyphens
        $this->addToAssertionCount(3);
    }

    public function testValidateTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number format');

        $this->service->validate('12345');
    }

    public function testValidateTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number format');

        // 16 digits + plus => length >15
        $this->service->validate('+1234567890123456');
    }

    public function testValidateStripsNonDigitPlus(): void
    {
        // Contains letters; after stripping becomes '123' (too short) -> invalid
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number format');

        $this->service->validate('abc123def');
    }

    public function testValidateEdgeLengths(): void
    {
        // Exactly 10 digits: valid
        $this->service->validate('1234567890');

        // Exactly 15 digits (no plus): valid
        $this->service->validate('123456789012345');

        $this->addToAssertionCount(2);
    }
}
