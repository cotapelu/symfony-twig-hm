<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\ResponseHelper;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ResponseHelperTest extends TestCase
{
    public function testSuccessReturnsExpectedStructure(): void
    {
        $data = ['items' => [1,2,3]];
        $result = ResponseHelper::success($data, 'OK');
        $this->assertSame(['success' => true, 'message' => 'OK', 'data' => $data], $result);
    }

    public function testErrorReturnsExpectedStructure(): void
    {
        $result = ResponseHelper::error('Something went wrong', 400);
        $this->assertSame([
            'success' => false,
            'error' => 'Something went wrong',
            'code' => 400,
        ], $result);
    }

    public function testAuthErrorReturns401(): void
    {
        $result = ResponseHelper::authError();
        $this->assertSame([
            'success' => false,
            'error' => 'Authentication required',
            'code' => 401,
        ], $result);
    }

    public function testValidationErrorReturnsDetails(): void
    {
        $errors = ['email' => 'Invalid', 'password' => 'Too short'];
        $result = ResponseHelper::validationError($errors);
        $this->assertSame([
            'success' => false,
            'error' => 'Validation failed',
            'details' => $errors,
            'code' => 400,
        ], $result);
    }

    public function testNotFoundReturnsDefaultMessage(): void
    {
        $result = ResponseHelper::notFound();
        $this->assertSame([
            'success' => false,
            'error' => 'Resource not found',
            'code' => 404,
        ], $result);
    }

    public function testNotFoundWithCustomResource(): void
    {
        $result = ResponseHelper::notFound('User');
        $this->assertSame([
            'success' => false,
            'error' => 'User not found',
            'code' => 404,
        ], $result);
    }
}
