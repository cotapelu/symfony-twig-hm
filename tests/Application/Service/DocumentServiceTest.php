<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Service\DocumentService;
use App\Domain\Entity\Document;
use App\Domain\Repository\DocumentRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DocumentServiceTest extends TestCase
{
    private DocumentRepositoryInterface&MockObject $documentRepository;
    private DocumentService $service;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->service = new DocumentService($this->documentRepository);
    }

    public function testSubmitForReview(): void
    {
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $document = new Document(
            'Test Title',
            'decree',
            'Test Content',
            'Issued By',
            null,
            null,
            null,
            null
        );
        // Set the document ID via reflection (since it's private)
        $reflection = new \ReflectionObject($document);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($document, $documentId);

        $this->documentRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($documentId))
            ->willReturn($document);

        $this->documentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($document));

        $result = $this->service->submitForReview($documentId);

        $this->assertInstanceOf(DocumentResponse::class, $result);
        $this->assertEquals($documentId, $result->getId()->toString());
        $this->assertEquals(Document::STATUS_PENDING, $result->getStatus());
    }
}