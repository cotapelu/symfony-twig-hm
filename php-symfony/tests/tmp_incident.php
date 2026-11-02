<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IncidentServiceTest extends TestCase
{
    private $incidentRepository;

    protected function setUp(): void
    {
        parent::setUp();
        // mocks omitted
    }

    public function testUpdateIncidentUpdatesProvidedFields(): void
    {
        $incident = new class {
            public function setId($id) {}
            public function setTitle($t) {}
            public function setDescription($d) {}
            public function setIncidentType($t) {}
            public function setSeverity($s) {}
            public function setStatus($s) {}
        };

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($incident);

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($inc) {
                return $inc->getTitle() === 'Updated Title'
                    && $inc->getDescription() === 'Updated desc'
                    && $inc->getSeverity() === 'high'
                    && $inc->getStatus() === 'closed';
            }));

        $request = new class {
            public $title = 'Updated Title';
            public $description = 'Updated desc';
            public $severity = 'high';
            public $status = 'closed';
        };

        $this->service->updateIncident(1, $request);
    }
}
