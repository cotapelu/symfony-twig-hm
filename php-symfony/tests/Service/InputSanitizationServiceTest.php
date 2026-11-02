<?php

declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Infrastructure\InputSanitizationService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class InputSanitizationServiceTest extends TestCase
{
    private InputSanitizationService $service;

    protected function setUp(): void
    {
        $this->service = new InputSanitizationService();
    }

    public function testSanitizeString(): void
    {
        $input = '<script>alert("xss")</script>Hello World';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Hello World';

        $this->assertEquals($expected, $this->service->sanitizeString($input));
    }

    public function testSanitizeEmail(): void
    {
        $input = '  test@example.com  ';
        $expected = 'test@example.com';

        $this->assertEquals($expected, $this->service->sanitizeEmail($input));
    }

    public function testSanitizeHtmlContent(): void
    {
        $input = '<p>Hello <b>World</b></p><script>evil()</script>';
        $expected = '<p>Hello <b>World</b></p>evil()';

        $this->assertEquals($expected, $this->service->sanitizeHtmlContent($input));
    }

    public function testContainsDangerousPatterns(): void
    {
        $this->assertTrue($this->service->containsDangerousPatterns('<script>alert(1)</script>'));
        $this->assertTrue($this->service->containsDangerousPatterns('javascript:alert(1)'));
        $this->assertTrue($this->service->containsDangerousPatterns('<iframe src="evil.com">'));
        $this->assertFalse($this->service->containsDangerousPatterns('Hello World'));
    }

    public function testSanitizeUserInput(): void
    {
        $input = [
            'name' => 'John Doe',
            'email' => ' john@example.com ',
            'message' => '<p>Hello</p><script>alert(1)</script>',
        ];

        $expected = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => '<p>Hello</p>alert(1)',
        ];

        $this->assertEquals($expected, $this->service->sanitizeUserInput($input));
    }

    public function testSanitizeUserInputThrowsExceptionOnDangerousContent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Input contains dangerous content');

        $input = [
            'name' => 'John<script>alert(1)</script>',
        ];

        $this->service->sanitizeUserInput($input);
    }
}