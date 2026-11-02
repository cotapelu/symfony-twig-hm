<?php declare(strict_types=1);

namespace Tests\Unit\User\Controller;

use App\User\Controller\AuthController;
use App\User\Service\AuthService;
use App\User\Entity\User;
use App\Shared\Infrastructure\RateLimitService;
use App\Shared\Service\JwtService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AuthControllerTest extends TestCase
{
    private AuthService&MockObject $authServiceMock;
    private RateLimitService&MockObject $rateLimitMock;
    private JwtService&MockObject $jwtMock;
    private AuthController $controller;

    protected function setUp(): void
    {
        $this->authServiceMock = $this->createMock(AuthService::class);
        $this->rateLimitMock = $this->createMock(RateLimitService::class);
        $this->jwtMock = $this->createMock(JwtService::class);
        $this->controller = new AuthController(
            $this->authServiceMock,
            $this->rateLimitMock,
            $this->jwtMock,
            null
        );
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $this->controller->setContainer($container);
    }

    private function createUserMock(string $id, string $email): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getEmail')->willReturn($email);
        return $user;
    }

    private function jsonResponse(JsonResponse $response, int $expectedStatus, ?array $expectedData = null): void
    {
        $this->assertSame($expectedStatus, $response->getStatusCode());
        if ($expectedData !== null) {
            $this->assertSame($expectedData, json_decode($response->getContent(), true));
        }
    }

    private function expectRateLimitOk(string $identifier, string $action): void
    {
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->with($identifier, $action)->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest')->with($identifier, $action, 'POST');
    }

    // ==================== register ====================

    public function testRegister_Success(): void
    {
        $request = Request::create('/api/auth/register', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => 'token'
        ], json_encode(['name' => 'John', 'email' => 'john@example.com', 'password' => 'pass']));

        $user = $this->createUserMock('u1', 'john@example.com');
        $this->authServiceMock->expects($this->once())->method('register')->with($this->callback(fn($data) => $data['name'] === 'John' && $data['email'] === 'john@example.com'))->willReturn($user);
        $this->authServiceMock->expects($this->once())->method('getUserProfile')->with($user)->willReturn(['id' => 'u1', 'email' => 'john@example.com']);

        $ip = $request->getClientIp();
        $this->expectRateLimitOk($ip, 'register');

        $response = $this->controller->register($request);
        $this->jsonResponse($response, 201, ['user' => ['id' => 'u1', 'email' => 'john@example.com'], 'message' => 'Registration successful']);
    }

    public function testRegister_RateLimitExceeded_Returns429(): void
    {
        $request = Request::create('/api/auth/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'John', 'email' => 'john@example.com', 'password' => 'pass']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(false);
        $response = $this->controller->register($request);
        $this->jsonResponse($response, 429, ['error' => 'Too many requests. Please try again later.']);
    }

    public function testRegister_InvalidJson_Returns400(): void
    {
        $request = Request::create('/api/auth/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $ip = $request->getClientIp();
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $response = $this->controller->register($request);
        $this->jsonResponse($response, 400);
    }

    public function testRegister_MissingRequired_Returns400(): void
    {
        $request = Request::create('/api/auth/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'John']));
        $ip = $request->getClientIp();
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $response = $this->controller->register($request);
        $this->jsonResponse($response, 400);
        $this->assertStringContainsString('required', $response->getContent());
    }

    // ==================== login ====================

    public function testLogin_Success(): void
    {
        $request = Request::create('/api/auth/login', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'john@example.com', 'password' => 'pass']));
        $user = $this->createUserMock('u1', 'john@example.com');
        $this->authServiceMock->expects($this->once())->method('authenticate')->with('john@example.com', 'pass')->willReturn($user);
        $this->authServiceMock->expects($this->once())->method('getUserProfile')->with($user)->willReturn(['id' => 'u1', 'email' => 'john@example.com']);
        $this->jwtMock->expects($this->once())->method('generateToken')->with($this->callback(fn($payload) => $payload['userId'] === 'u1'))->willReturn('jwt.token');

        $ip = $request->getClientIp();
        $this->expectRateLimitOk($ip, 'login');

        $response = $this->controller->login($request);
        $this->jsonResponse($response, 200, [
            'access_token' => 'jwt.token',
            'expires_in' => 86400,
            'token_type' => 'Bearer',
            'user' => ['id' => 'u1', 'email' => 'john@example.com'],
            'message' => 'Login successful',
        ]);
    }

    public function testLogin_RateLimitExceeded_Returns429(): void
    {
        $request = Request::create('/api/auth/login', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'john@example.com', 'password' => 'pass']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(false);
        $response = $this->controller->login($request);
        $this->jsonResponse($response, 429);
    }

    public function testLogin_InvalidCredentials_Returns401(): void
    {
        $request = Request::create('/api/auth/login', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'john@example.com', 'password' => 'wrong']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $this->authServiceMock->expects($this->once())->method('authenticate')->willReturn(null);
        $response = $this->controller->login($request);
        $this->jsonResponse($response, 401, ['error' => 'Invalid credentials']);
    }

    // ==================== socialLogin ====================

    public function testSocialLogin_Success(): void
    {
        $request = Request::create('/api/auth/social/google', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['token' => 'social_token']));
        $user = $this->createUserMock('u1', 'john@example.com');
        $this->authServiceMock->expects($this->once())->method('socialLogin')->with('google', $this->anything())->willReturn($user);
        $this->authServiceMock->expects($this->once())->method('getUserProfile')->with($user)->willReturn(['id' => 'u1']);

        $ip = $request->getClientIp();
        $this->expectRateLimitOk($ip, 'social_login');

        $response = $this->controller->socialLogin('google', $request);
        $this->jsonResponse($response, 200, ['user' => ['id' => 'u1'], 'message' => 'Social login successful']);
    }

    public function testSocialLogin_RateLimitExceeded_Returns429(): void
    {
        $request = Request::create('/api/auth/social/google', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['token' => 'social_token']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(false);
        $response = $this->controller->socialLogin('google', $request);
        $this->jsonResponse($response, 429);
    }

    // ==================== getProfile ====================

    public function testGetProfile_Success(): void
    {
        $user = $this->createUserMock('u1', 'john@example.com');
        $this->authServiceMock->expects($this->once())->method('getUserProfile')->with($user)->willReturn(['id' => 'u1', 'email' => 'john@example.com']);

        $response = $this->controller->getProfile($user);
        $this->jsonResponse($response, 200, ['user' => ['id' => 'u1', 'email' => 'john@example.com']]);
    }

    public function testGetProfile_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getProfile(null);
        $this->jsonResponse($response, 401, ['success' => false, 'error' => 'Authentication required', 'code' => 401]);
    }

    // ==================== updateProfile ====================

    public function testUpdateProfile_Success(): void
    {
        $user = $this->createUserMock('u1', 'john@example.com');
        $request = Request::create('/api/auth/profile', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'John Updated']));
        $updatedUser = $this->createUserMock('u1', 'john@example.com');
        $this->authServiceMock->expects($this->once())->method('updateProfile')->with($user, $this->callback(fn($data) => $data['name'] === 'John Updated'))->willReturn($updatedUser);
        $this->authServiceMock->expects($this->once())->method('getUserProfile')->with($updatedUser)->willReturn(['id' => 'u1', 'name' => 'John Updated']);

        $response = $this->controller->updateProfile($request, $user);
        $this->jsonResponse($response, 200, ['user' => ['id' => 'u1', 'name' => 'John Updated'], 'message' => 'Profile updated successfully']);
    }

    public function testUpdateProfile_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/auth/profile', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'John']));
        $response = $this->controller->updateProfile($request, null);
        $this->jsonResponse($response, 401, ['success' => false, 'error' => 'Authentication required', 'code' => 401]);
    }

    public function testUpdateProfile_InvalidJson_Returns400(): void
    {
        $user = $this->createUserMock('u1', 'john@example.com');
        $request = Request::create('/api/auth/profile', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->updateProfile($request, $user);
        $this->jsonResponse($response, 400);
    }

    // ==================== changePassword ====================

    public function testChangePassword_Success(): void
    {
        $user = $this->createUserMock('u1', 'john@example.com');
        $request = Request::create('/api/auth/change-password', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentPassword' => 'oldpass', 'newPassword' => 'newpass']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->with($request->getClientIp(), 'change_password')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $this->authServiceMock->expects($this->once())->method('changePassword')->with($user, 'oldpass', 'newpass')->willReturn(true);

        $response = $this->controller->changePassword($request, $user);
        $this->jsonResponse($response, 200, ['message' => 'Password changed successfully']);
    }

    public function testChangePassword_RateLimitExceeded_Returns429(): void
    {
        $user = $this->createUserMock('u1', 'john@example.com');
        $request = Request::create('/api/auth/change-password', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentPassword' => 'old', 'newPassword' => 'new']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(false);
        $response = $this->controller->changePassword($request, $user);
        $this->jsonResponse($response, 429);
    }

    public function testChangePassword_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/auth/change-password', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentPassword' => 'old', 'newPassword' => 'new']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $response = $this->controller->changePassword($request, null);
        $this->jsonResponse($response, 401, ['success' => false, 'error' => 'Authentication required', 'code' => 401]);
    }

    public function testChangePassword_MissingRequired_Returns400(): void
    {
        $user = $this->createUserMock('u1', 'john@example.com');
        $request = Request::create('/api/auth/change-password', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentPassword' => 'old']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $response = $this->controller->changePassword($request, $user);
        $this->jsonResponse($response, 400);
    }

    public function testChangePassword_WrongCurrent_Returns400(): void
    {
        $user = $this->createUserMock('u1', 'john@example.com');
        $request = Request::create('/api/auth/change-password', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentPassword' => 'wrong', 'newPassword' => 'new']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $this->authServiceMock->expects($this->once())->method('changePassword')->with($user, 'wrong', 'new')->willReturn(false);
        $response = $this->controller->changePassword($request, $user);
        $this->jsonResponse($response, 400, ['error' => 'Current password is incorrect']);
    }

    // ==================== forgotPassword ====================

    public function testForgotPassword_Success(): void
    {
        $request = Request::create('/api/auth/forgot-password', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'john@example.com']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->with($request->getClientIp(), 'forgot_password')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $this->authServiceMock->expects($this->once())->method('requestPasswordReset')->with('john@example.com');

        $response = $this->controller->forgotPassword($request);
        $this->jsonResponse($response, 200, ['message' => 'If the email exists, a password reset link has been sent']);
    }

    public function testForgotPassword_RateLimitExceeded_Returns429(): void
    {
        $request = Request::create('/api/auth/forgot-password', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'john@example.com']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(false);
        $response = $this->controller->forgotPassword($request);
        $this->jsonResponse($response, 429);
    }

    public function testForgotPassword_MissingEmail_Returns400(): void
    {
        $request = Request::create('/api/auth/forgot-password', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $response = $this->controller->forgotPassword($request);
        $this->jsonResponse($response, 400);
        $this->assertStringContainsString('Email is required', $response->getContent());
    }

    // ==================== resetPassword ====================

    public function testResetPassword_Success(): void
    {
        $request = Request::create('/api/auth/reset-password', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['token' => 'token123', 'password' => 'newpass']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->with($request->getClientIp(), 'reset_password')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $this->authServiceMock->expects($this->once())->method('resetPassword')->with('token123', 'newpass')->willReturn(true);

        $response = $this->controller->resetPassword($request);
        $this->jsonResponse($response, 200, ['message' => 'Password reset successfully']);
    }

    public function testResetPassword_RateLimitExceeded_Returns429(): void
    {
        $request = Request::create('/api/auth/reset-password', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['token' => 'token123', 'password' => 'newpass']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(false);
        $response = $this->controller->resetPassword($request);
        $this->jsonResponse($response, 429);
    }

    public function testResetPassword_MissingTokenOrPassword_Returns400(): void
    {
        $request = Request::create('/api/auth/reset-password', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['token' => 'token123']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $response = $this->controller->resetPassword($request);
        $this->jsonResponse($response, 400);
        $this->assertStringContainsString('Token and new password are required', $response->getContent());
    }

    public function testResetPassword_InvalidToken_Returns400(): void
    {
        $request = Request::create('/api/auth/reset-password', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['token' => 'bad', 'password' => 'newpass']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $this->authServiceMock->expects($this->once())->method('resetPassword')->with('bad', 'newpass')->willReturn(false);
        $response = $this->controller->resetPassword($request);
        $this->jsonResponse($response, 400, ['error' => 'Invalid or expired token']);
    }

    // ==================== verifyEmail ====================

    public function testVerifyEmail_Success(): void
    {
        $request = Request::create('/api/auth/verify-email', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['token' => 'verify_token']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->with($request->getClientIp(), 'verify_email')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $this->authServiceMock->expects($this->once())->method('verifyEmail')->with('verify_token')->willReturn(true);

        $response = $this->controller->verifyEmail($request);
        $this->jsonResponse($response, 200, ['message' => 'Email verified successfully']);
    }

    public function testVerifyEmail_RateLimitExceeded_Returns429(): void
    {
        $request = Request::create('/api/auth/verify-email', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['token' => 'verify_token']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(false);
        $response = $this->controller->verifyEmail($request);
        $this->jsonResponse($response, 429);
    }

    public function testVerifyEmail_MissingToken_Returns400(): void
    {
        $request = Request::create('/api/auth/verify-email', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $response = $this->controller->verifyEmail($request);
        $this->jsonResponse($response, 400);
        $this->assertStringContainsString('Verification token is required', $response->getContent());
    }

    public function testVerifyEmail_InvalidToken_Returns400(): void
    {
        $request = Request::create('/api/auth/verify-email', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['token' => 'bad']));
        $this->rateLimitMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $this->rateLimitMock->expects($this->once())->method('logRequest');
        $this->authServiceMock->expects($this->once())->method('verifyEmail')->with('bad')->willReturn(false);
        $response = $this->controller->verifyEmail($request);
        $this->jsonResponse($response, 400, ['error' => 'Invalid or expired token']);
    }

    // ==================== logout ====================

    public function testLogout_Success(): void
    {
        $response = $this->controller->logout();
        $this->jsonResponse($response, 200, ['message' => 'Logged out successfully']);
    }

    // ==================== me ====================

    public function testMe_Success(): void
    {
        $user = $this->createUserMock('u1', 'john@example.com');
        $this->authServiceMock->expects($this->once())->method('getUserProfile')->with($user)->willReturn(['id' => 'u1', 'email' => 'john@example.com']);

        $response = $this->controller->me($user);
        $this->jsonResponse($response, 200, ['user' => ['id' => 'u1', 'email' => 'john@example.com']]);
    }

    public function testMe_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->me(null);
        $this->jsonResponse($response, 401, ['success' => false, 'error' => 'Authentication required', 'code' => 401]);
    }
}
