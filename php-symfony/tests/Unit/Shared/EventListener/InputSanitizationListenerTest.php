<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\EventListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\EventListener\InputSanitizationListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AllowMockObjectsWithoutExpectations]

class InputSanitizationListenerTest extends TestCase
{
    private InputSanitizationListener $listener;

    protected function setUp(): void
    {
        $this->listener = new InputSanitizationListener();
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    public function testOnKernelRequestSanitizesQueryAndRequest(): void
    {
        $request = new Request();
        $request->query->set('name', "  <script>alert('xss')</script>  ");
        $request->request->set('email', "  union select * from users  ");

        $event = $this->createRequestEvent($request);
        $this->listener->onKernelRequest($event);

        $this->assertEquals('', $request->query->get('name')); // script tags + content removed
        $this->assertEquals('', $request->request->get('email')); // SQL keyword => empty
    }

    public function testSanitizeArrayRemovesNullBytes(): void
    {
        $bag = new ParameterBag([
            'field' => "test\0nullbyte",
        ]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals('testnullbyte', $bag->get('field'));
    }

    public function testSanitizeArrayTrimsWhitespace(): void
    {
        $bag = new ParameterBag([
            'field' => "   spaced   ",
        ]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals('spaced', $bag->get('field'));
    }

    public function testSanitizeArrayRemovesScriptTags(): void
    {
        $bag = new ParameterBag([
            'html' => "<script>alert('xss')</script>safe",
        ]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals('safe', $bag->get('html'));
    }

    public function testSanitizeArrayRemovesScriptTagsWithAttributes(): void
    {
        $bag = new ParameterBag([
            'html' => "<script type='text/javascript'>malicious()</script>ok",
        ]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals('ok', $bag->get('html'));
    }

    public function testSanitizeArrayClearsSqlKeywords(): void
    {
        $keywords = ['union', 'SELECT', 'Insert', 'DELETE', 'Update', 'drop', 'CREATE', 'ALTER'];
        $bag = new ParameterBag(['sql' => "  union select * from users  "]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals('', $bag->get('sql'));
    }

    public function testSanitizeArrayPreservesNonStringValues(): void
    {
        $bag = new ParameterBag([
            'number' => 42,
            'bool' => true,
            'null' => null,
            'array' => ['nested' => 'value'],
        ]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals(42, $bag->get('number'));
        $this->assertTrue($bag->get('bool'));
        $this->assertNull($bag->get('null'));
        $this->assertEquals(['nested' => 'value'], $bag->get('array'));
    }

    public function testSanitizeArrayHandlesNestedArrays(): void
    {
        $bag = new ParameterBag([
            'user' => [
                'name' => "  <b>bold</b>  ",
                'email' => "  union select  ",
            ],
        ]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals("<b>bold</b>", $bag->get('user')['name']);
        $this->assertEquals('', $bag->get('user')['email']);
    }

    public function testSanitizeArrayDoesNotAffectEmptyStrings(): void
    {
        $bag = new ParameterBag([
            'field' => '',
        ]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals('', $bag->get('field'));
    }

    public function testSanitizeArrayRemovesScriptTagsCaseInsensitive(): void
    {
        $bag = new ParameterBag([
            'html' => "<SCRIPT>alert('xss')</SCRIPT>ok",
        ]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals('ok', $bag->get('html'));
    }

    public function testSanitizeArraySqlKeywordsCaseInsensitive(): void
    {
        $bag = new ParameterBag([
            'sql' => "UNION SELECT INSERT DELETE UPDATE DROP CREATE ALTER",
        ]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals('', $bag->get('sql'));
    }

    public function testSanitizeArrayOnlyWholeWords(): void
    {
        // "unionized" should not trigger removal because it's not whole word "union"
        $bag = new ParameterBag([
            'word' => 'unionized',
        ]);

        $reflection = new \ReflectionMethod($this->listener, 'sanitizeArray');
        $reflection->setAccessible(true);
        $reflection->invoke($this->listener, $bag);

        $this->assertEquals('unionized', $bag->get('word'));
    }

    public function testGetSubscribedEvents(): void
    {
        $events = InputSanitizationListener::getSubscribedEvents();
        $this->assertArrayHasKey('kernel.request', $events);
        $this->assertEquals(['onKernelRequest', 10], $events['kernel.request']);
    }
}
