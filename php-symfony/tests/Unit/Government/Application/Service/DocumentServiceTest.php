<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Application\Service;

use App\Government\Application\Service\DocumentService;
use App\Government\Contracts\DTO\CreateDocumentRequest;
use App\Government\Contracts\DTO\DocumentResponse;
use App\Government\Domain\Entity\Document;
use App\Government\Domain\Repository\DocumentRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DocumentServiceTest extends TestCase
{
    private DocumentRepositoryInterface&MockObject $repoMock;
    private DocumentService $service;

    protected function setUp(): void
    {
        $this->repoMock = $this->createMock(DocumentRepositoryInterface::class);
        $this->service = new DocumentService($this->repoMock);
    }

    private function createDocumentEntity(array $overrides = []): Document
    {
        $title = $overrides['title'] ?? 'Test Document';
        $type = $overrides['type'] ?? Document::TYPE_DECREE;
        $content = $overrides['content'] ?? 'Content';
        $issuedBy = $overrides['issuedBy'] ?? 'Department';
        $docNumber = $overrides['documentNumber'] ?? null;
        $issuedDate = $overrides['issuedDate'] ?? null;
        $effectiveDate = $overrides['effectiveDate'] ?? null;
        $signedBy = $overrides['signedBy'] ?? null;
        $summary = $overrides['summary'] ?? null;
        $doc = new Document(
            $title,
            $type,
            $content,
            $issuedBy,
            $docNumber,
            $issuedDate,
            $effectiveDate,
            $signedBy,
            $summary
        );
        // Override ID if provided
        if (isset($overrides['id'])) {
            $ref = new \ReflectionClass($doc);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($doc, $overrides['id']);
        }
        // Override status if needed
        if (isset($overrides['status'])) {
            $ref = new \ReflectionClass($doc);
            $prop = $ref->getProperty('status');
            $prop->setAccessible(true);
            $prop->setValue($doc, $overrides['status']);
        }
        // Initialize history property? getHistory builds from createdAt/updatedAt, no need.
        return $doc;
    }

    private function createCreateDocumentRequest(array $data): CreateDocumentRequest
    {
        return new CreateDocumentRequest(
            $data['title'] ?? 'New Doc',
            $data['type'] ?? Document::TYPE_DECREE,
            $data['content'] ?? 'Content',
            $data['issuedBy'] ?? 'Issuer',
            $data['documentNumber'] ?? null,
            $data['issuedDate'] ?? null,
            $data['effectiveDate'] ?? null,
            $data['signedBy'] ?? null,
            $data['summary'] ?? null,
            $data['tags'] ?? []
        );
    }

    // ==================== createDocument ====================

    public function testCreateDocument_Success(): void
    {
        $requestData = [
            'title' => 'New Doc',
            'type' => Document::TYPE_DECISION,
            'content' => 'Content',
            'issuedBy' => 'Office',
            'documentNumber' => 'DN-001',
            'tags' => ['tag1'],
        ];
        $request = $this->createCreateDocumentRequest($requestData);
        $expectedDoc = $this->createDocumentEntity([
            'title' => $requestData['title'],
            'type' => $requestData['type'],
            'content' => $requestData['content'],
            'issuedBy' => $requestData['issuedBy'],
            'documentNumber' => $requestData['documentNumber'],
        ]);

        $this->repoMock->expects($this->once())
            ->method('save')
            ->with($this->callback(function($doc) use ($requestData) {
                return $doc->getTitle() === $requestData['title']
                    && $doc->getDocumentNumber() === $requestData['documentNumber']
                    && $doc->getType() === $requestData['type'];
            }));

        // The service returns DocumentResponse from the entity right after creation, so we don't need findById if we don't stub it.

        $result = $this->service->createDocument($request);
        $this->assertInstanceOf(DocumentResponse::class, $result);
        $this->assertSame($requestData['title'], $result->title);
    }

    // ==================== getDocumentById ====================

    public function testGetDocumentById_Found(): void
    {
        $doc = $this->createDocumentEntity(['id' => 'doc123']);
        $this->repoMock->method('findById')->with('doc123')->willReturn($doc);
        $result = $this->service->getDocumentById('doc123');
        $this->assertInstanceOf(DocumentResponse::class, $result);
        $this->assertSame($doc->getId()->toString(), $result->id);
    }

    public function testGetDocumentById_NotFound(): void
    {
        $this->repoMock->method('findById')->with('unknown')->willReturn(null);
        $result = $this->service->getDocumentById('unknown');
        $this->assertNull($result);
    }

    // ==================== listDocuments ====================

    public function testListDocuments_NoFilters(): void
    {
        $docs = [$this->createDocumentEntity(), $this->createDocumentEntity()];
        $this->repoMock->method('findAll')->with([], 1, 20)->willReturn($docs);
        $result = $this->service->listDocuments([], 1, 20);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(DocumentResponse::class, $result[0]);
    }

    public function testListDocuments_WithFilters(): void
    {
        $docs = [$this->createDocumentEntity()];
        $this->repoMock->method('findAll')->with(['status' => Document::STATUS_PUBLISHED], 2, 50)->willReturn($docs);
        $result = $this->service->listDocuments(['status' => Document::STATUS_PUBLISHED], 2, 50);
        $this->assertCount(1, $result);
    }

    // ==================== searchDocuments ====================

    public function testSearchDocuments(): void
    {
        $docs = [$this->createDocumentEntity()];
        $this->repoMock->method('searchByTitle')->with('keyword', 10)->willReturn($docs);
        $result = $this->service->searchDocuments('keyword', 10);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(DocumentResponse::class, $result[0]);
    }

    // ==================== publishDocument ====================

    public function testPublishDocument_Success(): void
    {
        $doc = $this->createDocumentEntity();
        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $this->repoMock->expects($this->once())->method('save')->with($this->callback(fn($d) => $d->getStatus() === Document::STATUS_PUBLISHED));
        $result = $this->service->publishDocument('doc1');
        $this->assertTrue($result);
    }

    public function testPublishDocument_NotFound(): void
    {
        $this->repoMock->method('findById')->with('unknown')->willReturn(null);
        $result = $this->service->publishDocument('unknown');
        $this->assertFalse($result);
    }

    // ==================== archiveDocument ====================

    public function testArchiveDocument_Success(): void
    {
        $doc = $this->createDocumentEntity();
        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $this->repoMock->expects($this->once())->method('save');
        $result = $this->service->archiveDocument('doc1');
        $this->assertTrue($result);
        $this->assertSame(Document::STATUS_ARCHIVED, $doc->getStatus());
    }

    public function testArchiveDocument_NotFound(): void
    {
        $this->repoMock->method('findById')->with('unknown')->willReturn(null);
        $result = $this->service->archiveDocument('unknown');
        $this->assertFalse($result);
    }

    // ==================== updateDocument ====================

    public function testUpdateDocument_Found(): void
    {
        $doc = $this->createDocumentEntity(['title' => 'Old Title']);
        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $this->repoMock->expects($this->once())->method('save');

        $result = $this->service->updateDocument('doc1', ['title' => 'New Title', 'content' => 'New Content']);
        $this->assertInstanceOf(DocumentResponse::class, $result);
        $this->assertSame('New Title', $doc->getTitle());
        $this->assertSame('New Content', $doc->getContent());
    }

    public function testUpdateDocument_NotFound(): void
    {
        $this->repoMock->method('findById')->with('unknown')->willReturn(null);
        $result = $this->service->updateDocument('unknown', ['title' => 'New']);
        $this->assertNull($result);
    }

    public function testUpdateDocument_AddTags(): void
    {
        $doc = $this->createDocumentEntity(['tags' => []]);
        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $this->repoMock->expects($this->once())->method('save');
        $result = $this->service->updateDocument('doc1', ['tags' => ['t1', 't2']]);
        $this->assertInstanceOf(DocumentResponse::class, $result);
        $this->assertSame(['t1', 't2'], $doc->getTags());
    }

    // ==================== addAttachment ====================

    public function testAddAttachment_Success(): void
    {
        $doc = $this->createDocumentEntity(['attachments' => []]);
        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $this->repoMock->expects($this->once())->method('save');
        $result = $this->service->addAttachment('doc1', 'file.pdf', '/path/file.pdf', 'application/pdf');
        $this->assertTrue($result);
        $this->assertCount(1, $doc->getAttachments());
    }

    public function testAddAttachment_NotFound(): void
    {
        $this->repoMock->method('findById')->with('unknown')->willReturn(null);
        $result = $this->service->addAttachment('unknown', 'f', '/p', 'mime');
        $this->assertFalse($result);
    }

    // ==================== getStatistics ====================

    public function testGetStatistics(): void
    {
        $this->repoMock->method('count')->willReturnCallback(function($filters) {
            if ($filters === []) {
                return 100; // total
            }
            return match($filters['status'] ?? null) {
                Document::STATUS_PUBLISHED => 50,
                Document::STATUS_DRAFT => 10,
                Document::STATUS_PENDING => 5,
                Document::STATUS_APPROVED => 20,
                Document::STATUS_REJECTED => 2,
                Document::STATUS_ARCHIVED => 15,
                default => 0,
            };
        });
        $stats = $this->service->getStatistics();
        $this->assertSame(100, $stats['totalDocuments']);
        $this->assertSame(50, $stats['published']);
        $this->assertSame(10, $stats['drafts']);
        $this->assertSame(15, $stats['archived']);
    }

    // ==================== submitForReview ====================

    public function testSubmitForReview_Success(): void
    {
        $doc = $this->createDocumentEntity(); // starts as draft
        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $this->repoMock->expects($this->once())->method('save');
        $result = $this->service->submitForReview('doc1');
        $this->assertInstanceOf(DocumentResponse::class, $result);
        $this->assertSame(Document::STATUS_PENDING, $doc->getStatus());
    }

    public function testSubmitForReview_NotFound(): void
    {
        $this->repoMock->method('findById')->with('unknown')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Document not found');
        $this->service->submitForReview('unknown');
    }

    // ==================== reviewDocument ====================

    public function testReviewDocument_Approved(): void
    {
        $doc = $this->createDocumentEntity();
        // Simulate pending state
        $ref = new \ReflectionClass($doc);
        $prop = $ref->getProperty('status');
        $prop->setAccessible(true);
        $prop->setValue($doc, Document::STATUS_PENDING);

        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $this->repoMock->expects($this->once())->method('save');

        $result = $this->service->reviewDocument('doc1', 'approved', 'Good', 'reviewer1');
        $this->assertInstanceOf(DocumentResponse::class, $result);
        $this->assertSame(Document::STATUS_APPROVED, $doc->getStatus());
    }

    public function testReviewDocument_Rejected(): void
    {
        $doc = $this->createDocumentEntity();
        $ref = new \ReflectionClass($doc);
        $prop = $ref->getProperty('status');
        $prop->setAccessible(true);
        $prop->setValue($doc, Document::STATUS_PENDING);

        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $this->repoMock->expects($this->once())->method('save');

        $result = $this->service->reviewDocument('doc1', 'rejected', 'Bad', 'reviewer2');
        $this->assertInstanceOf(DocumentResponse::class, $result);
        $this->assertSame(Document::STATUS_REJECTED, $doc->getStatus());
    }

    public function testReviewDocument_NotFound(): void
    {
        $this->repoMock->method('findById')->with('unknown')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->reviewDocument('unknown', 'approved');
    }

    // ==================== approveDocument ====================

    public function testApproveDocument_Success(): void
    {
        $doc = $this->createDocumentEntity();
        $ref = new \ReflectionClass($doc);
        $prop = $ref->getProperty('status');
        $prop->setAccessible(true);
        $prop->setValue($doc, Document::STATUS_PENDING);

        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $this->repoMock->expects($this->once())->method('save');

        $result = $this->service->approveDocument('doc1', 'admin', 'OK');
        $this->assertInstanceOf(DocumentResponse::class, $result);
        $this->assertSame(Document::STATUS_APPROVED, $doc->getStatus());
    }

    public function testApproveDocument_NotFound(): void
    {
        $this->repoMock->method('findById')->with('unknown')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->approveDocument('unknown');
    }

    // ==================== rejectDocument ====================

    public function testRejectDocument_Success(): void
    {
        $doc = $this->createDocumentEntity();
        $ref = new \ReflectionClass($doc);
        $prop = $ref->getProperty('status');
        $prop->setAccessible(true);
        $prop->setValue($doc, Document::STATUS_PENDING);

        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $this->repoMock->expects($this->once())->method('save');

        $result = $this->service->rejectDocument('doc1', 'Reason', 'user1');
        $this->assertInstanceOf(DocumentResponse::class, $result);
        $this->assertSame(Document::STATUS_REJECTED, $doc->getStatus());
    }

    public function testRejectDocument_NotFound(): void
    {
        $this->repoMock->method('findById')->with('unknown')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->rejectDocument('unknown', 'Reason');
    }

    // ==================== getPendingDocuments ====================

    public function testGetPendingDocuments(): void
    {
        $docs = [$this->createDocumentEntity()];
        $this->repoMock->method('findByStatus')->with(Document::STATUS_PENDING, 1, 20)->willReturn($docs);
        $result = $this->service->getPendingDocuments(1, 20);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Document::class, $result[0]);
    }

    // ==================== getDocumentsByStatus ====================

    public function testGetDocumentsByStatus(): void
    {
        $docs = [$this->createDocumentEntity()];
        $this->repoMock->method('findByStatus')->with(Document::STATUS_APPROVED, 2, 50)->willReturn($docs);
        $result = $this->service->getDocumentsByStatus(Document::STATUS_APPROVED, 2, 50);
        $this->assertCount(1, $result);
    }

    // ==================== getDocumentHistory ====================

    public function testGetDocumentHistory_Success(): void
    {
        $doc = $this->createDocumentEntity(['id' => 'doc1']);
        $this->repoMock->method('findById')->with('doc1')->willReturn($doc);
        $result = $this->service->getDocumentHistory('doc1');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('created', $result[0]['action']);
        $this->assertSame('current', $result[1]['action']);
    }

    public function testGetDocumentHistory_NotFound(): void
    {
        $this->repoMock->method('findById')->with('unknown')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Document not found');
        $this->service->getDocumentHistory('unknown');
    }
}
