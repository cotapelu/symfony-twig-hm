<?php

declare(strict_types=1);

namespace App\Tests\Controller\Government\Api;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Api\Controller\DocumentController;
use App\Government\Application\Service\DocumentService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class DocumentControllerTest extends TestCase
{
    private DocumentController $controller;
    private DocumentService|MockObject $documentService;

    protected function setUp(): void
    {
        $this->documentService = $this->createMock(DocumentService::class);
        $this->controller = new DocumentController($this->documentService);
    }

    public function testCreateDocumentSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Test Document',
                'type' => 'circular',
                'content' => 'Document content',
                'issuedBy' => 'Ministry of Health',
                'documentNumber' => 'DOC-2024-001',
                'issuedDate' => '2024-01-01',
                'signedBy' => 'John Doe',
            ])
        );

        $expectedResponse = [
            'id' => 'doc123',
            'title' => 'Test Document',
            'status' => 'draft',
        ];

        $this->documentService
            ->expects($this->once())
            ->method('createDocument')
            ->willReturn($expectedResponse);

        $result = $this->controller->create($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('doc123', $data['data']['id']);
    }

    public function testListDocumentsWithFilters(): void
    {
        $request = new Request([
            'page' => '1',
            'perPage' => '10',
            'type' => 'circular',
            'status' => 'published',
        ]);

        $documents = [
            ['id' => 'doc1', 'title' => 'Document 1'],
            ['id' => 'doc2', 'title' => 'Document 2'],
        ];

        $this->documentService
            ->expects($this->once())
            ->method('listDocuments')
            ->with(
                ['type' => 'circular', 'status' => 'published'],
                1,
                10
            )
            ->willReturn($documents);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['data']);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(10, $data['pagination']['perPage']);
    }

    public function testListDocumentsWithoutFilters(): void
    {
        $request = new Request();

        $documents = [];

        $this->documentService
            ->expects($this->once())
            ->method('listDocuments')
            ->with([], 1, 20)
            ->willReturn($documents);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(20, $data['pagination']['perPage']);
    }

    public function testSearchDocumentsSuccess(): void
    {
        $request = new Request(['q' => 'health', 'limit' => '10']);

        $documents = [
            ['id' => 'doc1', 'title' => 'Health Guidelines'],
        ];

        $this->documentService
            ->expects($this->once())
            ->method('searchDocuments')
            ->with('health', 10)
            ->willReturn($documents);

        $result = $this->controller->search($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['data']);
    }

    public function testSearchDocumentsEmptyQuery(): void
    {
        $request = new Request(['q' => '']);

        $result = $this->controller->search($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Search keyword is required', $data['error']);
    }

    public function testShowDocumentSuccess(): void
    {
        $document = [
            'id' => 'doc123',
            'title' => 'Test Document',
            'content' => 'Content',
        ];

        $this->documentService
            ->expects($this->once())
            ->method('getDocumentById')
            ->with('doc123')
            ->willReturn($document);

        $result = $this->controller->show('doc123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('doc123', $data['data']['id']);
    }

    public function testShowDocumentNotFound(): void
    {
        $this->documentService
            ->expects($this->once())
            ->method('getDocumentById')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->controller->show('notfound');

        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Document not found', $data['error']);
    }

    public function testPublishDocumentSuccess(): void
    {
        $this->documentService
            ->expects($this->once())
            ->method('publishDocument')
            ->with('doc123')
            ->willReturn(true);

        $result = $this->controller->publish('doc123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Document published successfully', $data['message']);
    }

    public function testPublishDocumentNotFound(): void
    {
        $this->documentService
            ->expects($this->once())
            ->method('publishDocument')
            ->with('notfound')
            ->willReturn(false);

        $result = $this->controller->publish('notfound');

        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Document not found', $data['error']);
    }

    public function testArchiveDocumentSuccess(): void
    {
        $this->documentService
            ->expects($this->once())
            ->method('archiveDocument')
            ->with('doc123')
            ->willReturn(true);

        $result = $this->controller->archive('doc123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Document archived successfully', $data['message']);
    }

    public function testArchiveDocumentNotFound(): void
    {
        $this->documentService
            ->expects($this->once())
            ->method('archiveDocument')
            ->with('notfound')
            ->willReturn(false);

        $result = $this->controller->archive('notfound');

        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Document not found', $data['error']);
    }

    public function testStatistics(): void
    {
        $stats = [
            'total' => 100,
            'published' => 80,
            'draft' => 20,
            'archived' => 5,
        ];

        $this->documentService
            ->expects($this->once())
            ->method('getStatistics')
            ->willReturn($stats);

        $result = $this->controller->statistics();

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(100, $data['data']['total']);
        $this->assertEquals(80, $data['data']['published']);
    }

    public function testListDocumentsWithOnlyTypeFilter(): void
    {
        $request = new Request(['type' => 'circular']);

        $documents = [];

        $this->documentService
            ->expects($this->once())
            ->method('listDocuments')
            ->with(['type' => 'circular'], 1, 20)
            ->willReturn($documents);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testListDocumentsWithOnlyStatusFilter(): void
    {
        $request = new Request(['status' => 'draft']);

        $documents = [];

        $this->documentService
            ->expects($this->once())
            ->method('listDocuments')
            ->with(['status' => 'draft'], 1, 20)
            ->willReturn($documents);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testSearchDocumentsDefaultLimit(): void
    {
        $request = new Request(['q' => 'test']);

        $documents = [];

        $this->documentService
            ->expects($this->once())
            ->method('searchDocuments')
            ->with('test', 20)
            ->willReturn($documents);

        $result = $this->controller->search($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testCreateDocumentWithOptionalFields(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Test Document',
                'type' => 'directive',
                'content' => 'Content',
                'issuedBy' => 'Ministry',
                'tags' => ['tag1', 'tag2'],
            ])
        );

        $expectedResponse = ['id' => 'doc123'];

        $this->documentService
            ->expects($this->once())
            ->method('createDocument')
            ->willReturn($expectedResponse);

        $result = $this->controller->create($request);

        $this->assertEquals(201, $result->getStatusCode());
    }
}
