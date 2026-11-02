<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure;

use App\Shared\Infrastructure\InputSanitizationService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class InputSanitizationServiceTest extends TestCase
{
    private InputSanitizationService $service;

    protected function setUp(): void
    {
        $this->service = new InputSanitizationService();
    }

    // sanitizeString
    public function testSanitizeStringTrimsWhitespace(): void
    {
        $result = $this->service->sanitizeString("  hello  ");
        $this->assertEquals('hello', $result);
    }

    public function testSanitizeStringConvertsSpecialChars(): void
    {
        $result = $this->service->sanitizeString('<>&"\'');
        // htmlspecialchars with ENT_QUOTES | ENT_HTML5 converts ' to &apos; in PHP 8.4+
        $this->assertEquals('&lt;&gt;&amp;&quot;&apos;', $result);
    }

    public function testSanitizeStringHandlesEmptyString(): void
    {
        $result = $this->service->sanitizeString('');
        $this->assertEquals('', $result);
    }

    // sanitizeForDatabase
    public function testSanitizeForDatabaseTrimsAndEncodes(): void
    {
        $result = $this->service->sanitizeForDatabase("  O'Reilly  ");
        $this->assertEquals('O&apos;Reilly', $result);
    }

    // sanitizeEmail
    public function testSanitizeEmailRemovesInvalidChars(): void
    {
        $result = $this->service->sanitizeEmail("user@example.com<script>");
        // FILTER_SANITIZE_EMAIL removes tags but preserves valid email chars as is
        $this->assertEquals('user@example.comscript', $result);
    }

    public function testSanitizeEmailWithSpaces(): void
    {
        $result = $this->service->sanitizeEmail(" user@example.com ");
        $this->assertEquals('user@example.com', $result);
    }

    public function testSanitizeEmailInvalidEmail(): void
    {
        $result = $this->service->sanitizeEmail('notanemail');
        // FILTER_SANITIZE_EMAIL only removes invalid chars; it does not validate format
        $this->assertEquals('notanemail', $result);
    }

    // sanitizeHtmlContent
    public function testSanitizeHtmlContentAllowsBasicTags(): void
    {
        $result = $this->service->sanitizeHtmlContent('<p>Hello <b>world</b></p>');
        $this->assertEquals('<p>Hello <b>world</b></p>', $result);
    }

    public function testSanitizeHtmlContentStripsScriptTags(): void
    {
        $result = $this->service->sanitizeHtmlContent('<script>alert(1)</script>Hello');
        // strip_tags removes the script tags but leaves inner content
        $this->assertEquals('alert(1)Hello', $result);
    }

    public function testSanitizeHtmlContentConvertsLineBreaks(): void
    {
        $result = $this->service->sanitizeHtmlContent("Line1\nLine2");
        // nl2br uses <br /> and preserves the newline character
        $this->assertEquals("Line1<br />\nLine2", $result);
    }

    public function testSanitizeHtmlContentHandlesNullByte(): void
    {
        $result = $this->service->sanitizeHtmlContent("Hello\0World");
        $this->assertEquals('HelloWorld', $result);
    }

    // containsDangerousPatterns
    public function testContainsDangerousPatternsDetectsScript(): void
    {
        $this->assertTrue($this->service->containsDangerousPatterns('<script>'));
    }

    public function testContainsDangerousPatternsDetectsJavaScriptProtocol(): void
    {
        $this->assertTrue($this->service->containsDangerousPatterns('javascript:alert(1)'));
    }

    public function testContainsDangerousPatternsDetectsOnEvent(): void
    {
        $this->assertTrue($this->service->containsDangerousPatterns('<div onclick="evil()">'));
    }

    public function testContainsDangerousPatternsDetectsIframe(): void
    {
        $this->assertTrue($this->service->containsDangerousPatterns('<iframe src="evil.com">'));
    }

    public function testContainsDangerousPatternsDetectsForm(): void
    {
        $this->assertTrue($this->service->containsDangerousPatterns('<form action="evil.com">'));
    }

    public function testContainsDangerousPatternsDetectsMeta(): void
    {
        $this->assertTrue($this->service->containsDangerousPatterns('<meta http-equiv="refresh" content="0;url=evil.com">'));
    }

    public function testContainsDangerousPatternsAllowsSafeInput(): void
    {
        $this->assertFalse($this->service->containsDangerousPatterns('Hello, world!'));
    }

    // sanitizeUserInput
    public function testSanitizeUserInputSanitizesStringFields(): void
    {
        $data = ['name' => "  Test's <>&\"  "];
        $result = $this->service->sanitizeUserInput($data);
        $this->assertEquals("Test&apos;s &lt;&gt;&amp;&quot;", $result['name']);
    }

    public function testSanitizeUserInputSanitizesEmailField(): void
    {
        $data = ['email' => "  USER@EXAMPLE.COM  "];
        $result = $this->service->sanitizeUserInput($data);
        // Email sanitization does not change case
        $this->assertEquals('USER@EXAMPLE.COM', $result['email']);
    }

    public function testSanitizeUserInputSanitizesContentFieldWithHtml(): void
    {
        $data = ['message_content' => "<p>Hello <b>world</b></p>\nNew line"];
        $result = $this->service->sanitizeUserInput($data);
        // nl2br uses <br />, and newline remains
        $this->assertEquals("<p>Hello <b>world</b></p><br />\nNew line", $result['message_content']);
    }

    public function testSanitizeUserInputThrowsOnDangerousPattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('contains dangerous content');
        $this->service->sanitizeUserInput(['comment' => '<script>alert(1)</script>']);
    }

    public function testSanitizeUserInputHandlesNestedArrays(): void
    {
        $data = [
            'user' => [
                'name' => "  Test <>&  ",
                'email' => "  test@example.com  "
            ]
        ];
        $result = $this->service->sanitizeUserInput($data);
        $this->assertEquals('Test &lt;&gt;&amp;', $result['user']['name']);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    public function testSanitizeUserInputPreservesNonStringValues(): void
    {
        $data = ['count' => 42, 'active' => true, 'price' => 19.99];
        $result = $this->service->sanitizeUserInput($data);
        $this->assertSame(42, $result['count']);
        $this->assertTrue($result['active']);
        $this->assertSame(19.99, $result['price']);
    }
}
