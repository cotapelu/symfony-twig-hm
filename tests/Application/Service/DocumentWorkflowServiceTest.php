<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Government\Application\Service\DocumentWorkflowService;
use App\Government\Domain\Entity\Document;
use App\Government\Domain\Entity\DocumentWorkflow;
use App\Government\Domain\Repository\DocumentRepositoryInterface;
use App\Government\Domain\Repository\DocumentWorkflowRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DocumentWorkflowServiceTest extends TestCase
{
    private DocumentRepositoryInterface&MockObject $documentRepository;
    private DocumentWorkflowRepositoryInterface&MockObject $documentWorkflowRepository;
    private DocumentWorkflowService $service;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->documentWorkflowRepository = $this->createMock(DocumentWorkflowRepositoryInterface::class);
        $this->service = new DocumentWorkflowService(
            $this->documentRepository,
            $this->documentWorkflowRepository
        );
    }

    public function testSubmitForReview(): void
    {
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $document = new Document(
            'Test Title',
            'decree',
            'Test Content',
            'Issued By'
        );
        // Set the document ID via reflection (since it's private)
        $reflection = new \ReflectionObject($document);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($document, $documentId);

        $expectedWorkflow = new DocumentWorkflow(
            $documentId,
            DocumentWorkflow::STATUS_PENDING,
            null,
            'Submitted for review'
        );

        $this->documentRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($documentId))
            ->willReturn($document);

        $this->documentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($document));

        $this->documentWorkflowRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($expectedWorkflow));

        $workflow = $this->service->submitForReview($documentId);

        $this->assertInstanceOf(DocumentWorkflow::class, $workflow);
        $this->assertEquals($documentId, $workflow->getDocumentId());
        $this->assertEquals(DocumentWorkflow::STATUS_PENDING, $workflow->getStatus());
    }

    public function testApproveDocument(): void
    {
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $approvedBy = 'officer123';
        $comments = 'Looks good';
        $document = new Document(
            'Test Title',
            'decree',
            'Test Content',
            'Issued By'
        );
        // Set the document ID via reflection
        $reflection = new \ReflectionObject($document);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($document, $documentId);

        $expectedWorkflow = new DocumentWorkflow(
            $documentId,
            DocumentWorkflow::STATUS_APPROVED,
            $approvedBy,
            $comments
        );

        $this->documentRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($documentId))
            ->willReturn($document);

        $this->documentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($document));

        $this->documentWorkflowRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($expectedWorkflow));

        $workflow = $this->service->approveDocument($documentId, $approvedBy, $comments);

        $this->assertInstanceOf(DocumentWorkflow::class, $workflow);
        $this->assertEquals($documentId, $workflow->getDocumentId());
        $this->assertEquals(DocumentWorkflow::STATUS_APPROVED, $workflow->getStatus());
        $this->assertEquals($approvedBy, $workflow->getProcessedBy());
        $this->assertEquals($comments, $workflow->getComments());
    }

    public function testRejectDocument(): void
    {
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $rejectedBy = 'officer123';
        $reason = 'Missing signature';
        $document = new Document(
            'Test Title',
            'decree',
            'Test Content',
            'Issued By'
        );
        // Set the document ID via reflection
        $reflection = new \ReflectionObject($document);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($document, $documentId);

        $expectedWorkflow = new DocumentWorkflow(
            $documentId,
            DocumentWorkflow::STATUS_REJECTED,
            $rejectedBy,
            $reason
        );

        $this->documentRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($documentId))
            ->willReturn($document);

        $this->documentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($document));

        $this->documentWorkflowRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($expectedWorkflow));

        $workflow = $this->service->rejectDocument($documentId, $rejectedBy, $reason);

        $this->assertInstanceOf(DocumentWorkflow::class, $workflow);
        $this->assertEquals($documentId, $workflow->getDocumentId());
        $this->assertEquals(DocumentWorkflow::STATUS_REJECTED, $workflow->getStatus());
        $this->assertEquals($rejectedBy, $workflow->getProcessedBy());
        $this->assertEquals($reason, $workflow->getComments());
    }

    public function testPublishDocument(): void
    {
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $document = new Document(
            'Test Title',
            'decree',
            'Test Content',
            'Issued By'
        );
        // Set the document ID via reflection
        $reflection = new \ReflectionObject($document);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($document, $documentId);

        $expectedWorkflow = new DocumentWorkflow(
            $documentId,
            DocumentWorkflow::STATUS_PUBLISHED,
            null,
            'Published'
        );

        $this->documentRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($documentId))
            ->willReturn($document);

        $this->documentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($document));

        $this->documentWorkflowRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($expectedWorkflow));

        $workflow = $this->service->publishDocument($documentId);

        $this->assertInstanceOf(DocumentWorkflow::class, $workflow);
        $this->assertEquals($documentId, $workflow->getDocumentId());
        $this->assertEquals(DocumentWorkflow::STATUS_PUBLISHED, $workflow->getStatus());
    }

    public function testArchiveDocument(): void
    {
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $document = new Document(
            'Test Title',
            'decree',
            'Test Content',
            'Issued By'
        );
        // Set the document ID via reflection
        $reflection = new \ReflectionObject($document);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($document, $documentId);

        $expectedWorkflow = new DocumentWorkflow(
            $documentId,
            DocumentWorkflow::STATUS_ARCHIVED,
            null,
            'Archived'
        );

        $this->documentRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($documentId))
            ->willReturn($document);

        $this->documentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($document));

        $this->documentWorkflowRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($expectedWorkflow));

        $workflow = $this->service->archiveDocument($documentId);

        $this->assertInstanceOf(DocumentWorkflow::class, $workflow);
        $this->assertEquals($documentId, $workflow->getDocumentId());
        $this->assertEquals(DocumentWorkflow::STATUS_ARCHIVED, $workflow->getStatus());
    }

    public function testGetPendingDocuments(): void
    {
        $expectedWorkflows = [
            new DocumentWorkflow('doc1', DocumentWorkflow::STATUS_PENDING),
            new DocumentWorkflow('doc2', DocumentWorkflow::STATUS_PENDING)
        ];

        $this->documentWorkflowRepository
            ->expects($this->once())
            ->method('findByStatus')
            ->with(
                $this->equalTo(DocumentWorkflow::STATUS_PENDING),
                $this->equalTo(1),
                $this->equalTo(20)
            )
            ->willReturn($expectedWorkflows);

        $workflows = $this->service->getPendingDocuments(1, 20);

        $this->assertEquals($expectedWorkflows, $workflows);
    }

    public function testGetDocumentsByStatus(): void
    {
        $expectedWorkflows = [
            new DocumentWorkflow('doc1', DocumentWorkflow::STATUS_APPROVED),
            new DocumentWorkflow('doc2', DocumentWorkflow::STATUS_APPROVED)
        ];

        $this->documentWorkflowRepository
            ->expects($this->once())
            ->method('findByStatus')
            ->with(
                $this->equalTo(DocumentWorkflow::STATUS_APPROVED),
                $this->equalTo(1),
                $this->equalTo(20)
            )
            ->willReturn($expectedWorkflows);

        $workflows = $this->service->getDocumentsByStatus(DocumentWorkflow::STATUS_APPROVED, 1, 20);

        $this->assertEquals($expectedWorkflows, $workflows);
    }

    public function testGetDocumentStatistics(): void
    {
        $this->documentWorkflowRepository
            ->expects($this->exactly(5))
            ->method('count')
            ->withConsecutive(
                [$this->equalTo(['status' => DocumentWorkflow::STATUS_PENDING])],
                [$this->equalTo(['status' => DocumentWorkflow::STATUS_APPROVED])],
                [$this->equalTo(['status' => DocumentWorkflow::STATUS_REJECTED])],
                [$this->equalTo(['status' => DocumentWorkflow::STATUS_PUBLISHED])],
                [$this->equalTo(['status' => DocumentWorkflow::STATUS_ARCHIVED])]
            )
            ->willReturnOnConsecutiveCalls(10, 5, 2, 8, 3);

        $stats = $this->service->getDocumentStatistics();

        $this->assertEquals([
            'pending' => 10,
            'approved' => 5,
            'rejected' => 2,
            'published' => 8,
            'archived' => 3
        ], $stats);
    }
}