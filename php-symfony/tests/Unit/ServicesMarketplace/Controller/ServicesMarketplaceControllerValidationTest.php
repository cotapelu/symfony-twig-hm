<?php

declare(strict_types=1);

namespace Tests\Unit\ServicesMarketplace\Controller;

use App\ServicesMarketplace\Controller\ServicesMarketplaceController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ServicesMarketplaceControllerValidationTest extends TestCase
{
    private ServicesMarketplaceController $controller;

    protected function setUp(): void
    {
        $serviceMock = $this->createMock(\App\ServicesMarketplace\Service\ServicesMarketplaceService::class);
        $this->controller = new ServicesMarketplaceController($serviceMock);
    }

    private function invokePrivate(string $method, array $args = [])
    {
        $ref = new ReflectionMethod($this->controller, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->controller, ...$args);
    }

    // ==================== validateRegisterProviderData ====================

    public function testValidateRegisterProviderData_AllValid_ReturnsEmptyArray(): void
    {
        $data = [
            'businessName' => 'Test Business',
            'displayName' => 'Test Display',
            'category' => 'general',
            'address' => '123 Main St',
            'phone' => '1234567890',
            'startingPrice' => 1000,
            'description' => 'A short desc',
            'latitude' => 10.0,
            'longitude' => 106.0,
        ];
        $result = $this->invokePrivate('validateRegisterProviderData', [$data]);
        $this->assertEmpty($result);
    }

    public function testValidateRegisterProviderData_MissingRequiredBusinessName_ReturnsError(): void
    {
        $data = [
            'displayName' => 'Test',
            'category' => 'gen',
            'address' => 'Addr',
            'phone' => '123',
            'startingPrice' => 1000,
        ];
        $result = $this->invokePrivate('validateRegisterProviderData', [$data]);
        $this->assertContains('Missing required field: businessName', $result);
    }

    public function testValidateRegisterProviderData_MissingRequiredDisplayName_ReturnsError(): void
    {
        $data = [
            'businessName' => 'Biz',
            'category' => 'gen',
            'address' => 'Addr',
            'phone' => '123',
            'startingPrice' => 1000,
        ];
        $result = $this->invokePrivate('validateRegisterProviderData', [$data]);
        $this->assertContains('Missing required field: displayName', $result);
    }

    public function testValidateRegisterProviderData_MissingRequiredCategory_ReturnsError(): void
    {
        $data = [
            'businessName' => 'Biz',
            'displayName' => 'Disp',
            'address' => 'Addr',
            'phone' => '123',
            'startingPrice' => 1000,
        ];
        $result = $this->invokePrivate('validateRegisterProviderData', [$data]);
        $this->assertContains('Missing required field: category', $result);
    }

    public function testValidateRegisterProviderData_DisplayNameTooLong_ReturnsError(): void
    {
        $data = [
            'businessName' => 'Biz',
            'displayName' => str_repeat('a', 256), // 256 > 255
            'category' => 'gen',
            'address' => 'Addr',
            'phone' => '123',
            'startingPrice' => 1000,
        ];
        $result = $this->invokePrivate('validateRegisterProviderData', [$data]);
        $this->assertContains('Display name too long (max 255 characters)', $result);
    }

    public function testValidateRegisterProviderData_DescriptionTooLong_ReturnsError(): void
    {
        $data = [
            'businessName' => 'Biz',
            'displayName' => 'Disp',
            'category' => 'gen',
            'address' => 'Addr',
            'phone' => '123',
            'startingPrice' => 1000,
            'description' => str_repeat('a', 1001),
        ];
        $result = $this->invokePrivate('validateRegisterProviderData', [$data]);
        $this->assertContains('Description too long (max 1000 characters)', $result);
    }

    public function testValidateRegisterProviderData_CoordinatesInvalid_ReturnsError(): void
    {
        $data = [
            'businessName' => 'Biz',
            'displayName' => 'Disp',
            'category' => 'gen',
            'address' => 'Addr',
            'phone' => '123',
            'startingPrice' => 1000,
            'latitude' => 200, // invalid > 90
            'longitude' => 106,
        ];
        $result = $this->invokePrivate('validateRegisterProviderData', [$data]);
        $this->assertContains('Invalid latitude', $result);
    }

    public function testValidateRegisterProviderData_StartingPriceTooHigh_ReturnsError(): void
    {
        $data = [
            'businessName' => 'Biz',
            'displayName' => 'Disp',
            'category' => 'gen',
            'address' => 'Addr',
            'phone' => '123',
            'startingPrice' => 200000000, // > MAX_PRICE 100000000
        ];
        $result = $this->invokePrivate('validateRegisterProviderData', [$data]);
        $this->assertContains('Invalid starting price', $result);
    }

    public function testValidateRegisterProviderData_PhoneTooLong_ReturnsError(): void
    {
        $data = [
            'businessName' => 'Biz',
            'displayName' => 'Disp',
            'category' => 'gen',
            'address' => 'Addr',
            'phone' => str_repeat('1', 21), // 21 > 20
            'startingPrice' => 1000,
        ];
        $result = $this->invokePrivate('validateRegisterProviderData', [$data]);
        $this->assertContains('Phone number too long (max 20 characters)', $result);
    }

    // ==================== validateCreateBookingData ====================

    public function testValidateCreateBookingData_AllValid_ReturnsEmptyArray(): void
    {
        $data = [
            'providerId' => 1,
            'userId' => '123',
            'address' => '123 Main St',
            'scheduledAt' => '2027-01-01T10:00:00', // future date
            'price' => 5000,
            'paymentMethod' => 'cash',
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertEmpty($result);
    }

    public function testValidateCreateBookingData_MissingRequiredProviderId_ReturnsError(): void
    {
        $data = [
            'userId' => '123',
            'address' => 'Addr',
            'scheduledAt' => '2025-01-01T10:00:00',
            'price' => 5000,
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Missing required field: providerId', $result);
    }

    public function testValidateCreateBookingData_MissingRequiredUserId_ReturnsError(): void
    {
        $data = [
            'providerId' => 1,
            'address' => 'Addr',
            'scheduledAt' => '2025-01-01T10:00:00',
            'price' => 5000,
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Missing required field: userId', $result);
    }

    public function testValidateCreateBookingData_MissingRequiredAddress_ReturnsError(): void
    {
        $data = [
            'providerId' => 1,
            'userId' => '123',
            'scheduledAt' => '2025-01-01T10:00:00',
            'price' => 5000,
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Missing required field: address', $result);
    }

    public function testValidateCreateBookingData_MissingRequiredScheduledAt_ReturnsError(): void
    {
        $data = [
            'providerId' => 1,
            'userId' => '123',
            'address' => 'Addr',
            'price' => 5000,
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Missing required field: scheduledAt', $result);
    }

    public function testValidateCreateBookingData_MissingRequiredPrice_ReturnsError(): void
    {
        $data = [
            'providerId' => 1,
            'userId' => '123',
            'address' => 'Addr',
            'scheduledAt' => '2025-01-01T10:00:00',
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Missing required field: price', $result);
    }

    public function testValidateCreateBookingData_AddressTooLong_ReturnsError(): void
    {
        $data = [
            'providerId' => 1,
            'userId' => '123',
            'address' => str_repeat('a', 501),
            'scheduledAt' => '2025-01-01T10:00:00',
            'price' => 5000,
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Address too long (max 500 characters)', $result);
    }

    public function testValidateCreateBookingData_InvalidCoordinates_ReturnsError(): void
    {
        $data = [
            'providerId' => 1,
            'userId' => '123',
            'address' => 'Addr',
            'scheduledAt' => '2025-01-01T10:00:00',
            'price' => 5000,
            'latitude' => 200,
            'longitude' => 106,
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Invalid latitude', $result);
    }

    public function testValidateCreateBookingData_PriceNegative_ReturnsError(): void
    {
        $data = [
            'providerId' => 1,
            'userId' => '123',
            'address' => 'Addr',
            'scheduledAt' => '2025-01-01T10:00:00',
            'price' => -100,
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Invalid price', $result);
    }

    public function testValidateCreateBookingData_PriceTooHigh_ReturnsError(): void
    {
        $data = [
            'providerId' => 1,
            'userId' => '123',
            'address' => 'Addr',
            'scheduledAt' => '2025-01-01T10:00:00',
            'price' => 200000000, // > MAX_PRICE
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Invalid price', $result);
    }

    public function testValidateCreateBookingData_ScheduledAtInvalidFormat_ReturnsError(): void
    {
        $data = [
            'providerId' => 1,
            'userId' => '123',
            'address' => 'Addr',
            'scheduledAt' => 'invalid-date',
            'price' => 5000,
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Invalid scheduledAt format', $result);
    }

    public function testValidateCreateBookingData_PaymentMethodInvalid_ReturnsError(): void
    {
        $data = [
            'providerId' => 1,
            'userId' => '123',
            'address' => 'Addr',
            'scheduledAt' => '2025-01-01T10:00:00',
            'price' => 5000,
            'paymentMethod' => 'bitcoin',
        ];
        $result = $this->invokePrivate('validateCreateBookingData', [$data]);
        $this->assertContains('Invalid payment method', $result);
    }
}
