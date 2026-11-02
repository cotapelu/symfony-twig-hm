<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Controller\PhoneController;
use App\User\Service\PhoneVerificationService;
use App\User\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AllowMockObjectsWithoutExpectations]
class PhoneControllerTest extends TestCase
{
    private PhoneController $controller;
    private PhoneVerificationService|MockObject $phoneVerificationService;
    private User|MockObject $currentUser;

    protected function setUp(): void
    {
        $this->phoneVerificationService = $this->createMock(PhoneVerificationService::class);
        $this->currentUser = $this->createMock(User::class);
        $this->currentUser->method('getId')->willReturn('user123');

        $this->controller = new PhoneController($this->phoneVerificationService);
        $controllerReflection = new \ReflectionClass($this->controller);
        $userProperty = $controllerReflection->getParentClass()->getProperty('user');
        $userProperty->setAccessible(true);
        $userProperty->setValue($this->controller, $this->currentUser);
    }

    public function testSendVerificationSuccess(): void
    {
        $this->phoneVerificationService
            ->expects($this->once())
            ->method('sendVerificationCode')
            ->with('user123', '+1234567890')
            ->willReturn(['success' => true, 'message' => 'Verification code sent']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['phone_number' => '+1234567890'])
        );

        $result = $this->controller->sendVerification($request);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Verification code sent', $data['message']);
    }

    public function testSendVerificationInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->sendVerification($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testSendVerificationMissingPhoneNumber(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $result = $this->controller->sendVerification($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Phone number is required', $data['error']);
    }

    public function testSendVerificationNullPhoneNumber(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['phone_number' => null])
        );

        $result = $this->controller->sendVerification($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Phone number is required', $data['error']);
    }

    public function testSendVerificationInvalidPhoneNumber(): void
    {
        $this->phoneVerificationService
            ->expects($this->once())
            ->method('sendVerificationCode')
            ->with('user123', 'invalid-phone')
            ->willThrowException(new \InvalidArgumentException('Invalid phone number format'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['phone_number' => 'invalid-phone'])
        );

        $result = $this->controller->sendVerification($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid phone number format', $data['error']);
    }

    public function testSendVerificationServiceError(): void
    {
        $this->phoneVerificationService
            ->expects($this->once())
            ->method('sendVerificationCode')
            ->with('user123', '+1234567890')
            ->willThrowException(new \Exception('SMS gateway error'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['phone_number' => '+1234567890'])
        );

        $result = $this->controller->sendVerification($request);

        $this->assertEquals(500, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Failed to send verification code', $data['error']);
    }

    public function testSendVerificationUnauthorized(): void
    {
        $nonUser = $this->createMock(UserInterface::class);

        $controller = new PhoneController($this->phoneVerificationService);
        $controllerReflection = new \ReflectionClass($controller);
        $userProperty = $controllerReflection->getParentClass()->getProperty('user');
        $userProperty->setAccessible(true);
        $userProperty->setValue($controller, $nonUser);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['phone_number' => '+1234567890'])
        );

        $result = $controller->sendVerification($request);

        $this->assertEquals(401, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    public function testVerifyPhoneSuccess(): void
    {
        $this->phoneVerificationService
            ->expects($this->once())
            ->method('verifyPhoneNumber')
            ->with('user123', '123456')
            ->willReturn(['success' => true, 'verified' => true, 'message' => 'Phone number verified successfully']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => '123456'])
        );

        $result = $this->controller->verifyPhone($request);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertTrue($data['verified']);
    }

    public function testVerifyPhoneInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->verifyPhone($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testVerifyPhoneMissingCode(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $result = $this->controller->verifyPhone($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Verification code is required', $data['error']);
    }

    public function testVerifyPhoneNullCode(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => null])
        );

        $result = $this->controller->verifyPhone($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Verification code is required', $data['error']);
    }

    public function testVerifyPhoneInvalidCode(): void
    {
        $this->phoneVerificationService
            ->expects($this->once())
            ->method('verifyPhoneNumber')
            ->with('user123', '000000')
            ->willThrowException(new \InvalidArgumentException('Invalid verification code'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => '000000'])
        );

        $result = $this->controller->verifyPhone($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid verification code', $data['error']);
    }

    public function testVerifyPhoneExpiredCode(): void
    {
        $this->phoneVerificationService
            ->expects($this->once())
            ->method('verifyPhoneNumber')
            ->with('user123', '123456')
            ->willThrowException(new \InvalidArgumentException('Verification code expired'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => '123456'])
        );

        $result = $this->controller->verifyPhone($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Verification code expired', $data['error']);
    }

    public function testVerifyPhoneServiceError(): void
    {
        $this->phoneVerificationService
            ->expects($this->once())
            ->method('verifyPhoneNumber')
            ->with('user123', '123456')
            ->willThrowException(new \Exception('Database error'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => '123456'])
        );

        $result = $this->controller->verifyPhone($request);

        $this->assertEquals(500, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Failed to verify phone number', $data['error']);
    }

    public function testVerifyPhoneUnauthorized(): void
    {
        $nonUser = $this->createMock(UserInterface::class);

        $controller = new PhoneController($this->phoneVerificationService);
        $controllerReflection = new \ReflectionClass($controller);
        $userProperty = $controllerReflection->getParentClass()->getProperty('user');
        $userProperty->setAccessible(true);
        $userProperty->setValue($controller, $nonUser);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => '123456'])
        );

        $result = $controller->verifyPhone($request);

        $this->assertEquals(401, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    public function testSendVerificationRateLimited(): void
    {
        $this->phoneVerificationService
            ->expects($this->once())
            ->method('sendVerificationCode')
            ->with('user123', '+1234567890')
            ->willThrowException(new \InvalidArgumentException('Too many attempts, please try again later'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['phone_number' => '+1234567890'])
        );

        $result = $this->controller->sendVerification($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Too many attempts, please try again later', $data['error']);
    }

    public function testVerifyPhoneAlreadyVerified(): void
    {
        $this->phoneVerificationService
            ->expects($this->once())
            ->method('verifyPhoneNumber')
            ->with('user123', '123456')
            ->willReturn(['success' => true, 'verified' => false, 'message' => 'Phone number already verified']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => '123456'])
        );

        $result = $this->controller->verifyPhone($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertFalse($data['verified']);
    }
}
