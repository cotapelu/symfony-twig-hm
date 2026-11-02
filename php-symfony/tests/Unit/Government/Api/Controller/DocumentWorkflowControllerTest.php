<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government\Api\Controller;

use PHPUnit\Framework\TestCase;
use App\Government\Api\Controller\DocumentWorkflowController;
use App\Government\Application\Service\DocumentWorkflowService;
use App\Government\Domain\Entity\DocumentWorkflow;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DocumentWorkflowControllerTest extends TestCase
{
    private DocumentWorkflowService $service;
    private DocumentWorkflowController $controller;

    protected function setUp(): void
    {
        $this->service = $this->createMock(DocumentWorkflowService::class);
        $this->controller = new DocumentWorkflowController($this->service);
        $this->setupContainer();
    }

    private function setupContainer(): void
    {
        $container = new ContainerBuilder();
        $container->set('serializer', new \Symfony\Component\Serializer\Serializer([], [new \Symfony\Component\Serializer\Encoder\JsonEncoder()]));
        $this->controller->setContainer($container);
    }

    private function jsonRequest(array $data = [], string $method = 'POST'): Request
    {
        return Request::create('/api/government/document-workflows/any', $method, [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    private function mockWorkflowReturn(array $data = []): DocumentWorkflow
    {
        $workflow = $this->createMock(DocumentWorkflow::class);
        $workflow->method('toArray')->willReturn($data ?: ['id' => 'wf-'.uniqid()]);
        return $workflow;
    }

    // ==================== submit ====================

    public function testSubmitForReviewSuccess(): void
    {
        $id = 'doc-'.uniqid();
        $workflow = $this->mockWorkflowReturn(['id' => $id, 'status' => 'pending']);

        $this->service->expects($this->once())
            ->method('submitForReview')
            ->with($id)
            ->willReturn($workflow);

        $response = $this->controller->submitForReview($id);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertSame('Document submitted for review', $json['message']);
        $this->assertSame(['id' => $id, 'status' => 'pending'], $json['data']);
    }

    public function testSubmitForReviewServiceThrowsException(): void
    {
        $id = 'doc-'.uniqid();

        $this->service->expects($this->once())
            ->method('submitForReview')
            ->with($id)
            ->willThrowException(new \Exception('Invalid document'));

        $response = $this->controller->submitForReview($id);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertSame('Invalid document', $json['error']);
    }

    // ==================== approve ====================

    public function testApproveSuccess(): void
    {
        $id = 'doc-'.uniqid();
        $approvedBy = 'user-'.uniqid();
        $request = $this->jsonRequest(['approvedBy' => $approvedBy, 'comments' => 'Looks good']);
        $workflow = $this->mockWorkflowReturn(['status' => 'approved']);

        $this->service->expects($this->once())
            ->method('approveDocument')
            ->with($id, $approvedBy, 'Looks good')
            ->willReturn($workflow);

        $response = $this->controller->approve($id, $request);

        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertSame('Document approved successfully', $json['message']);
    }

    public function testApproveMissingApprovedBy(): void
    {
        $id = 'doc-'.uniqid();
        $request = $this->jsonRequest([]);

        $response = $this->controller->approve($id, $request);

        $this->assertSame(400, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('Approved by is required', $json['error']);
    }

    public function testApproveInvalidJson(): void
    {
        $id = 'doc-'.uniqid();
        $request = Request::create('/api/government/document-workflows/any', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');

        $response = $this->controller->approve($id, $request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testApproveServiceThrowsException(): void
    {
        $id = 'doc-'.uniqid();
        $request = $this->jsonRequest(['approvedBy' => 'user-123']);

        $this->service->expects($this->once())
            ->method('approveDocument')
            ->willThrowException(new \Exception('Cannot approve'));

        $response = $this->controller->approve($id, $request);

        $this->assertSame(400, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertSame('Cannot approve', $json['error']);
    }

    // ==================== reject ====================

    public function testRejectSuccess(): void
    {
        $id = 'doc-'.uniqid();
        $rejectedBy = 'user-'.uniqid();
        $reason = 'Not acceptable';
        $request = $this->jsonRequest(['rejectedBy' => $rejectedBy, 'reason' => $reason]);
        $workflow = $this->mockWorkflowReturn(['status' => 'rejected']);

        $this->service->expects($this->once())
            ->method('rejectDocument')
            ->with($id, $rejectedBy, $reason)
            ->willReturn($workflow);

        $response = $this->controller->reject($id, $request);

        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertSame('Document rejected', $json['message']);
    }

    public function testRejectMissingReason(): void
    {
        $id = 'doc-'.uniqid();
        $request = $this->jsonRequest(['rejectedBy' => 'user-123']);

        $response = $this->controller->reject($id, $request);

        $this->assertSame(400, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('Rejection reason is required', $json['error']);
    }

    public function testRejectInvalidJson(): void
    {
        $id = 'doc-'.uniqid();
        $request = Request::create('/api/government/document-workflows/any', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'not json');

        $response = $this->controller->reject($id, $request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRejectServiceThrowsException(): void
    {
        $id = 'doc-'.uniqid();
        $request = $this->jsonRequest(['rejectedBy' => 'user-456', 'reason' => 'Bad']);

        $this->service->expects($this->once())
            ->method('rejectDocument')
            ->willThrowException(new \Exception('Rejection failed'));

        $response = $this->controller->reject($id, $request);

        $this->assertSame(400, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertSame('Rejection failed', $json['error']);
    }

    // ==================== publish ====================

    public function testPublishSuccess(): void
    {
        $id = 'doc-'.uniqid();
        $workflow = $this->mockWorkflowReturn(['status' => 'published']);

        $this->service->expects($this->once())
            ->method('publishDocument')
            ->with($id)
            ->willReturn($workflow);

        $response = $this->controller->publish($id);

        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertSame('Document published successfully', $json['message']);
    }

    public function testPublishServiceThrowsException(): void
    {
        $id = 'doc-'.uniqid();

        $this->service->expects($this->once())
            ->method('publishDocument')
            ->with($id)
            ->willThrowException(new \Exception('Publish error'));

        $response = $this->controller->publish($id);

        $this->assertSame(400, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertSame('Publish error', $json['error']);
    }

    // ==================== archive ====================

    public function testArchiveSuccess(): void
    {
        $id = 'doc-'.uniqid();
        $workflow = $this->mockWorkflowReturn(['status' => 'archived']);

        $this->service->expects($this->once())
            ->method('archiveDocument')
            ->with($id)
            ->willReturn($workflow);

        $response = $this->controller->archive($id);

        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertSame('Document archived successfully', $json['message']);
    }

    public function testArchiveServiceThrowsException(): void
    {
        $id = 'doc-'.uniqid();

        $this->service->expects($this->once())
            ->method('archiveDocument')
            ->with($id)
            ->willThrowException(new \Exception('Archive failed'));

        $response = $this->controller->archive($id);

        $this->assertSame(400, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertSame('Archive failed', $json['error']);
    }
}
