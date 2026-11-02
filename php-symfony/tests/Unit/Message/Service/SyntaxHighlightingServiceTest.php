<?php

namespace App\Tests\Unit\Message\Service;

use App\Message\Service\SyntaxHighlightingService;
use PHPUnit\Framework\TestCase;

class SyntaxHighlightingServiceTest extends TestCase
{
    private SyntaxHighlightingService $service;

    protected function setUp(): void
    {
        $this->service = new SyntaxHighlightingService();
    }

    public function testGetPluginScriptsReturnsExpectedScripts(): void
    {
        $method = new \ReflectionMethod($this->service, 'getPluginScripts');
        $method->setAccessible(true);
        $scripts = $method->invoke($this->service);
        $this->assertIsArray($scripts);
        $this->assertCount(2, $scripts);
        $this->assertStringContainsString('line-numbers', $scripts[0]);
        $this->assertStringContainsString('copy-to-clipboard', $scripts[1]);
    }

    public function testGetPrismStylesReturnsValidLinkTags(): void
    {
        $styles = $this->service->getPrismStyles('default');
        $this->assertIsString($styles);
        $this->assertStringContainsString('<link', $styles);
        $this->assertStringContainsString('prism.css', $styles);
        $this->assertStringContainsString('line-numbers', $styles);
    }

    public function testGetPrismStylesWithUnknownThemeUsesDefault(): void
    {
        $styles = $this->service->getPrismStyles('banana');
        $this->assertStringContainsString('prism.css', $styles);
    }

    public function testGetPrismScriptsIncludesCoreAndPluginsAndLanguages(): void
    {
        $scriptsStr = $this->service->getPrismScripts(['php', 'javascript']);
        $this->assertIsString($scriptsStr);
        $this->assertStringContainsString('prism.min.js', $scriptsStr);
        $this->assertStringContainsString('prism-php.min.js', $scriptsStr);
        $this->assertStringContainsString('prism-javascript.min.js', $scriptsStr);
        $this->assertStringContainsString('prism-line-numbers', $scriptsStr);
    }

    public function testGetPrismScriptsWithEmptyLanguagesReturnsCoreAndPluginsOnly(): void
    {
        $scriptsStr = $this->service->getPrismScripts([]);
        $this->assertStringContainsString('prism.min.js', $scriptsStr);
        $this->assertStringContainsString('prism-line-numbers', $scriptsStr);
        $this->assertStringNotContainsString('prism-php', $scriptsStr); // no language component
        $this->assertStringNotContainsString('prism-javascript', $scriptsStr);
    }

    public function testHighlightCodeGeneratesHtmlWithLineNumbersAndCopyButton(): void
    {
        $code = '<?php echo "Hello"; ?>';
        $html = $this->service->highlightCode($code, 'php', ['lineNumbers' => true, 'copyButton' => true]);

        $this->assertStringContainsString('<pre class="code-block language-php line-numbers"', $html);
        $this->assertStringContainsString('<code class="language-php">', $html);
        $this->assertStringContainsString('echo', $html);
        $this->assertStringContainsString('</code>', $html);
        $this->assertStringContainsString('</pre>', $html);
        $this->assertStringContainsString('copy-code-btn', $html);
    }

    public function testHighlightCodeFallbackWhenLanguageNotSupported(): void
    {
        $code = 'select * from users';
        $html = $this->service->highlightCode($code, 'unknownlang');
        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('select', $html);
    }

    public function testParseCodeBlocksReplacesSingleBlock(): void
    {
        $content = "Here is code:\n```php\necho 'test';\n```\nEnd.";
        $result = $this->service->parseCodeBlocks($content);

        $this->assertStringNotContainsString('```', $result);
        $this->assertStringContainsString('echo', $result);
        $this->assertStringContainsString('language-php', $result);
    }

    public function testParseCodeBlocksMultipleBlocks(): void
    {
        $content = "```js\nconsole.log('a');\n``` and ```py\nprint(1)\n```";
        $result = $this->service->parseCodeBlocks($content);

        $this->assertStringNotContainsString('```', $result);
        $this->assertStringContainsString('console.log', $result);
        $this->assertStringContainsString('print', $result);
    }

    public function testParseCodeBlocksWithoutCodeBlocks(): void
    {
        $content = "Just plain text.";
        $result = $this->service->parseCodeBlocks($content);
        $this->assertSame($content, $result);
    }

    public function testParseCodeBlocksHandlesInlineCode(): void
    {
        $content = "Use `ls -la` to list files.";
        $result = $this->service->parseCodeBlocks($content);

        $this->assertStringNotContainsString('`', $result);
        $this->assertStringContainsString('<code class="inline-code">ls -la</code>', $result);
    }

    public function testParseCodeBlocksLanguageAlias(): void
    {
        $content = "```js\nlet x = 1;\n```";
        $result = $this->service->parseCodeBlocks($content);
        $this->assertStringContainsString('language-javascript', $result);
    }

    public function testIsLanguageSupported(): void
    {
        $method = new \ReflectionMethod($this->service, 'isLanguageSupported');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke($this->service, 'php'));
        $this->assertFalse($method->invoke($this->service, 'unknownlang'));
    }

    public function testNormalizeLanguage(): void
    {
        $method = new \ReflectionMethod($this->service, 'normalizeLanguage');
        $method->setAccessible(true);
        $this->assertSame('javascript', $method->invoke($this->service, 'js'));
        $this->assertSame('typescript', $method->invoke($this->service, 'ts'));
        $this->assertSame('csharp', $method->invoke($this->service, 'cs'));
        $this->assertSame('python', $method->invoke($this->service, 'py'));
    }


    public function testDetectLanguageReturnsPlaintext(): void
    {
        $method = new \ReflectionMethod($this->service, 'detectLanguage');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, 'any code');
        $this->assertSame('plaintext', $result);
    }

    public function testHighlightCodeWithNullLanguageUsesAutoDetection(): void
    {
        $code = 'echo "Hello"; // some comment';
        $html = $this->service->highlightCode($code, null);
        // Since detectLanguage returns 'plaintext', expect generic block; but the code content should be present.
        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('echo', $html);
        $this->assertStringNotContainsString('language-php', $html);
    }

    public function testHighlightCodeOptionsFalseRemovesFeatures(): void
    {
        $code = '<?php echo 1; ?>';
        $html = $this->service->highlightCode($code, 'php', ['lineNumbers' => false, 'copyButton' => false]);
        $this->assertStringNotContainsString('line-numbers', $html);
        $this->assertStringNotContainsString('copy-code-btn', $html);
    }

    public function testNormalizeLanguageReturnsLowercaseForUnknown(): void
    {
        $method = new \ReflectionMethod($this->service, 'normalizeLanguage');
        $method->setAccessible(true);
        $this->assertSame('unknownlang', $method->invoke($this->service, 'unknownlang'));
    }

    public function testGetLanguageComponentMapContainsPhp(): void
    {
        $method = new \ReflectionMethod($this->service, 'getLanguageComponentMap');
        $method->setAccessible(true);
        $map = $method->invoke($this->service);
        $this->assertIsArray($map);
        $this->assertArrayHasKey('php', $map);
        $this->assertSame('php', $map['php']);
    }
}
