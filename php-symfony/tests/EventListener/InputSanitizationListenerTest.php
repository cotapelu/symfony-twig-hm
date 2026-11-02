<?php

declare(strict_types=1);

namespace App\Tests\EventListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\EventListener\InputSanitizationListener;
use PHPUnit\Framework\TestCase;
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

    public function testOnKernelRequestSanitizesInput(): void
    {
        $request = new Request();
        $request->request->set('name', 'John<script>alert("xss")</script>Doe');
        $request->request->set('email', 'test@example.com');
        $request->query->set('search', 'SELECT * FROM users');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->onKernelRequest($event);

        // Script tags should be removed
        $this->assertEquals('JohnDoe', $request->request->get('name'));
        // SQL keywords should be removed
        $this->assertEquals('test@example.com', $request->request->get('email'));
        $this->assertEquals('', $request->query->get('search'));
    }
}