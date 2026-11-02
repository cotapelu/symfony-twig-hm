<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Middleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Middleware\RequestSizeLimitMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AllowMockObjectsWithoutExpectations]

class RequestSizeLimitMiddlewareTest extends TestCase
{
    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    #[DataProvider('provideContentLengths')]
    public function testOnKernelRequestAllowsWithinLimit(?string $contentLength, int $expectedBehavior): void
    {
        $middleware = new RequestSizeLimitMiddleware();

        $request = new Request();
        if ($contentLength !== null) {
            $request->headers->set('Content-Length', $contentLength);
        }
        $event = $this->createRequestEvent($request);

        $middleware->onKernelRequest($event);

        $response = $event->getResponse();
        if ($expectedBehavior === 1) {
            $this->assertNull($response);
        } else {
            $this->assertNotNull($response);
            $this->assertEquals(413, $response->getStatusCode());
            $this->assertEquals(['error' => 'Request entity too large'], json_decode($response->getContent(), true));
        }
    }

    public static function provideContentLengths(): array
    {
        return [
            'no header' => [null, 1],
            'zero length' => ['0', 1],
            'small size' => ['1024', 1],
            'exactly at limit' => [strval(10485760), 1],
            'one byte over' => [strval(10485761), 0],
            'much larger' => ['50000000', 0],
            'negative' => ['-1', 1], // cast to int -1 < limit, allowed
        ];
    }

    public function testOnKernelRequestDoesNotModifyWhenNotOverLimit(): void
    {
        $middleware = new RequestSizeLimitMiddleware();

        $request = new Request();
        $request->headers->set('Content-Length', '1024');
        $event = $this->createRequestEvent($request);

        $middleware->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestRejectsWhenOverLimit(): void
    {
        $middleware = new RequestSizeLimitMiddleware();

        $request = new Request();
        $request->headers->set('Content-Length', '20000000'); // 20 MB
        $event = $this->createRequestEvent($request);

        $middleware->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(413, $response->getStatusCode());
        $this->assertEquals(['error' => 'Request entity too large'], json_decode($response->getContent(), true));
    }

    public function testOnKernelRequestSetsResponseWithCorrectContentType(): void
    {
        $middleware = new RequestSizeLimitMiddleware();

        $request = new Request();
        $request->headers->set('Content-Length', '20000000');
        $event = $this->createRequestEvent($request);

        $middleware->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    public function testMaxContentLengthConstant(): void
    {
        $reflection = new \ReflectionClass(RequestSizeLimitMiddleware::class);
        $constant = $reflection->getConstant('MAX_CONTENT_LENGTH');
        $this->assertEquals(10485760, $constant); // 10 MB
    }
}
