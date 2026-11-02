<?php

declare(strict_types=1);

namespace Tests\Unit\Feedback\Api\Controller;

use App\Feedback\Api\Controller\FeedbackController;
use App\Feedback\Application\Service\FeedbackService;
use App\Feedback\Domain\Entity\Feedback;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FeedbackControllerSimpleTest extends TestCase
{
    private FeedbackService $serviceMock;
    private FeedbackController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(FeedbackService::class);
        $this->controller = new FeedbackController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function createJsonRequest(array $data): Request
    {
        return Request::create('/api/feedback', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    public function testSubmitFeedback_InvalidJson_Returns400(): void
    {
        $request = Request::create('/api/feedback', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->submitFeedback($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSubmitFeedback_MissingRequiredFields_Returns400(): void
    {
        $request = $this->createJsonRequest([]);
        $response = $this->controller->submitFeedback($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSubmitFeedback_Success_Returns201(): void
    {
        $feedback = $this->createMock(Feedback::class);
        $feedback->method('toArray')->willReturn(['id' => 'abc123']);
        $this->serviceMock->expects($this->once())
            ->method('submitFeedback')
            ->with('complaint', 'WC01', 'Title', 'Desc', 'John', null, null, null, null, null, null, false)
            ->willReturn($feedback);
        $request = $this->createJsonRequest([
            'type' => 'complaint',
            'wardCode' => 'WC01',
            'title' => 'Title',
            'description' => 'Desc',
            'citizenName' => 'John'
        ]);
        $response = $this->controller->submitFeedback($request);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
    }

    public function testGetFeedback_MissingId_Returns400(): void
    {
        $response = $this->controller->getFeedback('');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetFeedback_NotFound_Returns404(): void
    {
        $this->serviceMock->expects($this->once())->method('getFeedback')->with('123')->willReturn(null);
        $response = $this->controller->getFeedback('123');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetFeedback_Success_Returns200(): void
    {
        $feedback = $this->createMock(Feedback::class);
        $this->serviceMock->expects($this->once())->method('getFeedback')->with('123')->willReturn($feedback);
        $response = $this->controller->getFeedback('123');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetFeedbackByWard_MissingWard_Returns400(): void
    {
        $request = Request::create('/api/feedback/ward/', 'GET');
        $response = $this->controller->getFeedbackByWard($request, '');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetFeedbackByWard_Success_Returns200(): void
    {
        $feedback = $this->createMock(Feedback::class);
        $this->serviceMock->expects($this->once())->method('getFeedbackByWard')->with('WC01', null, 50)->willReturn([$feedback]);
        $request = Request::create('/api/feedback/ward/WC01', 'GET');
        $response = $this->controller->getFeedbackByWard($request, 'WC01');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetMyFeedbacks_MissingPhone_Returns400(): void
    {
        $request = Request::create('/api/feedback/my', 'GET');
        $response = $this->controller->getMyFeedbacks($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetMyFeedbacks_Success_Returns200(): void
    {
        $feedback = $this->createMock(Feedback::class);
        $this->serviceMock->expects($this->once())->method('getFeedbackByPhone')->with('+84912345678')->willReturn([$feedback]);
        $request = Request::create('/api/feedback/my', 'GET', ['phone' => '+84912345678']);
        $response = $this->controller->getMyFeedbacks($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testAssignFeedback_MissingId_Returns400(): void
    {
        $request = $this->createJsonRequest(['officerId' => 'OFF1']);
        $response = $this->controller->assignFeedback($request, '');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignFeedback_MissingOfficerId_Returns400(): void
    {
        $request = $this->createJsonRequest([]);
        $response = $this->controller->assignFeedback($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignFeedback_NotFound_Returns404(): void
    {
        $this->serviceMock->expects($this->once())->method('assignFeedback')->with('123', 'OFF1')->willReturn(null);
        $request = $this->createJsonRequest(['officerId' => 'OFF1']);
        $response = $this->controller->assignFeedback($request, '123');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAssignFeedback_Success_Returns200(): void
    {
        $feedback = $this->createMock(Feedback::class);
        $this->serviceMock->expects($this->once())->method('assignFeedback')->with('123', 'OFF1')->willReturn($feedback);
        $request = $this->createJsonRequest(['officerId' => 'OFF1']);
        $response = $this->controller->assignFeedback($request, '123');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testResolveFeedback_MissingId_Returns400(): void
    {
        $request = $this->createJsonRequest(['summary' => 'Fixed', 'resolvedBy' => 'OFF1']);
        $response = $this->controller->resolveFeedback($request, '');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testResolveFeedback_MissingFields_Returns400(): void
    {
        $request = $this->createJsonRequest(['summary' => 'Fixed']);
        $response = $this->controller->resolveFeedback($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testResolveFeedback_NotFound_Returns404(): void
    {
        $this->serviceMock->expects($this->once())->method('resolveFeedback')->with('123', 'Fixed', 'OFF1')->willReturn(null);
        $request = $this->createJsonRequest(['summary' => 'Fixed', 'resolvedBy' => 'OFF1']);
        $response = $this->controller->resolveFeedback($request, '123');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testResolveFeedback_Success_Returns200(): void
    {
        $feedback = $this->createMock(Feedback::class);
        $this->serviceMock->expects($this->once())->method('resolveFeedback')->with('123', 'Fixed', 'OFF1')->willReturn($feedback);
        $request = $this->createJsonRequest(['summary' => 'Fixed', 'resolvedBy' => 'OFF1']);
        $response = $this->controller->resolveFeedback($request, '123');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCloseFeedback_MissingId_Returns400(): void
    {
        $response = $this->controller->closeFeedback('');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCloseFeedback_NotFound_Returns404(): void
    {
        $this->serviceMock->expects($this->once())->method('closeFeedback')->with('123')->willReturn(null);
        $response = $this->controller->closeFeedback('123');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCloseFeedback_Success_Returns200(): void
    {
        $feedback = $this->createMock(Feedback::class);
        $this->serviceMock->expects($this->once())->method('closeFeedback')->with('123')->willReturn($feedback);
        $response = $this->controller->closeFeedback('123');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRateResolution_MissingId_Returns400(): void
    {
        $request = $this->createJsonRequest(['satisfaction' => 5]);
        $response = $this->controller->rateResolution($request, '');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRateResolution_MissingSatisfaction_Returns400(): void
    {
        $request = $this->createJsonRequest([]);
        $response = $this->controller->rateResolution($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRateResolution_InvalidSatisfaction_Returns400(): void
    {
        $request = $this->createJsonRequest(['satisfaction' => 0]);
        $response = $this->controller->rateResolution($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRateResolution_InvalidSatisfaction_High_Returns400(): void
    {
        $request = $this->createJsonRequest(['satisfaction' => 6]);
        $response = $this->controller->rateResolution($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRateResolution_NotFound_Returns404(): void
    {
        $this->serviceMock->expects($this->once())->method('rateResolution')->with('123', 5)->willReturn(null);
        $request = $this->createJsonRequest(['satisfaction' => 5]);
        $response = $this->controller->rateResolution($request, '123');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRateResolution_Success_Returns200(): void
    {
        $feedback = $this->createMock(Feedback::class);
        $this->serviceMock->expects($this->once())->method('rateResolution')->with('123', 5)->willReturn($feedback);
        $request = $this->createJsonRequest(['satisfaction' => 5]);
        $response = $this->controller->rateResolution($request, '123');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetStatistics_MissingWard_Returns400(): void
    {
        $request = Request::create('/api/feedback/statistics/', 'GET');
        $response = $this->controller->getStatistics($request, '');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetStatistics_Success_Returns200(): void
    {
        $this->serviceMock->expects($this->once())->method('getStatistics')->with('WC01', null, null)->willReturn(['total' => 10]);
        $request = Request::create('/api/feedback/statistics/WC01', 'GET');
        $response = $this->controller->getStatistics($request, 'WC01');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertSame(10, $content['data']['total']);
    }
}
